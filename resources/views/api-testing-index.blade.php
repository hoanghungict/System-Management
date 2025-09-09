<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Testing Tools - System Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 800px;
            width: 90%;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: #666;
            font-size: 1.1em;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .tool-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .tool-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .tool-card:hover::before {
            left: 100%;
        }

        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            border-color: #667eea;
        }

        .tool-card.common {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .tool-card.admin {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
        }

        .tool-card.student {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
        }

        .tool-card.lecturer {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }

        .tool-card h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .tool-card p {
            margin-bottom: 20px;
            opacity: 0.9;
            line-height: 1.5;
        }

        .tool-card a {
            display: inline-block;
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.2);
            color: inherit;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .tool-card a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .tool-card.lecturer a {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
        }

        .tool-card.lecturer a:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .features {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .features h3 {
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }

        .features ul {
            list-style: none;
            padding: 0;
        }

        .features li {
            padding: 8px 0;
            color: #666;
            position: relative;
            padding-left: 25px;
        }

        .features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .tools-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 API Testing Tools</h1>
            <p>Hệ thống quản lý nhiệm vụ và lịch trình</p>
        </div>

        <div class="tools-grid">
            <div class="tool-card common">
                <h3>🌐 Common APIs</h3>
                <p>API chung cho tất cả người dùng đã đăng nhập</p>
                <a href="/api-testing/common">Mở Tool</a>
            </div>

            <div class="tool-card admin">
                <h3>👨‍💼 Admin APIs</h3>
                <p>API dành cho giảng viên có quyền admin (is_admin = 1)</p>
                <a href="/api-testing/admin">Mở Tool</a>
            </div>

            <div class="tool-card student">
                <h3>👨‍🎓 Student APIs</h3>
                <p>API dành cho sinh viên</p>
                <a href="/api-testing/student">Mở Tool</a>
            </div>

            <div class="tool-card lecturer">
                <h3>👨‍🏫 Lecturer APIs</h3>
                <p>API dành cho giảng viên thường (is_admin = 0)</p>
                <a href="/api-testing/lecturer">Mở Tool</a>
            </div>
        </div>

        <div class="features">
            <h3>✨ Tính năng của các tool:</h3>
            <ul>
                <li>Đăng nhập tự động với API thực của hệ thống</li>
                <li>Giao diện giống Postman với form có sẵn</li>
                <li>Có thể chỉnh sửa endpoint và body request</li>
                <li>Hiển thị response chi tiết với status code</li>
                <li>Xử lý lỗi JSON parsing một cách an toàn</li>
                <li>Phân quyền theo role người dùng</li>
                <li>Lưu trữ token JWT tự động</li>
            </ul>
        </div>
    </div>
</body>
</html>

