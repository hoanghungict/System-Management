# 🚀 Hướng Dẫn Sử Dụng Swagger Documentation

## ✅ Đã Hoàn Thành

Tôi đã tạo đầy đủ Swagger documentation cho dự án của bạn bao gồm:

### 📁 Files Đã Tạo:

1. **`swagger.yaml`** - File OpenAPI specification chính với tất cả API endpoints
2. **`swagger-part2.yaml`** - Phần 2 với các API endpoints còn lại
3. **`swagger-schemas.yaml`** - Tất cả schemas và models
4. **`resources/docs/api-docs.yaml`** - File YAML đã merge sẵn sàng sử dụng
5. **`SWAGGER_SETUP.md`** - Hướng dẫn cài đặt chi tiết

### 📋 API Documentation Bao Gồm:

#### 🔐 **Authentication APIs (6 endpoints)**

-   `POST /v1/login` - Đăng nhập tự động xác định loại user
-   `POST /v1/login/student` - Đăng nhập sinh viên
-   `POST /v1/login/lecturer` - Đăng nhập giảng viên
-   `POST /v1/refresh` - Làm mới JWT token
-   `GET /v1/me` - Lấy thông tin user từ JWT token
-   `POST /v1/logout` - Đăng xuất

#### 👥 **Student Management APIs (8 endpoints)**

-   `GET /v1/students` - Lấy danh sách sinh viên (Admin only)
-   `POST /v1/students` - Tạo sinh viên mới (Admin only)
-   `GET /v1/students/{id}` - Lấy thông tin sinh viên theo ID (Admin only)
-   `PUT /v1/students/{id}` - Cập nhật thông tin sinh viên (Admin only)
-   `DELETE /v1/students/{id}` - Xóa sinh viên (Admin only)
-   `GET /v1/student/profile` - Xem thông tin cá nhân (Student only)
-   `PUT /v1/student/profile` - Cập nhật thông tin cá nhân (Student only)
-   `GET /v1/student/class/{classId}` - Lấy danh sách sinh viên theo lớp

#### 👨‍🏫 **Lecturer Management APIs (8 endpoints)**

-   `GET /v1/lecturers` - Lấy danh sách giảng viên (Admin only)
-   `POST /v1/lecturers` - Tạo giảng viên mới (Admin only)
-   `GET /v1/lecturers/{id}` - Lấy thông tin giảng viên theo ID (Admin only)
-   `PUT /v1/lecturers/{id}` - Cập nhật thông tin giảng viên (Admin only)
-   `DELETE /v1/lecturers/{id}` - Xóa giảng viên (Admin only)
-   `PATCH /v1/lecturers/{id}/admin-status` - Cập nhật quyền admin (Admin only)
-   `GET /v1/lecturer/profile` - Xem thông tin cá nhân (Lecturer only)
-   `PUT /v1/lecturer/profile` - Cập nhật thông tin cá nhân (Lecturer only)

#### 🏫 **Class Management APIs (7 endpoints)**

-   `GET /v1/classes` - Lấy danh sách lớp học (Admin only)
-   `POST /v1/classes` - Tạo lớp học mới (Admin only)
-   `GET /v1/classes/{id}` - Lấy thông tin lớp học theo ID (Admin only)
-   `PUT /v1/classes/{id}` - Cập nhật thông tin lớp học (Admin only)
-   `DELETE /v1/classes/{id}` - Xóa lớp học (Admin only)
-   `GET /v1/classes/faculty/{facultyId}` - Lấy danh sách lớp theo khoa
-   `GET /v1/classes/lecturer/{lecturerId}` - Lấy danh sách lớp theo giảng viên

#### 🏢 **Department Management APIs (6 endpoints)**

-   `GET /v1/departments` - Lấy danh sách khoa/phòng ban (Admin only)
-   `POST /v1/departments` - Tạo khoa/phòng ban mới (Admin only)
-   `GET /v1/departments/tree` - Lấy cấu trúc cây khoa/phòng ban (Admin only)
-   `GET /v1/departments/{id}` - Lấy thông tin khoa/phòng ban theo ID (Admin only)
-   `PUT /v1/departments/{id}` - Cập nhật thông tin khoa/phòng ban (Admin only)
-   `DELETE /v1/departments/{id}` - Xóa khoa/phòng ban (Admin only)

#### 📋 **Roll Call Management APIs (10 endpoints)**

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

#### 📧 **Notification APIs (8 endpoints)**

