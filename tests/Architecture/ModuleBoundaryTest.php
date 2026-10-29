<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

class ModuleBoundaryTest extends TestCase
{
    private const DEPENDENCIES = ['Identity' => [], 'Catalog' => [], 'Pricing' => ['Catalog'],
        'Inventory' => ['Identity'], 'Payments' => [], 'Ordering' => ['Identity', 'Pricing', 'Inventory', 'Payments']];

    private const MODELS = ['User' => 'Identity', 'Product' => 'Catalog', 'ProductVariant' => 'Catalog',
        'Inventory' => 'Inventory', 'InventoryOperation' => 'Inventory', 'InventoryReservation' => 'Inventory',
        'InventoryReservationItem' => 'Inventory', 'InventoryLegacyHold' => 'Inventory', 'InventoryDiscrepancy' => 'Inventory',
        'InventoryTransaction' => 'Inventory', 'Warehouse' => 'Inventory', 'PaymentAttempt' => 'Payments',
        'Order' => 'Ordering', 'OrderItem' => 'Ordering', 'OrderTransition' => 'Ordering', 'Cart' => 'Ordering'];

    private function files(): array
    {
        $result = [];
        $root = dirname(__DIR__, 2).'/app/Modules';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $result[str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1))] = file_get_contents($file->getPathname());
            }
        }

        return $result;
    }

    public function test_modules_depend_only_on_public_contracts_and_have_no_cycles(): void
    {
        $graph = array_fill_keys(array_keys(self::DEPENDENCIES), []);
        foreach ($this->files() as $path => $body) {
            $owner = explode('/', $path)[0];
            if ($path === 'ModuleServiceProvider.php') {
                continue;
            }
            $this->assertArrayHasKey($owner, $graph, $path.' needs an explicit module owner');
            $this->assertDoesNotMatchRegularExpression('/App\\\\Services\\\\/', $body, $path.' must not call a compatibility adapter');
            foreach (token_get_all($body) as $token) {
                if (! is_array($token) || ! in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                    continue;
                }
                if (! preg_match('/^\\\\?App\\\\Modules\\\\([^\\\\]+)(.*)$/', $token[1], $match)) {
                    continue;
                }
                $target = $match[1];
                if ($target === $owner) {
                    continue;
                }
                $this->assertContains($target, self::DEPENDENCIES[$owner], $path.' forbidden module dependency');
                $this->assertStringStartsWith('\\Contracts', $match[2], $path.' must use the other module public contract');
                $graph[$owner][$target] = true;
            }
        }
        $visit = function ($node, array $stack = []) use (&$visit, $graph) {
            $this->assertNotContains($node, $stack, 'Module dependency cycle');
            foreach (array_keys($graph[$node]) as $child) {
                $visit($child, [...$stack, $node]);
            }
        };
        foreach (array_keys($graph) as $node) {
            $visit($node);
        }
        foreach (array_keys($graph) as $module) {
            $this->assertDirectoryExists(dirname(__DIR__, 2).'/app/Modules/'.$module.'/Contracts');
        }
    }

    public function test_domain_application_and_contracts_are_independent_of_framework_and_models(): void
    {
        foreach ($this->files() as $path => $body) {
            if (! preg_match('~/(Domain|Application|Contracts)/~', $path)) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('/(?:Illuminate|App\\\\Models|App\\\\Services|Symfony)\\\\/', $body, $path);
            $this->assertDoesNotMatchRegularExpression('/\\\\Infrastructure\\\\/', $body, $path);
            $tokens = array_values(array_filter(token_get_all($body), fn ($token) => ! is_array($token)
                || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)));
            foreach ($tokens as $index => $token) {
                if (! is_array($token) || ! in_array($token[0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)
                    || ($tokens[$index + 1] ?? null) !== '(') {
                    continue;
                }
                $previous = $tokens[$index - 1] ?? null;
                if (is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
                    continue;
                }
                $this->assertNotContains(strtolower(ltrim($token[1], '\\')), ['auth', 'request', 'response', 'config', 'app', 'now', 'dispatch',
                    'collect', 'env', 'abort', 'event', 'report', 'logger', 'session', 'cache', 'redirect', 'view', 'validator'], $path.' implicit runtime helper');
            }
        }
    }

    public function test_persistence_uses_only_models_owned_by_its_module(): void
    {
        foreach ($this->files() as $path => $body) {
            $owner = explode('/', $path)[0];
            if (! isset(self::DEPENDENCIES[$owner])) {
                continue;
            }
            // Expand grouped imports as well as ordinary imports and fully qualified names.
            preg_match_all('/use\s+App\\\\Models\\\\\{([^}]+)\}/s', $body, $groups);
            $names = [];
            foreach ($groups[1] as $group) {
                foreach (explode(',', $group) as $model) {
                    $names[] = preg_split('/\s+as\s+/i', trim($model))[0];
                }
            }
            preg_match_all('/App\\\\Models\\\\([A-Za-z_][A-Za-z0-9_]*)/', $body, $simple);
            foreach ([...$names, ...$simple[1]] as $model) {
                $this->assertArrayHasKey($model, self::MODELS, $path.' unregistered table owner');
                $this->assertSame($owner, self::MODELS[$model], $path.' cross-module model import');
            }
            preg_match_all('/DB::table\([\'"]([a-z_]+)/', $body, $tables);
            foreach ($tables[1] as $table) {
                $tableOwner = match (true) {
                    str_starts_with($table, 'inventory') => 'Inventory', str_starts_with($table, 'payment') => 'Payments',
                    str_starts_with($table, 'order'), str_starts_with($table, 'cart') => 'Ordering', default => null,
                };
                $this->assertSame($owner, $tableOwner, $path.' cross-module SQL');
            }
        }
    }

    public function test_public_dto_contracts_cannot_return_eloquent_models(): void
    {
        foreach ($this->files() as $path => $body) {
            if (! str_contains($path, '/Contracts/')) {
                continue;
            }
            $class = 'App\\Modules\\'.str_replace('/', '\\', substr($path, 0, -4));
            $reflection = new \ReflectionClass($class);
            if (! $reflection->isInterface() && ! $reflection->isSubclassOf(\Throwable::class)) {
                $this->assertTrue($reflection->isReadOnly(), $class.' DTO must be immutable');
            }
            foreach ($reflection->getMethods() as $method) {
                foreach ([$method->getReturnType(), ...array_map(fn ($parameter) => $parameter->getType(), $method->getParameters())] as $type) {
                    if ($type === null) {
                        continue;
                    }
                    $this->assertDoesNotMatchRegularExpression('/App\\\\Models|Illuminate|Symfony/', (string) $type, $class.' leaks framework across a contract');
                }
            }
        }
    }

    public function test_shared_kernel_has_no_business_module_or_framework_dependency(): void
    {
        $root = dirname(__DIR__, 2).'/app/SharedKernel';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php' || str_contains(str_replace('\\', '/', $file->getPathname()), '/Infrastructure/')) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('/(?:Illuminate|Symfony|App\\\\(?:Modules|Models|Services))\\\\/', file_get_contents($file->getPathname()), $file->getFilename());
        }
    }

    public function test_inventory_balance_assignments_have_one_runtime_writer(): void
    {
        $root = dirname(__DIR__, 2).'/app';
        $assignments = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $body = file_get_contents($file->getPathname());
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (preg_match('/->(?:reserved_quantity|available_quantity)\s*(?:[+\-]?=|\+\+|--)/', $body)) {
                $assignments++;
                $this->assertSame('Modules/Inventory/Infrastructure/StockWriter.php', $relative, 'Stock balance mutation outside protocol writer');
            }
            if (preg_match('/Inventory::(?:create|insert|upsert|firstOrCreate|updateOrCreate)\s*\(/', $body)) {
                $this->assertSame('Modules/Inventory/Infrastructure/StockStore.php', $relative, 'Inventory row creation outside stock lock protocol');
            }
        }
        $this->assertSame(1, $assignments);
    }
}
