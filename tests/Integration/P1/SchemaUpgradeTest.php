<?php

namespace Tests\Integration\P1;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;
use Tests\Support\DatabaseSandbox;
use Tests\TestCase;

class SchemaUpgradeTest extends TestCase
{
    private ?DatabaseSandbox $sandbox = null;

    private PDO $target;

    protected function setUp(): void
    {
        if (getenv('P1_SCHEMA_TESTS') !== '1') {
            $this->markTestSkipped('P1 schema tests are opt-in: P1_SCHEMA_TESTS=1. They use .env only to create uniquely named isolated databases.');
        }
        parent::setUp();
        $this->sandbox = new DatabaseSandbox;
        $name = 'apple_project_p1_fresh_'.bin2hex(random_bytes(6));
        $this->target = $this->sandbox->create($name);
        config(['database.connections.p1' => $this->sandbox->connectionConfig($name)]);
        DB::setDefaultConnection('p1');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->sandbox !== null) {
                DB::purge('p1');
                $this->sandbox->dropCreated();
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_native_migrate_bootstraps_an_empty_database_and_new_writers_work(): void
    {
        $this->migrate();
        $this->assertSame(51, DB::table('migrations')->count());
        $this->assertSame(0, DB::table('users')->count());
        $this->assertTargetSchema();
        $this->postJson('/api/register', ['name' => 'P1 synthetic user', 'email' => 'p1-register@example.test', 'password' => 'Synthetic-password-123'])
            ->assertCreated()->assertJsonStructure(['status', 'message', 'user', 'token']);
        $user = User::where('email', 'p1-register@example.test')->firstOrFail();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $user->code);
        $this->assertSame('user', $user->role);
        $this->assertSame('active', $user->status);
        $factory = User::factory()->create(['email' => 'p1-factory@example.test']);
        $this->assertNotSame($user->code, $factory->code);
        $this->assertTrue($factory->exists);
        DB::table('categories')->insert(['id' => 101, 'name' => 'P1 category', 'slug' => 'p1-category']);
        DB::table('products')->insert(['id' => 101, 'category_id' => 101, 'name' => 'P1 phone', 'slug' => 'p1-phone']);
        $variant = ProductVariant::create(['product_id' => 101, 'sku' => 'P1-NO-IMAGE', 'price' => 100, 'cost_price' => 60]);
        $this->assertNull($variant->fresh()->thumbnail);
        $this->migrate();
        $this->assertSame(51, DB::table('migrations')->count());
    }

    public function test_current_snapshot_upgrade_preserves_ids_money_snapshots_and_existing_codes(): void
    {
        $this->loadBaseline();
        $this->seedLegacyData();
        $before = $this->businessFingerprint();
        $this->migrate();
        $this->assertTargetSchema();
        $this->assertSame($before, $this->businessFingerprint());
        $this->assertSame('KEEP-P1-CODE', DB::table('users')->where('id', 101)->value('code'));
        $this->assertSame('admin', DB::table('users')->where('id', 101)->value('role'));
        $this->assertSame('legacy-user-102', DB::table('users')->where('id', 102)->value('code'));
        $this->assertSame('legacy-user-103', DB::table('users')->where('id', 103)->value('code'));
        $migration = $this->repair();
        $stable = DatabaseSandbox::schemaSnapshotFrom($this->target);
        $migration->up();
        $this->assertSame($stable, DatabaseSandbox::schemaSnapshotFrom($this->target));
        $this->assertSame($before, $this->businessFingerprint());
        $this->assertForeignKeyRejectsOrphan();
        $this->assertDuplicateCodeIsRejected();
    }

