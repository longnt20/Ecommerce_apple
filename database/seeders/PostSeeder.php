<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $stylePosts = [
            [
                'title' => 'Phong cách thời trang nam hiện đại – Tối giản nhưng tinh tế',
                'description' => 'Khám phá bí quyết phối đồ phong cách tối giản cho nam giới – xu hướng được yêu thích trong năm nay tại StyleX.',
                'content' => 'Thời trang nam hiện đại đang hướng tới phong cách tối giản (minimalist) – nơi mọi chi tiết đều được chọn lọc kỹ càng để tạo nên tổng thể tinh tế, thanh lịch. 

Một tủ đồ nam tối giản thường bao gồm những món cơ bản như áo sơ mi trắng, quần tây đen, áo thun trơn, blazer và giày da. Khi biết cách phối hợp, bạn có thể tạo ra nhiều phong cách khác nhau mà không cần quá nhiều món đồ.

Điều quan trọng trong phong cách này là **chất liệu và form dáng**. Một chiếc áo vừa vặn, vải cotton thoáng mát hay linen tự nhiên sẽ mang lại cảm giác thoải mái và sang trọng.

Tại StyleX, bạn có thể dễ dàng tìm thấy những thiết kế nam mang phong cách hiện đại, tinh giản nhưng không hề đơn điệu – phù hợp cho cả đi làm, đi chơi hay dự tiệc.'
            ],
            [
                'title' => 'Xu hướng thời trang nữ 2025 – Thanh lịch và năng động',
                'description' => 'StyleX cập nhật những xu hướng thời trang nữ nổi bật năm 2025: từ phong cách thanh lịch công sở đến streetwear năng động.',
                'content' => 'Bước sang năm 2025, thời trang nữ chứng kiến sự hòa trộn giữa phong cách thanh lịch cổ điển và nét năng động hiện đại. 

Áo blazer oversize, chân váy midi, đầm hai dây chất liệu satin, hay quần jeans ống suông là những món được yêu thích nhất. Xu hướng phối đồ theo tone màu pastel nhẹ nhàng hoặc các gam trung tính như beige, kem, xám vẫn tiếp tục được ưa chuộng.

Bên cạnh đó, phong cách **mix & match linh hoạt** lên ngôi – phụ nữ hiện đại không ngại thử nghiệm, kết hợp các item để thể hiện cá tính riêng. 

StyleX mang đến bộ sưu tập thời trang nữ được thiết kế tinh tế, dễ phối và phù hợp cho mọi hoàn cảnh – giúp bạn tự tin tỏa sáng mỗi ngày.'
            ],
            [
                'title' => 'Cách chọn size quần áo chuẩn – Bí quyết mua sắm online tại StyleX',
                'description' => 'Chia sẻ mẹo chọn size quần áo chính xác khi mua hàng online để luôn vừa vặn và thoải mái.',
                'content' => 'Một trong những nỗi lo lớn nhất khi mua quần áo online là chọn sai size. Để giúp bạn yên tâm mua sắm tại StyleX, chúng tôi chia sẻ một số bí quyết đơn giản mà hiệu quả.

Trước hết, hãy **đo 3 vòng cơ bản**: ngực, eo và hông. So sánh số đo này với bảng size chuẩn mà StyleX cung cấp trong từng sản phẩm. Mỗi mẫu quần áo có thể có sự chênh lệch nhỏ tùy chất liệu và form dáng, nên bạn hãy đọc kỹ phần mô tả.

Ngoài ra, nếu bạn thích mặc thoải mái, nên chọn **size lớn hơn 1 số** so với form ôm sát. Ngược lại, nếu muốn phong cách trẻ trung, năng động thì **chọn form vừa vặn** là lựa chọn tốt nhất.

Với chính sách đổi trả dễ dàng của StyleX, bạn hoàn toàn yên tâm khi mua sắm trực tuyến mà vẫn đảm bảo phong cách và sự vừa vặn.'
            ],
            [
                'title' => 'Phối đồ đôi cho cặp đôi – Tình yêu trong từng chi tiết thời trang',
                'description' => 'Gợi ý những set đồ đôi đẹp và tinh tế giúp các cặp đôi thể hiện phong cách riêng cùng StyleX.',
                'content' => 'Thời trang đôi không chỉ là xu hướng, mà còn là cách các cặp đôi thể hiện sự đồng điệu và kết nối trong phong cách sống. 

Bạn không nhất thiết phải mặc giống hệt nhau để tạo cảm giác “đồ đôi”. Thay vào đó, hãy chọn **màu sắc, họa tiết hoặc chất liệu tương đồng**. Ví dụ, anh mặc áo sơ mi xanh navy thì cô có thể chọn váy xanh nhạt hoặc áo blouse cùng tone.

Các bộ sưu tập Couple Collection của StyleX hướng đến sự tinh tế, trẻ trung và dễ phối. Chúng tôi tin rằng mỗi cặp đôi đều có thể tìm thấy phong cách riêng của mình – dù là năng động, thanh lịch hay lãng mạn.

Cùng StyleX tạo nên những khoảnh khắc thời trang đáng nhớ bên người thương của bạn!'
            ],
            [
                'title' => 'Chăm sóc và bảo quản quần áo đúng cách – Giữ phong độ bền lâu',
                'description' => 'Hướng dẫn cách giặt, phơi và bảo quản quần áo giúp giữ form và màu sắc lâu dài, được StyleX khuyên dùng.',
                'content' => 'Đầu tư vào thời trang không chỉ dừng lại ở việc chọn đồ đẹp mà còn ở cách bạn **bảo quản quần áo** sau khi sử dụng. Một vài thói quen nhỏ có thể giúp trang phục luôn như mới và bền màu.

Hãy đọc kỹ nhãn hướng dẫn giặt trên từng sản phẩm – đặc biệt với các chất liệu như lụa, len hoặc cotton cao cấp. Giặt tay bằng nước lạnh sẽ giúp giữ form áo tốt hơn, trong khi phơi nơi thoáng mát tránh ánh nắng trực tiếp giúp vải không bị phai màu.

Quần áo nên được **phân loại theo màu và chất liệu** trước khi giặt, đồng thời tránh dùng quá nhiều chất tẩy. Khi ủi, hãy chọn nhiệt độ phù hợp cho từng loại vải để tránh hư hỏng.

Tại StyleX, chúng tôi tin rằng phong cách bền vững bắt đầu từ việc chăm sóc trang phục đúng cách – để mỗi bộ đồ luôn thể hiện trọn vẹn đẳng cấp và cá tính của bạn.'
            ],
        ];


        $user = User::query()->where('email', 'admin@example.com')->first();

        foreach ($stylePosts as $post) {
            Post::create([
                'user_id' => $user->id,
                'category_id' => rand(16,27),
                'title' => $post['title'],
                'slug' => Str::slug($post['title']) . '-' . Str::uuid(),
                'description' => $post['description'],
                'content' => $post['content'],
                'status' => 'published',
                'views' => rand(100, 5000),
                'is_hot' => 1,
                'published_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
