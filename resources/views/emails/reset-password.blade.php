<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .token-box { background: white; border: 2px dashed #4F46E5; padding: 20px; margin: 20px 0; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 2px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Đổi mật khẩu</h1>
        </div>
        <div class="content">
            <p>Xin chào,</p>
            <p>Đây là mail tự động được gửi khi có người dùng yêu cầu thay đổi hoặc quên mật khẩu.</p>
            <p>Token thay đổi mật khẩu của bạn là:</p>
            <div class="token-box">{{ $token }}</div>
            <p>Token sẽ hết hạn sau 60 phút.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} SQL Learning Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>