<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\ReviewMedia;
use App\Models\ReviewExperience;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // Cố định product_id = 9
        $product = Product::find(27);

        if (!$product) {
            $this->command->warn('⚠️ Không tìm thấy sản phẩm ID = 9');
            return;
        }

        // Lấy ngẫu nhiên variant của sản phẩm (nếu có)
        $variant = ProductVariant::where('product_id', $product->id)->inRandomOrder()->first();

        // Tạo 5 review ngẫu nhiên
        for ($i = 1; $i <= 5; $i++) {
            // Chọn user_id ngẫu nhiên từ 15 → 20
            $userId = rand(1, 7);

            $review = Review::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id ?? null,
                'rating' => rand(3, 5),
                'content' => fake()->paragraph(),
            ]);

            // Thêm ảnh mẫu
            ReviewMedia::create([
                'review_id' => $review->id,
                'url' => 'https://res.cloudinary.com/demo/image/upload/sample.jpg',
                'type' => 'image',
            ]);

            // Thêm đánh giá chi tiết theo trải nghiệm
            $criteria = ['Hiệu năng', 'Thời lượng pin', 'Hiệu năng camera'];
            foreach ($criteria as $criterion) {
                ReviewExperience::create([
                    'review_id' => $review->id,
                    'criterion' => $criterion,
                    'rating' => rand(3, 5),
                ]);
            }
        }

        $this->command->info('✅ Đã tạo 5 review ngẫu nhiên cho sản phẩm ID = 9');
    }
}
