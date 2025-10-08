<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Báo Thay Đổi Vai Trò - LongTech</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap');

        body {
            font-family: 'Manrope', Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f8;
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#f5f5f8; font-family: 'Manrope', Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f8; padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                    style="background-color:#ffffff; padding:0; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.06); overflow:hidden; max-width:100%;"> 

                    <!-- Header -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #E27447, #f59776); padding:40px 20px 30px;">
                            <img src="https://res.cloudinary.com/dere3na7i/image/upload/c_thumb,w_200,g_face/v1740311680/logo-container_zi19ug.png"
                                alt="Longtech Logo" width="80" height="80"
                                style="box-shadow: 0 6px 15px rgba(0,0,0,0.12); border-radius: 16px; max-width:80px; object-fit:contain;">
                            <h1 style="color:#ffffff; margin-top:20px; font-size:28px; font-weight:700; letter-spacing:0.5px;">
                                Longtech</h1>
                            <p style="color:#ffffff; opacity:0.95; margin:8px 0 0; font-size:16px; letter-spacing: 0.3px;">
                                Thông Báo Thay Đổi Vai Trò</p>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td align="center" style="padding:40px 30px 20px;">
                            <h2 style="color:#222; margin:0; font-size:24px; font-weight:600;">
                                Xin chào {{ $user->name }},</h2>
                            <p style="color:#555; font-size:16px; line-height:1.6; margin-top:16px; text-align: center;">
                                Vai trò hiện tại của bạn là: <strong style="color:#E27447;">{{ $oldRole }}</strong><br>
                                Vai trò mới của bạn trên nền tảng Longtech là: <strong style="color:#E27447;">{{ $newRole }}</strong>.
                            </p>
                        </td>
                    </tr>

                    <!-- Role Details Section -->
                    <tr>
                        <td style="padding:0 30px 30px;">
                            <div style="background: linear-gradient(to bottom right, #fff8f5, #ffefe9); border-radius:14px; padding:25px 20px; text-align:center; box-shadow: 0 8px 15px rgba(226,116,71,0.08);">
                                <p style="font-size:18px; color:#E27447; font-weight:600; margin-bottom:20px;">
                                    🎉 Thông Tin Vai Trò Mới Của Bạn 🎉</p>

                                <div style="background-color:white; border-radius:10px; padding:20px; margin-bottom:20px; box-shadow:0 6px 15px rgba(226,116,71,0.1);">
                                    <p style="margin:0; font-size:14px; color:#666; margin-bottom:10px;">Vai trò mới:</p>
                                    <div style="display:inline-block; background-color:#f0f0f0; border-radius:8px; padding:12px 20px; border:2px dashed #E27447;">
                                        <span style="font-size:24px; font-weight:700; color:#E27447; letter-spacing:2px;">
                                            {{ $newRole }}
                                        </span>
                                    </div>
                                </div>

                                <a href="#"
                                    style="display:inline-block; background: linear-gradient(to right, #E27447, #f59776); color:#fff; padding:14px 30px; font-size:16px; text-decoration:none; border-radius:10px; font-weight:600; letter-spacing:0.5px; box-shadow:0 6px 15px rgba(226,116,71,0.3); transition: all 0.3s;">
                                    👉 XEM CHI TIẾT
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Additional Information -->
                    <tr>
                        <td align="center" style="padding:5px 30px 30px;">
                            <div style="border-top:1px solid #eee; padding-top:20px; max-width: 90%; margin: 0 auto;">
                                <p style="font-size:15px; color:#555; line-height: 1.6; text-align: center;">
                                    Nếu bạn có bất kỳ câu hỏi nào về vai trò mới của mình, vui lòng liên hệ với chúng tôi
                                    qua email hoặc truy cập trang hồ sơ của bạn để tìm hiểu thêm.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#fff8f5; padding:30px; border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <div style="margin-bottom: 16px;">
                                            <a href="#" style="display:inline-block; margin:0 8px;">
                                                <img src="/api/placeholder/28/28" alt="Facebook"
                                                    style="width:28px; height:28px; border-radius:6px;">
                                            </a>
                                            <a href="#" style="display:inline-block; margin:0 8px;">
                                                <img src="/api/placeholder/28/28" alt="Instagram"
                                                    style="width:28px; height:28px; border-radius:6px;">
                                            </a>
                                            <a href="#" style="display:inline-block; margin:0 8px;">
                                                <img src="/api/placeholder/28/28" alt="LinkedIn"
                                                    style="width:28px; height:28px; border-radius:6px;">
                                            </a>
                                            <a href="#" style="display:inline-block; margin:0 8px;">
                                                <img src="/api/placeholder/28/28" alt="YouTube"
                                                    style="width:28px; height:28px; border-radius:6px;">
                                            </a>
                                        </div>
                                        <p style="font-size:14px; color:#777; margin:0 0 5px;">
                                            &copy; 2025 Longtech. Mọi quyền được bảo lưu.
                                        </p>
                                        <p style="font-size:13px; color:#999; margin:5px 0 0;">
                                            Email này được gửi tự động, vui lòng không trả lời. Nếu bạn cần hỗ trợ, vui
                                            lòng liên hệ
                                            <a href="mailto:Longtech@gmail.com"
                                                style="color:#E27447; text-decoration:none; font-weight: 500;">
                                                Longtech@gmail.com
                                            </a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
