<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Module API Documentation</title>
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
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            font-size: 1.2rem;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        .badge {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 5px;
        }

        .nav-tabs {
            display: flex;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 10px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .nav-tab {
            flex: 1;
            padding: 15px 20px;
            background: transparent;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.3s ease;
            white-space: nowrap;
            margin: 0 5px;
        }

        .nav-tab.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .nav-tab:hover:not(.active) {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .content-section {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .content-section.active {
            display: block;
        }

        .section-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }

        .endpoint-group {
            margin-bottom: 40px;
        }

        .group-title {
            font-size: 1.5rem;
            color: #34495e;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }

        .endpoint {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .endpoint:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .method {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-right: 15px;
            min-width: 80px;
            text-align: center;
        }

        .method.get { background: #28a745; color: white; }
        .method.post { background: #007bff; color: white; }
        .method.put { background: #ffc107; color: #212529; }
        .method.patch { background: #fd7e14; color: white; }
        .method.delete { background: #dc3545; color: white; }

        .endpoint-url {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .endpoint-description {
            color: #7f8c8d;
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .endpoint-params {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }

        .params-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
        }

        .param {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .param:last-child {
            border-bottom: none;
        }

        .param-name {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #667eea;
        }

        .param-desc {
            color: #6c757d;
        }

        .example-section {
            background: #2c3e50;
            color: #ecf0f1;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }

        .example-title {
            color: #3498db;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 10px;
            transition: background 0.3s ease;
        }

        .copy-btn:hover {
            background: #5a6fd8;
        }

        .auth-info {
            background: linear-gradient(45deg, #e8f5e8, #f0f8f0);
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .auth-title {
            color: #155724;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .auth-content {
            color: #155724;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .nav-tab {
                margin: 5px 0;
            }
            
            .endpoint-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .method {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Task Module API Documentation</h1>
            <p>Hệ thống quản lý nhiệm vụ với phân quyền theo vai trò</p>
            <div>
                <span class="badge">JWT Authentication</span>
                <span class="badge">Role-based Access</span>
                <span class="badge">RESTful API</span>
                <span class="badge">Laravel Framework</span>
            </div>
        </div>

        <div class="auth-info">
            <div class="auth-title">🔐 Authentication Required</div>
            <div class="auth-content">
                <strong>Base URL:</strong> <code>/api/v1</code><br>
                <strong>Headers:</strong> <code>Authorization: Bearer {jwt_token}</code><br>
                <strong>Content-Type:</strong> <code>application/json</code>
            </div>
        </div>

        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showSection('common')">🔓 Common</button>
            <button class="nav-tab" onclick="showSection('lecturer')">👨‍🏫 Lecturer</button>
            <button class="nav-tab" onclick="showSection('student')">👨‍🎓 Student</button>
            <button class="nav-tab" onclick="showSection('admin')">👑 Admin</button>
            <button class="nav-tab" onclick="showSection('examples')">📝 Examples</button>
        </div>

        <!-- Common Routes -->
        <div id="common" class="content-section active">
            <h2 class="section-title">🔓 Common Routes (Tất cả người dùng đã đăng nhập)</h2>
            
            <div class="endpoint-group">
                <h3 class="group-title">📋 Tasks Management</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/my-tasks</span>
                    </div>
                    <div class="endpoint-description">Lấy danh sách task của user hiện tại</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/my-assigned-tasks</span>
                    </div>
                    <div class="endpoint-description">Lấy danh sách task được giao cho user</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/statistics/my</span>
                    </div>
                    <div class="endpoint-description">Thống kê task cá nhân</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/{task}</span>
                    </div>
                    <div class="endpoint-description">Xem chi tiết task</div>
                    <div class="endpoint-params">
                        <div class="params-title">Parameters:</div>
                        <div class="param">
                            <span class="param-name">task</span>
                            <span class="param-desc">ID của task</span>
                        </div>
                    </div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/tasks</span>
                    </div>
                    <div class="endpoint-description">Tạo task mới</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method delete">DELETE</span>
                        <span class="endpoint-url">/api/v1/tasks/{task}</span>
                    </div>
                    <div class="endpoint-description">Xóa task</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method patch">PATCH</span>
                        <span class="endpoint-url">/api/v1/tasks/{task}/status</span>
                    </div>
                    <div class="endpoint-description">Cập nhật trạng thái task</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/tasks/{task}/files</span>
                    </div>
                    <div class="endpoint-description">Upload file cho task</div>
                </div>
            </div>

            <div class="endpoint-group">
                <h3 class="group-title">🔍 Data Lookup</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/departments</span>
                    </div>
                    <div class="endpoint-description">Lấy danh sách khoa</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/classes/by-department</span>
                    </div>
                    <div class="endpoint-description">Lấy lớp theo khoa</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/students/by-class</span>
                    </div>
                    <div class="endpoint-description">Lấy sinh viên theo lớp</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/lecturers</span>
                    </div>
                    <div class="endpoint-description">Lấy danh sách giảng viên</div>
                </div>
            </div>
        </div>

        <!-- Lecturer Routes -->
        <div id="lecturer" class="content-section">
            <h2 class="section-title">👨‍🏫 Lecturer Routes (Chỉ Giảng viên)</h2>
            
            <div class="endpoint-group">
                <h3 class="group-title">📚 Lecturer Tasks</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/lecturer-tasks</span>
                    </div>
                    <div class="endpoint-description">Danh sách task của giảng viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/lecturer-tasks/created</span>
                    </div>
                    <div class="endpoint-description">Task do giảng viên tạo</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/lecturer-tasks/assigned</span>
                    </div>
                    <div class="endpoint-description">Task được giao cho giảng viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/lecturer-tasks</span>
                    </div>
                    <div class="endpoint-description">Tạo task mới</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method put">PUT</span>
                        <span class="endpoint-url">/api/v1/lecturer-tasks/{task}</span>
                    </div>
                    <div class="endpoint-description">Cập nhật task</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method patch">PATCH</span>
                        <span class="endpoint-url">/api/v1/lecturer-tasks/{task}/assign</span>
                    </div>
                    <div class="endpoint-description">Giao task cho sinh viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/lecturer-tasks/recurring</span>
                    </div>
                    <div class="endpoint-description">Tạo task định kỳ</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/lecturer-tasks/generate-report</span>
                    </div>
                    <div class="endpoint-description">Tạo báo cáo</div>
                </div>
            </div>

            <div class="endpoint-group">
                <h3 class="group-title">📅 Lecturer Calendar</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/lecturer-calendar/events</span>
                    </div>
                    <div class="endpoint-description">Lấy sự kiện của giảng viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/lecturer-calendar/events</span>
                    </div>
                    <div class="endpoint-description">Tạo sự kiện mới</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method put">PUT</span>
                        <span class="endpoint-url">/api/v1/lecturer-calendar/events/{event}</span>
                    </div>
                    <div class="endpoint-description">Cập nhật sự kiện</div>
                </div>
            </div>

            <div class="endpoint-group">
                <h3 class="group-title">👤 Lecturer Profile & Classes</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/lecturer-profile</span>
                    </div>
                    <div class="endpoint-description">Xem profile giảng viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/lecturer-classes</span>
                    </div>
                    <div class="endpoint-description">Lấy lớp của giảng viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/lecturer-classes/{class}/students</span>
                    </div>
                    <div class="endpoint-description">Lấy sinh viên trong lớp</div>
                </div>
            </div>
        </div>

        <!-- Student Routes -->
        <div id="student" class="content-section">
            <h2 class="section-title">👨‍🎓 Student Routes (Chỉ Sinh viên)</h2>
            
            <div class="endpoint-group">
                <h3 class="group-title">📝 Student Tasks</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/student-tasks</span>
                    </div>
                    <div class="endpoint-description">Danh sách task của sinh viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/student-tasks/pending</span>
                    </div>
                    <div class="endpoint-description">Task đang chờ xử lý</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/student-tasks/submitted</span>
                    </div>
                    <div class="endpoint-description">Task đã nộp</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/student-tasks/overdue</span>
                    </div>
                    <div class="endpoint-description">Task quá hạn</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/student-tasks/{task}/submit</span>
                    </div>
                    <div class="endpoint-description">Nộp bài task</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method put">PUT</span>
                        <span class="endpoint-url">/api/v1/student-tasks/{task}/submission</span>
                    </div>
                    <div class="endpoint-description">Cập nhật bài nộp</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/student-tasks/{task}/upload-file</span>
                    </div>
                    <div class="endpoint-description">Upload file bài nộp</div>
                </div>
            </div>

            <div class="endpoint-group">
                <h3 class="group-title">📅 Student Calendar & Profile</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/student-calendar/events</span>
                    </div>
                    <div class="endpoint-description">Sự kiện của sinh viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/student-profile</span>
                    </div>
                    <div class="endpoint-description">Xem profile sinh viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/student-class</span>
                    </div>
                    <div class="endpoint-description">Thông tin lớp của sinh viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/student-class/classmates</span>
                    </div>
                    <div class="endpoint-description">Danh sách bạn cùng lớp</div>
                </div>
            </div>
        </div>

        <!-- Admin Routes -->
        <div id="admin" class="content-section">
            <h2 class="section-title">👑 Admin Routes (Chỉ Quản trị viên)</h2>
            
            <div class="endpoint-group">
                <h3 class="group-title">🔧 Admin Tasks Management</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/admin/all</span>
                    </div>
                    <div class="endpoint-description">Tất cả task (admin view)</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/tasks/statistics/overview</span>
                    </div>
                    <div class="endpoint-description">Thống kê tổng quan</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method delete">DELETE</span>
                        <span class="endpoint-url">/api/v1/tasks/{task}/force</span>
                    </div>
                    <div class="endpoint-description">Xóa vĩnh viễn task</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/admin-tasks/assign</span>
                    </div>
                    <div class="endpoint-description">Giao task cho giảng viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/admin-tasks/{taskId}/restore</span>
                    </div>
                    <div class="endpoint-description">Khôi phục task</div>
                </div>
            </div>

            <div class="endpoint-group">
                <h3 class="group-title">📊 System Monitoring</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/monitoring/metrics</span>
                    </div>
                    <div class="endpoint-description">Metrics hệ thống</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/monitoring/health</span>
                    </div>
                    <div class="endpoint-description">Health check</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/monitoring/dashboard</span>
                    </div>
                    <div class="endpoint-description">Dashboard dữ liệu</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/monitoring/logs</span>
                    </div>
                    <div class="endpoint-description">Logs hệ thống</div>
                </div>
            </div>

            <div class="endpoint-group">
                <h3 class="group-title">🗄️ Cache Management</h3>
                
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/api/v1/cache/health</span>
                    </div>
                    <div class="endpoint-description">Health check cache</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/cache/invalidate/student</span>
                    </div>
                    <div class="endpoint-description">Xóa cache sinh viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/cache/invalidate/lecturer</span>
                    </div>
                    <div class="endpoint-description">Xóa cache giảng viên</div>
                </div>

                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/api/v1/cache/invalidate/all</span>
                    </div>
                    <div class="endpoint-description">Xóa tất cả cache</div>
                </div>
            </div>
        </div>

        <!-- Examples -->
        <div id="examples" class="content-section">
            <h2 class="section-title">📝 Request/Response Examples</h2>
            
            <div class="endpoint-group">
                <h3 class="group-title">Tạo Task mới (Lecturer)</h3>
                <div class="example-section">
                    <div class="example-title">Request:</div>
                    <div>POST /api/v1/lecturer-tasks</div>
                    <div>Authorization: Bearer {jwt_token}</div>
                    <div>Content-Type: application/json</div>
                    <br>
                    <div>{</div>
                    <div>&nbsp;&nbsp;"title": "Bài tập Laravel",</div>
                    <div>&nbsp;&nbsp;"description": "Làm bài tập về Laravel Framework",</div>
                    <div>&nbsp;&nbsp;"due_date": "2024-12-31 23:59:59",</div>
                    <div>&nbsp;&nbsp;"priority": "high",</div>
                    <div>&nbsp;&nbsp;"class_id": 1</div>
                    <div>}</div>
                </div>
                
                <div class="example-section">
                    <div class="example-title">Response Success:</div>
                    <div>{</div>
                    <div>&nbsp;&nbsp;"success": true,</div>
                    <div>&nbsp;&nbsp;"message": "Task created successfully",</div>
                    <div>&nbsp;&nbsp;"data": {</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"id": 1,</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"title": "Bài tập Laravel",</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"description": "Làm bài tập về Laravel Framework",</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"due_date": "2024-12-31 23:59:59",</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"priority": "high",</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"status": "pending",</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"created_at": "2024-01-15T10:30:00Z"</div>
                    <div>&nbsp;&nbsp;}</div>
                    <div>}</div>
                </div>
            </div>

            <div class="endpoint-group">
                <h3 class="group-title">Nộp bài Task (Student)</h3>
                <div class="example-section">
                    <div class="example-title">Request:</div>
                    <div>POST /api/v1/student-tasks/1/submit</div>
                    <div>Authorization: Bearer {jwt_token}</div>
                    <div>Content-Type: application/json</div>
                    <br>
                    <div>{</div>
                    <div>&nbsp;&nbsp;"submission_content": "Đây là bài làm của tôi",</div>
                    <div>&nbsp;&nbsp;"files": [1, 2, 3]</div>
                    <div>}</div>
                </div>
                
                <div class="example-section">
                    <div class="example-title">Response Success:</div>
                    <div>{</div>
                    <div>&nbsp;&nbsp;"success": true,</div>
                    <div>&nbsp;&nbsp;"message": "Task submitted successfully",</div>
                    <div>&nbsp;&nbsp;"data": {</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"task_id": 1,</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"submission_content": "Đây là bài làm của tôi",</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"submitted_at": "2024-01-15T14:30:00Z",</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"status": "submitted"</div>
                    <div>&nbsp;&nbsp;}</div>
                    <div>}</div>
                </div>
            </div>

            <div class="endpoint-group">
                <h3 class="group-title">Error Responses</h3>
                <div class="example-section">
                    <div class="example-title">Authentication Error:</div>
                    <div>{</div>
                    <div>&nbsp;&nbsp;"success": false,</div>
                    <div>&nbsp;&nbsp;"message": "Unauthorized",</div>
                    <div>&nbsp;&nbsp;"error": "Token not provided or invalid"</div>
                    <div>}</div>
                </div>
                
                <div class="example-section">
                    <div class="example-title">Validation Error:</div>
                    <div>{</div>
                    <div>&nbsp;&nbsp;"success": false,</div>
                    <div>&nbsp;&nbsp;"message": "Validation failed",</div>
                    <div>&nbsp;&nbsp;"errors": {</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"title": ["The title field is required."],</div>
                    <div>&nbsp;&nbsp;&nbsp;&nbsp;"due_date": ["The due date must be a date after today."]</div>
                    <div>&nbsp;&nbsp;}</div>
                    <div>}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionId).classList.add('active');

            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Copy to clipboard functionality
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Copied to clipboard!');
            });
        }

        // Add copy buttons to example sections
        document.addEventListener('DOMContentLoaded', function() {
            const exampleSections = document.querySelectorAll('.example-section');
            exampleSections.forEach(section => {
                const copyBtn = document.createElement('button');
                copyBtn.className = 'copy-btn';
                copyBtn.textContent = 'Copy';
                copyBtn.onclick = function() {
                    const text = section.textContent.replace('Copy', '').trim();
                    copyToClipboard(text);
                };
                section.appendChild(copyBtn);
            });
        });
    </script>
</body>
</html>