    public function test_supported_legacy_column_drift_is_repaired_without_losing_values(): void
    {
        $this->loadBaseline();
        $this->seedLegacyData();
        $before = $this->businessFingerprint();
        DB::statement('ALTER TABLE users DROP COLUMN role');
        DB::statement("ALTER TABLE users MODIFY status ENUM('active','inactive','block','blocked') NOT NULL DEFAULT 'active'");
        DB::table('users')->where('status', 'blocked')->update(['status' => 'block']);
        DB::statement("ALTER TABLE users MODIFY status ENUM('active','inactive','block') NOT NULL DEFAULT 'active'");
        DB::statement('ALTER TABLE product_variants RENAME COLUMN thumbnail TO thumnail');
        DB::statement("ALTER TABLE inventory_transactions MODIFY type ENUM('import','export','transfer','adjust','return','damage','reserve','release','inventory') NOT NULL");
        DB::statement('ALTER TABLE products MODIFY short_description VARCHAR(255) NULL');
        DB::statement('ALTER TABLE product_specs MODIFY spec_value VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE posts DROP INDEX posts_title_description_content_fulltext');
        $this->migrate();
        $this->assertTargetSchema();
        $this->assertSame('blocked', DB::table('users')->where('id', 102)->value('status'));
        $this->assertSame('user', DB::table('users')->where('id', 101)->value('role'));
        $this->assertSame('p1-fixture.webp', DB::table('product_variants')->where('id', 101)->value('thumbnail'));
        $this->assertSame($before, $this->businessFingerprint());
        DB::table('inventory_transactions')->insert([
            'transaction_code' => 'P1-COMMIT', 'warehouse_id' => 1, 'product_variant_id' => 101,
            'type' => 'commit', 'quantity' => -1, 'before_quantity' => 10, 'after_quantity' => 9,
        ]);
        $this->assertSame(1, DB::table('inventory_transactions')->where('type', 'commit')->count());
    }

    public function test_ambiguous_codes_stop_before_any_schema_changes(): void
    {
        $this->loadBaseline();
        $this->seedLegacyData();
        DB::table('users')->where('id', 102)->update(['code' => 'KEEP-P1-CODE']);
        DB::statement('ALTER TABLE users DROP COLUMN role');
        $before = DatabaseSandbox::schemaSnapshotFrom($this->target);
        try {
            $this->repair()->up();
            $this->fail('Duplicate codes must abort before DDL.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Duplicate non-empty user codes', $exception->getMessage());
        }
        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertSame($before, DatabaseSandbox::schemaSnapshotFrom($this->target));
    }

