<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** MySQL DDL commits implicitly. Preflight all supported data conflicts first. */
    public function up(): void
    {
        $this->preflight();

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->enum('role', ['admin', 'user'])->default('user'));
        }
        if (! Schema::hasColumn('users', 'code')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('code')->nullable());
        }

        $status = $this->column('users', 'status');
        if ($status->COLUMN_TYPE !== "enum('active','inactive','blocked')") {
            DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('active','inactive','block','blocked') NOT NULL DEFAULT 'active'");
            DB::table('users')->where('status', 'block')->update(['status' => 'blocked']);
            DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active'");
        }

        // IDs and existing non-empty codes stay unchanged. Mapping is deterministic and retryable.
        DB::table('users')->whereRaw("TRIM(COALESCE(code, '')) = ''")->orderBy('id')->select('id')->chunkById(500, function ($users) {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update(['code' => 'legacy-user-'.$user->id]);
            }
        });
        if ($this->column('users', 'code')->IS_NULLABLE === 'YES') {
            Schema::table('users', fn (Blueprint $table) => $table->string('code')->nullable(false)->change());
        }
        if (! $this->hasUnique('users', ['code'])) {
            Schema::table('users', fn (Blueprint $table) => $table->unique('code', 'users_code_unique'));
        }

        if (! Schema::hasColumn('product_variants', 'thumbnail')) {
            Schema::table('product_variants', fn (Blueprint $table) => $table->renameColumn('thumnail', 'thumbnail'));
        } elseif (Schema::hasColumn('product_variants', 'thumnail')) {
            DB::table('product_variants')->whereRaw("COALESCE(thumbnail, '') = ''")->whereNotNull('thumnail')
                ->update(['thumbnail' => DB::raw('thumnail')]);
            // Keep the legacy column until a separately reviewed contract migration.
        }
        if ($this->column('product_variants', 'thumbnail')->IS_NULLABLE === 'NO') {
            Schema::table('product_variants', fn (Blueprint $table) => $table->string('thumbnail')->nullable()->change());
        }

        $type = $this->column('inventory_transactions', 'type')->COLUMN_TYPE;
        if (! str_contains($type, "'commit'")) {
            $type = substr($type, 0, -1).",'commit')";
            DB::statement('ALTER TABLE `inventory_transactions` MODIFY `type` '.$type.' NOT NULL');
        }
        if (! $this->hasUnique('inventory', ['warehouse_id', 'product_variant_id'])) {
            Schema::table('inventory', fn (Blueprint $table) => $table->unique(['warehouse_id', 'product_variant_id']));
        }
        if ($this->column('products', 'short_description')->DATA_TYPE === 'varchar') {
            Schema::table('products', fn (Blueprint $table) => $table->text('short_description')->nullable()->change());
        }
        $spec = $this->column('product_specs', 'spec_value');
        if ($spec->DATA_TYPE === 'varchar' && (int) $spec->CHARACTER_MAXIMUM_LENGTH < 2000) {
            Schema::table('product_specs', fn (Blueprint $table) => $table->string('spec_value', 2000)->change());
        }
        $fulltext = collect(Schema::getIndexes('posts'))->contains(fn ($index) => $index['type'] === 'fulltext'
            && $index['columns'] === ['title', 'description', 'content']);
        if (! $fulltext) {
            Schema::table('posts', fn (Blueprint $table) => $table->fullText(['title', 'description', 'content']));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('P1 repair is forward-only: mapped codes/status and expanded schema must not be discarded. Restore a verified backup on an isolated target or deploy a reviewed forward repair.');
    }

    private function preflight(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('P1 schema repair is verified for MySQL only.');
        }
        foreach ([
            'users' => ['id', 'status'],
            'inventory' => ['warehouse_id', 'product_variant_id', 'quantity', 'available_quantity', 'reserved_quantity'],
            'inventory_transactions' => ['type', 'transaction_code', 'quantity', 'before_quantity', 'after_quantity'],
            'product_variants' => ['id', 'sku'],
            'products' => ['short_description'],
            'product_specs' => ['spec_value'],
            'posts' => ['title', 'description', 'content'],
        ] as $table => $columns) {
            if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns)) {
                throw new RuntimeException('Unsupported pre-baseline schema: missing required columns on '.$table.'. Review an explicit mapping before DDL.');
            }
        }
        if (! Schema::hasColumn('product_variants', 'thumbnail') && ! Schema::hasColumn('product_variants', 'thumnail')) {
            throw new RuntimeException('Variant image mapping is missing.');
        }
        if (Schema::hasColumn('product_variants', 'thumbnail') && Schema::hasColumn('product_variants', 'thumnail')
            && DB::table('product_variants')->whereRaw("COALESCE(thumbnail, '') <> '' AND COALESCE(thumnail, '') <> '' AND BINARY thumbnail <> BINARY thumnail")->exists()) {
            throw new RuntimeException('Conflicting variant image columns require manual review.');
        }
        if (DB::table('users')->whereNotIn('status', ['active', 'inactive', 'block', 'blocked'])->orWhereNull('status')->exists()) {
            throw new RuntimeException('Unknown user status requires manual review.');
        }
        if (Schema::hasColumn('users', 'role') && DB::table('users')->whereNotIn('role', ['admin', 'user'])->orWhereNull('role')->exists()) {
            throw new RuntimeException('Unknown user role requires manual review.');
        }
        if (Schema::hasColumn('users', 'code')) {
            $duplicate = DB::table('users')->select('code')->whereRaw("TRIM(COALESCE(code, '')) <> ''")->groupBy('code')->havingRaw('COUNT(*) > 1')->exists();
            if ($duplicate) {
                throw new RuntimeException('Duplicate non-empty user codes require manual review.');
            }
            DB::table('users')->whereRaw("TRIM(COALESCE(code, '')) = ''")->orderBy('id')->select('id')->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    if (DB::table('users')->where('code', 'legacy-user-'.$user->id)->where('id', '<>', $user->id)->exists()) {
                        throw new RuntimeException('Generated legacy user code would collide; review mapping before DDL.');
                    }
                }
            });
        }
        if (DB::table('inventory')->select('warehouse_id', 'product_variant_id')->groupBy('warehouse_id', 'product_variant_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate warehouse-SKU balances require manual review.');
        }
        $type = $this->column('inventory_transactions', 'type');
        if ($type->DATA_TYPE !== 'enum') {
            throw new RuntimeException('Unsupported movement type definition.');
        }
    }

    private function column(string $table, string $column): object
    {
        return DB::selectOne('SELECT COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]);
    }

    private function hasUnique(string $table, array $columns): bool
    {
        sort($columns);
        foreach (Schema::getIndexes($table) as $index) {
            $actual = $index['columns'];
            sort($actual);
            if ($index['unique'] && $actual === $columns) {
                return true;
            }
        }

        return false;
    }
};
