# Hướng Dẫn Cài Đặt và Sử Dụng Swagger Documentation

## 📋 Tổng Quan

Dự án đã được cài đặt thư viện `l5-swagger` để tạo API documentation tự động. File Swagger đã được tạo sẵn với đầy đủ thông tin về tất cả các API endpoints.

## 🚀 Cài Đặt

### 1. Cài đặt thư viện (Đã hoàn thành)

```bash
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### 2. Cấu hình

File cấu hình đã được tạo tại `config/l5-swagger.php`. Các cài đặt mặc định:

```php
'paths' => [
    'docs' => base_path('resources/docs'),
    'views' => base_path('resources/views/vendor/l5-swagger'),
    'base' => env('L5_SWAGGER_BASE_PATH', null),
    'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
    'excludes' => [],
],
```

## 📁 Cấu Trúc Files

```
project/
├── swagger.yaml                    # File OpenAPI specification chính
├── swagger-part2.yaml             # Phần 2 của API endpoints
├── swagger-schemas.yaml           # Schemas và models
├── config/l5-swagger.php          # Cấu hình Swagger
└── resources/
    └── views/vendor/l5-swagger/   # Views của Swagger UI
```

## 🔧 Cách Sử Dụng

### 1. Tạo Documentation từ Annotations

Để tạo documentation từ code annotations, thêm annotations vào Controllers:

```php
/**
 * @OA\Post(
 *     path="/api/v1/login",
 *     summary="Đăng nhập tự động xác định loại user",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="username", type="string", example="SV001"),
 *             @OA\Property(property="password", type="string", example="password123"),
 *             @OA\Property(property="user_type", type="string", enum={"student", "lecturer"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Đăng nhập thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Đăng nhập thành công")
 *         )
 *     )
 * )
 */
public function login(LoginRequest $request): JsonResponse
{
    // Controller logic
}
```

### 2. Sử dụng File YAML (Khuyến nghị)

Copy nội dung từ các file YAML đã tạo vào file `resources/docs/api-docs.yaml`:

```bash
# Tạo thư mục docs nếu chưa có
mkdir -p resources/docs

# Copy file swagger chính
cp swagger.yaml resources/docs/api-docs.yaml

# Hoặc merge tất cả files
cat swagger.yaml swagger-part2.yaml swagger-schemas.yaml > resources/docs/api-docs.yaml
```

### 3. Generate Documentation

```bash
# Generate documentation từ annotations
php artisan l5-swagger:generate

# Hoặc nếu sử dụng file YAML
php artisan l5-swagger:generate --yaml
```

### 4. Truy cập Swagger UI

Sau khi generate, truy cập:

-   **URL**: `http://localhost:8000/api/documentation`
-   **JSON**: `http://localhost:8000/docs/api-docs.json`

## 📖 Nội Dung Documentation

### 🔐 Authentication APIs

-   `POST /v1/login` - Đăng nhập tự động xác định loại user
-   `POST /v1/login/student` - Đăng nhập sinh viên
-   `POST /v1/login/lecturer` - Đăng nhập giảng viên
-   `POST /v1/refresh` - Làm mới JWT token
-   `GET /v1/me` - Lấy thông tin user từ JWT token
-   `POST /v1/logout` - Đăng xuất

### 👥 Student Management APIs

-   `GET /v1/students` - Lấy danh sách sinh viên (Admin only)
-   `POST /v1/students` - Tạo sinh viên mới (Admin only)
-   `GET /v1/students/{id}` - Lấy thông tin sinh viên theo ID (Admin only)
-   `PUT /v1/students/{id}` - Cập nhật thông tin sinh viên (Admin only)
-   `DELETE /v1/students/{id}` - Xóa sinh viên (Admin only)
-   `GET /v1/student/profile` - Xem thông tin cá nhân (Student only)
-   `PUT /v1/student/profile` - Cập nhật thông tin cá nhân (Student only)
-   `GET /v1/student/class/{classId}` - Lấy danh sách sinh viên theo lớp

### 👨‍🏫 Lecturer Management APIs

-   `GET /v1/lecturers` - Lấy danh sách giảng viên (Admin only)
-   `POST /v1/lecturers` - Tạo giảng viên mới (Admin only)
-   `GET /v1/lecturers/{id}` - Lấy thông tin giảng viên theo ID (Admin only)
-   `PUT /v1/lecturers/{id}` - Cập nhật thông tin giảng viên (Admin only)
-   `DELETE /v1/lecturers/{id}` - Xóa giảng viên (Admin only)
-   `PATCH /v1/lecturers/{id}/admin-status` - Cập nhật quyền admin (Admin only)
-   `GET /v1/lecturer/profile` - Xem thông tin cá nhân (Lecturer only)
-   `PUT /v1/lecturer/profile` - Cập nhật thông tin cá nhân (Lecturer only)

### 🏫 Class Management APIs

-   `GET /v1/classes` - Lấy danh sách lớp học (Admin only)
-   `POST /v1/classes` - Tạo lớp học mới (Admin only)
-   `GET /v1/classes/{id}` - Lấy thông tin lớp học theo ID (Admin only)
-   `PUT /v1/classes/{id}` - Cập nhật thông tin lớp học (Admin only)
-   `DELETE /v1/classes/{id}` - Xóa lớp học (Admin only)
-   `GET /v1/classes/faculty/{facultyId}` - Lấy danh sách lớp theo khoa
-   `GET /v1/classes/lecturer/{lecturerId}` - Lấy danh sách lớp theo giảng viên