-   `POST /v1/notifications/send` - Gửi thông báo đơn lẻ
-   `POST /v1/notifications/send-bulk` - Gửi thông báo hàng loạt
-   `POST /v1/notifications/schedule` - Lên lịch gửi thông báo
-   `GET /v1/notifications/templates` - Lấy danh sách templates
-   `GET /v1/notifications/status/{id}` - Lấy trạng thái gửi thông báo
-   `GET /v1/internal/notifications/user` - Lấy thông báo của user (JWT required)
-   `POST /v1/internal/notifications/mark-read` - Đánh dấu thông báo đã đọc (JWT required)
-   `POST /v1/events/publish` - Publish Event lên Kafka

## 🚀 Cách Sử Dụng

### 1. **Sử dụng File YAML (Khuyến nghị)**

File `resources/docs/api-docs.yaml` đã sẵn sàng sử dụng. Bạn có thể:

```bash
# Truy cập trực tiếp file YAML
http://localhost:8000/resources/docs/api-docs.yaml

# Hoặc sử dụng Swagger Editor online
# Copy nội dung file và paste vào https://editor.swagger.io/
```

### 2. **Tạo Swagger UI**

Nếu muốn tạo Swagger UI, hãy thử các cách sau:

#### Cách 1: Sửa quyền thư mục

```bash
sudo chown -R $USER:$USER resources/
chmod -R 755 resources/
php artisan l5-swagger:generate
```

#### Cách 2: Sử dụng Docker

```bash
# Tạo file docker-compose.yml
version: '3.8'
services:
  swagger-ui:
    image: swaggerapi/swagger-ui
    ports:
      - "8080:8080"
    volumes:
      - ./resources/docs/api-docs.yaml:/usr/share/nginx/html/api-docs.yaml
    environment:
      - SWAGGER_JSON=/usr/share/nginx/html/api-docs.yaml
```

#### Cách 3: Sử dụng Swagger Editor Online

1. Truy cập https://editor.swagger.io/
2. Copy nội dung file `resources/docs/api-docs.yaml`
3. Paste vào editor
4. Sử dụng "Try it out" để test APIs

### 3. **Tích hợp vào Frontend**

```javascript
// Sử dụng Swagger UI trong React/Vue/Angular
import SwaggerUI from "swagger-ui-react";
import "swagger-ui-react/swagger-ui.css";

function App() {
    return (
        <SwaggerUI
            url="/api/docs/api-docs.yaml"
            docExpansion="list"
            defaultModelsExpandDepth={3}
            defaultModelExpandDepth={3}
        />
    );
}
```

## 📊 Thông Tin Chi Tiết

### **Request/Response Examples**

Mỗi API đều có:

-   ✅ Request body examples
-   ✅ Response examples
-   ✅ Error response examples
-   ✅ Validation rules
-   ✅ Authentication requirements

### **Security**

-   ✅ JWT Bearer token authentication
-   ✅ Role-based access control (Admin, Lecturer, Student)
-   ✅ Public APIs cho notifications

### **Validation**

-   ✅ Tất cả request schemas với validation rules
-   ✅ Vietnamese error messages
-   ✅ Required/optional fields
-   ✅ Data types và constraints

## 🎯 Tính Năng Nổi Bật

1. **Đầy đủ 47+ API endpoints** với documentation chi tiết
2. **Request/Response schemas** hoàn chỉnh
3. **Authentication & Authorization** rõ ràng
4. **Validation rules** từ Laravel Request classes
5. **Vietnamese error messages**
6. **Examples** cho mọi API
7. **OpenAPI 3.0.3** standard
8. **Ready to use** - không cần cài đặt thêm

## 🔧 Troubleshooting

Nếu gặp lỗi permission, hãy thử:

```bash
# Cách 1: Sửa quyền
sudo chown -R $USER:$USER .
chmod -R 755 resources/

# Cách 2: Sử dụng file YAML trực tiếp
cp resources/docs/api-docs.yaml public/swagger.yaml
# Truy cập: http://localhost:8000/swagger.yaml

# Cách 3: Sử dụng Swagger Editor online
# Copy nội dung file và paste vào https://editor.swagger.io/
```

## 📝 Kết Luận

Swagger documentation đã được tạo hoàn chỉnh với:

-   ✅ **47+ API endpoints** được document đầy đủ
-   ✅ **Request/Response schemas** chi tiết
-   ✅ **Authentication & Authorization** rõ ràng
-   ✅ **Validation rules** từ Laravel
-   ✅ **Examples** cho mọi API
-   ✅ **Ready to use** ngay lập tức

Bạn có thể sử dụng file `resources/docs/api-docs.yaml` trực tiếp hoặc tạo Swagger UI theo hướng dẫn trên!