    public function test_schema_and_synthetic_data_backup_can_be_restored_to_a_new_isolated_database(): void
    {
        $this->loadBaseline();
        $this->seedLegacyData();
        $this->migrate();
        $sql = DatabaseSandbox::schemaSnapshotFrom($this->target)."SET FOREIGN_KEY_CHECKS=0;\n";
        $tables = DB::select('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME');
        $rows = [];
        foreach ($tables as $table) {
            if ($table->TABLE_NAME === 'migrations') {
                continue;
            }
            $rows[$table->TABLE_NAME] = $this->target->query('SELECT * FROM `'.$table->TABLE_NAME.'`')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows[$table->TABLE_NAME] as $row) {
                $columns = '`'.implode('`, `', array_keys($row)).'`';
                $values = array_map(fn ($value) => $value === null ? 'NULL' : $this->target->quote((string) $value), array_values($row));
                $sql .= 'INSERT INTO `'.$table->TABLE_NAME.'` ('.$columns.') VALUES ('.implode(', ', $values).");\n";
            }
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents(base_path('tests/.runtime/p1-synthetic-backup.sql'), $sql);
        $restore = $this->sandbox->create('apple_project_p1_restore_'.bin2hex(random_bytes(6)));
        DatabaseSandbox::loadSnapshot($restore, $sql);
        foreach ($rows as $table => $expected) {
            $this->assertSame($expected, $restore->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC), $table);
        }
        $this->assertSame(51, (int) $restore->query('SELECT COUNT(*) FROM migrations')->fetchColumn());
    }

    private function loadBaseline(): void
    {
        DatabaseSandbox::loadSnapshot($this->target, file_get_contents(database_path('schema/mysql-schema.sql')));
    }

    private function migrate(): void
    {
        // Laravel's native MySQL schema loader uses the installed mysql client.
        $path = getenv('PATH');
        $clients = glob(dirname(PHP_BINARY, 3).'/mysql/*/bin/mysql.exe');
        if ($clients !== []) {
            putenv('PATH='.dirname($clients[0]).PATH_SEPARATOR.$path);
            $_ENV['PATH'] = $_SERVER['PATH'] = getenv('PATH');
        }
        try {
            $exit = Artisan::call('migrate', ['--database' => 'p1', '--schema-path' => database_path('schema/mysql-schema.sql'), '--force' => true, '--no-interaction' => true]);
            $this->assertSame(0, $exit, 'Native migrate must succeed on the isolated database.');
        } finally {
            putenv('PATH='.$path);
            $_ENV['PATH'] = $_SERVER['PATH'] = $path;
        }
    }

    private function repair(): object
    {
        return require database_path('migrations/2026_10_28_000001_reconcile_application_schema.php');
    }

    private function assertTargetSchema(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['role', 'code', 'status']));
        $this->assertTrue(Schema::hasColumn('product_variants', 'thumbnail'));
        $this->assertTrue(Schema::hasIndex('users', 'users_code_unique', 'unique'));
        $type = DB::selectOne("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_transactions' AND COLUMN_NAME = 'type'")->COLUMN_TYPE;
        $this->assertStringContainsString("'commit'", $type);
    }

    private function seedLegacyData(): void
    {
        foreach ([101 => 'KEEP-P1-CODE', 102 => '', 103 => ''] as $id => $code) {
            DB::table('users')->insert([
                'id' => $id, 'name' => 'P1 synthetic '.$id, 'email' => $id.'@example.test', 'password' => 'synthetic-only',
                'code' => $code, 'role' => $id === 101 ? 'admin' : 'user', 'status' => $id === 101 ? 'active' : 'blocked',
            ]);
        }
        DB::table('categories')->insert(['id' => 101, 'name' => 'P1 category', 'slug' => 'p1-category']);
        DB::table('products')->insert(['id' => 101, 'category_id' => 101, 'name' => 'P1 phone', 'slug' => 'p1-phone']);
        DB::table('product_variants')->insert(['id' => 101, 'product_id' => 101, 'sku' => 'P1-SKU', 'price' => '12345.00', 'cost_price' => '8000.00', 'thumbnail' => 'p1-fixture.webp']);
        DB::table('warehouses')->insert(['id' => 1, 'code' => 'P1-WH', 'name' => 'P1 warehouse', 'address' => 'Synthetic', 'city' => 'Synthetic']);
        DB::table('inventory')->insert(['id' => 101, 'warehouse_id' => 1, 'product_variant_id' => 101, 'quantity' => 10, 'available_quantity' => 8, 'reserved_quantity' => 2]);
        DB::table('orders')->insert(['id' => 101, 'user_id' => 101, 'warehouse_id' => 1, 'code' => 'P1-ORDER', 'total_price' => '24690.00', 'final_amount' => '24690.00']);
        DB::table('order_items')->insert(['id' => 101, 'order_id' => 101, 'product_id' => 101, 'variant_id' => 101, 'product_name' => 'P1 snapshot', 'quantity' => 2, 'price' => '12345.00', 'total' => '24690.00']);
        DB::table('inventory_transactions')->insert(['id' => 101, 'transaction_code' => 'P1-RESERVE', 'warehouse_id' => 1, 'product_variant_id' => 101, 'type' => 'reserve', 'quantity' => 2, 'before_quantity' => 10, 'after_quantity' => 10, 'reference_type' => 'order', 'reference_id' => 101]);
    }

    private function businessFingerprint(): array
    {
        $result = [];
        foreach (['orders', 'order_items', 'inventory', 'inventory_transactions'] as $table) {
            $result[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        return $result;
    }

    private function assertForeignKeyRejectsOrphan(): void
    {
        try {
            DB::table('order_items')->insert(['order_id' => 999999, 'product_id' => 101, 'variant_id' => 101, 'product_name' => 'Orphan', 'quantity' => 1, 'price' => 1, 'total' => 1]);
            $this->fail('Foreign key must reject an orphan item.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }
    }

    private function assertDuplicateCodeIsRejected(): void
    {
        try {
            DB::table('users')->where('id', 102)->update(['code' => 'KEEP-P1-CODE']);
            $this->fail('Unique code must reject duplicates.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }
    }
}
