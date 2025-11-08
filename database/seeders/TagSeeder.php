<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $styleTags = [
            // Phong cách thời trang
            'Thời trang nam',
            'Thời trang nữ',
            'Thời trang công sở',
            'Thời trang dạo phố',
            'Thời trang thể thao',
            'Thời trang mùa hè',
            'Thời trang mùa đông',
            'Phong cách tối giản',
            'Phong cách cá tính',
            'Phong cách thanh lịch',
            'Phong cách vintage',
            'Phong cách Hàn Quốc',
            'Phong cách châu Âu',
            'Phong cách đường phố',
            'Phong cách unisex',

            // Trang phục cụ thể
            'Áo sơ mi nam',
            'Áo thun nữ',
            'Quần jean',
            'Chân váy',
            'Đầm dự tiệc',
            'Vest nam',
            'Áo blazer nữ',
            'Áo khoác da',
            'Áo hoodie',
            'Áo polo',
            'Quần tây',
            'Đồ ngủ',
            'Đồ đôi',
            'Đồ công sở',
            'Set đồ matching',

            // Chất liệu và xu hướng
            'Cotton',
            'Linen',
            'Denim',
            'Vải lụa',
            'Vải nỉ',
            'Chất liệu bền vững',
            'Thời trang xanh',
            'Xu hướng 2025',
            'Bộ sưu tập mới',
            'Thời trang bền vững',

            // Phụ kiện và chi tiết
            'Giày sneaker',
            'Giày da',
            'Túi xách nữ',
            'Kính râm',
            'Dây nịt nam',
            'Mũ lưỡi trai',
            'Khăn choàng cổ',
            'Trang sức',
            'Đồng hồ thời trang',

            // Mẹo phối đồ & chăm sóc
            'Phối đồ nam',
            'Phối đồ nữ',
            'Mẹo chọn size',
            'Cách bảo quản quần áo',
            'Cách giặt giữ form',
            'Phong cách cá nhân',
            'Mix & match',
            'Phong cách tối giản',
            'Gợi ý outfit',
            'Xu hướng mặc đẹp',
        ];


        foreach ($styleTags as $tag) {
            Tag::create([
                'name' => $tag,
                'slug' => Str::slug($tag) . '-' . Str::random(8),
            ]);
        }
    }
}
