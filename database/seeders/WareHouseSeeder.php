<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('warehouses')->insert([
            [
                'code' => 'WH001',
                'name' => 'Kho Trung Tâm Hà Nội',
                'type' => 'Main',
                'address' => '123 Đường Láng',
                'city' => 'Hà Nội',
                'district' => 'Đống Đa',
                'phone' => '0901234567',
                'email' => 'kho.hanoi@example.com',
                'manager_name' => 'Nguyễn Văn A',
                'manager_phone' => '0912345678',
                'is_active' => true,
                'notes' => 'Kho chính ở Hà Nội',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'WH002',
                'name' => 'Kho Hồ Chí Minh',
                'type' => 'Branch',
                'address' => '456 Nguyễn Trãi',
                'city' => 'Hồ Chí Minh',
                'district' => 'Quận 5',
                'phone' => '0907654321',
                'email' => 'kho.hcm@example.com',
                'manager_name' => 'Trần Thị B',
                'manager_phone' => '0987654321',
                'is_active' => true,
                'notes' => 'Kho chi nhánh tại HCM',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'WH003',
                'name' => 'Kho Đà Nẵng',
                'type' => 'Branch',
                'address' => '789 Lê Duẩn',
                'city' => 'Đà Nẵng',
                'district' => 'Hải Châu',
                'phone' => '0933333333',
                'email' => 'kho.dn@example.com',
                'manager_name' => 'Lê Văn C',
                'manager_phone' => '0977777777',
                'is_active' => false,
                'notes' => 'Kho đang bảo trì',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