### 🏢 Department Management APIs

-   `GET /v1/departments` - Lấy danh sách khoa/phòng ban (Admin only)
-   `POST /v1/departments` - Tạo khoa/phòng ban mới (Admin only)
-   `GET /v1/departments/tree` - Lấy cấu trúc cây khoa/phòng ban (Admin only)
-   `GET /v1/departments/{id}` - Lấy thông tin khoa/phòng ban theo ID (Admin only)
-   `PUT /v1/departments/{id}` - Cập nhật thông tin khoa/phòng ban (Admin only)
-   `DELETE /v1/departments/{id}` - Xóa khoa/phòng ban (Admin only)

### 📋 Roll Call Management APIs

-   `GET /v1/roll-calls/classrooms` - Lấy danh sách lớp học để tạo điểm danh (Lecturer only)
-   `POST /v1/roll-calls` - Tạo buổi điểm danh mới (Lecturer only)
-   `GET /v1/roll-calls/class/{classId}` - Lấy danh sách buổi điểm danh theo lớp (Lecturer only)
-   `GET /v1/roll-calls/{id}` - Lấy chi tiết buổi điểm danh (Lecturer only)
-   `PUT /v1/roll-calls/{rollCallId}/status` - Cập nhật trạng thái điểm danh của 1 sinh viên (Lecturer only)
-   `PUT /v1/roll-calls/{rollCallId}/bulk-status` - Cập nhật trạng thái điểm danh hàng loạt (Lecturer only)
-   `PATCH /v1/roll-calls/{id}/complete` - Hoàn thành buổi điểm danh (Lecturer only)
-   `PATCH /v1/roll-calls/{id}/cancel` - Hủy buổi điểm danh (Lecturer only)
-   `GET /v1/roll-calls/statistics/class/{classId}` - Lấy thống kê điểm danh theo lớp (Lecturer only)
-   `GET /v1/roll-calls/students/class/{classId}` - Lấy danh sách sinh viên trong lớp để điểm danh (Lecturer only)

### 📧 Notification APIs

-   `POST /v1/notifications/send` - Gửi thông báo đơn lẻ
-   `POST /v1/notifications/send-bulk` - Gửi thông báo hàng loạt
-   `POST /v1/notifications/schedule` - Lên lịch gửi thông báo
-   `GET /v1/notifications/templates` - Lấy danh sách templates
-   `GET /v1/notifications/status/{id}` - Lấy trạng thái gửi thông báo
-   `GET /v1/internal/notifications/user` - Lấy thông báo của user (JWT required)
-   `POST /v1/internal/notifications/mark-read` - Đánh dấu thông báo đã đọc (JWT required)
-   `POST /v1/events/publish` - Publish Event lên Kafka

## 🔐 Authentication

Tất cả APIs (trừ login và public notification APIs) đều yêu cầu JWT token:

```http
Authorization: Bearer {JWT_TOKEN}
```

## 📊 Request/Response Examples

### Login Request

```json
{
    "username": "SV001",
    "password": "password123",
    "user_type": "student"
}
```

### Login Response

```json
{
    "data": {
        "id": 1,
        "full_name": "Nguyễn Văn A",
        "student_code": "SV001",
        "email": "nguyenvana@example.com",
        "department": "Công nghệ thông tin",
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    },
    "message": "Đăng nhập thành công"
}
```

### Create Student Request

```json
{
    "full_name": "Nguyễn Văn A",
    "birth_date": "2000-01-01",
    "gender": "male",
    "address": "Hà Nội",
    "email": "nguyenvana@example.com",
    "phone": "0123456789",
    "student_code": "SV001",
    "class_id": 1
}
```

## 🛠️ Troubleshooting

### 1. Lỗi "Class not found"

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### 2. Lỗi "File not found"

Kiểm tra đường dẫn file YAML:

```bash
ls -la resources/docs/
```

### 3. Lỗi "Permission denied"

```bash
chmod -R 755 resources/docs/
chmod -R 755 storage/
```

### 4. Lỗi "Swagger UI not loading"

```bash
php artisan l5-swagger:generate --force
```

## 📝 Customization

### 1. Thay đổi theme Swagger UI

Chỉnh sửa file `resources/views/vendor/l5-swagger/index.blade.php`

### 2. Thêm custom CSS

Thêm vào file `resources/views/vendor/l5-swagger/index.blade.php`:

```html
<style>
    .swagger-ui .topbar {
        display: none;
    }
    .swagger-ui .info .title {
        color: #your-color;
    }
</style>
```

### 3. Thêm custom JavaScript

```html
<script>
    // Custom JavaScript code
</script>
```

## 🚀 Production Deployment

### 1. Cấu hình Nginx

```nginx
location /api/documentation {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 2. Cấu hình Apache

```apache
RewriteRule ^api/documentation$ /index.php [L,QSA]
```

### 3. Environment Variables

```env
L5_SWAGGER_BASE_PATH=/api
L5_SWAGGER_UI_ASSETS_PATH=/vendor/swagger-api/swagger-ui/dist/
```

## 📚 Tài Liệu Tham Khảo

-   [OpenAPI Specification](https://swagger.io/specification/)
-   [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
-   [Swagger UI](https://swagger.io/tools/swagger-ui/)
-   [JWT Authentication](https://jwt.io/)

## 🤝 Contributing

1. Fork project
2. Tạo feature branch
3. Cập nhật documentation
4. Commit changes
5. Push to branch
6. Create Pull Request

## 📄 License

MIT License - Xem file LICENSE để biết thêm chi tiết.
