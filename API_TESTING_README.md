# 🔧 API Testing Tools - Hướng dẫn sử dụng

## 📋 Tổng quan

Hệ thống cung cấp 4 công cụ testing API riêng biệt cho từng loại người dùng:

### 🌐 Common APIs (`/api-testing/common`)
- **Mục đích**: API chung cho tất cả người dùng đã đăng nhập
- **Màu sắc**: Xanh dương
- **Tính năng**: 
  - Quản lý nhiệm vụ cơ bản
  - Xem lịch trình
  - Cache operations
  - Email operations

### 👨‍💼 Admin APIs (`/api-testing/admin`)
- **Mục đích**: API dành cho giảng viên có quyền admin (is_admin = 1)
- **Màu sắc**: Đỏ
- **Tính năng**:
  - Quản lý nhiệm vụ admin
  - Monitoring và báo cáo
  - Quản lý hệ thống
  - Tất cả tính năng của Common APIs

### 👨‍🎓 Student APIs (`/api-testing/student`)
- **Mục đích**: API dành cho sinh viên
- **Màu sắc**: Xanh lá
- **Tính năng**:
  - Xem nhiệm vụ được giao
  - Cập nhật trạng thái nhiệm vụ
  - Xem lịch trình cá nhân
  - Cache operations cơ bản

### 👨‍🏫 Lecturer APIs (`/api-testing/lecturer`)
- **Mục đích**: API dành cho giảng viên thường (is_admin = 0)
- **Màu sắc**: Tím nhạt
- **Tính năng**:
  - Quản lý nhiệm vụ giảng viên
  - Giao nhiệm vụ cho sinh viên
  - Xem lịch trình giảng dạy
  - Cache operations

## 🚀 Cách truy cập

### Trang chủ
```
http://localhost/api-testing
```

### Các tool riêng lẻ
```
http://localhost/api-testing/common    # Common APIs
http://localhost/api-testing/admin     # Admin APIs  
http://localhost/api-testing/student   # Student APIs
http://localhost/api-testing/lecturer  # Lecturer APIs
```

## 🔐 Đăng nhập

### Thông tin tài khoản mẫu

#### 👨‍💼 Admin (Giảng viên admin)
```
Username: admin_lecturer
Password: password123
```

#### 👨‍🎓 Student (Sinh viên)
```
Username: student1
Password: password123
```

#### 👨‍🏫 Lecturer (Giảng viên thường)
```
Username: lecturer1
Password: password123
```

## 🛠️ Cách sử dụng

### 1. Truy cập tool
- Mở trình duyệt và truy cập `http://localhost/api-testing`
- Chọn tool phù hợp với role của bạn

### 2. Đăng nhập
- Nhập username và password
- Hệ thống sẽ tự động kiểm tra role và chuyển hướng
- Token JWT sẽ được lưu tự động

### 3. Test API
- Chọn endpoint từ danh sách
- Chỉnh sửa URL nếu cần
- Điền body request (nếu có)
- Click "Send Request"
- Xem response và status code

### 4. Xử lý lỗi
- Nếu gặp lỗi "Unexpected end of JSON input", hệ thống sẽ hiển thị response text gốc
- Kiểm tra server logs nếu cần

## 📊 API Endpoints

### Common APIs
- `GET /api/v1/tasks` - Lấy danh sách nhiệm vụ
- `GET /api/v1/tasks/{id}` - Lấy chi tiết nhiệm vụ
- `GET /api/v1/calendar` - Lấy lịch trình
- `GET /api/v1/cache/status` - Kiểm tra trạng thái cache
- `POST /api/v1/cache/clear` - Xóa cache

### Admin APIs
- `GET /api/v1/admin/tasks` - Quản lý nhiệm vụ admin
- `POST /api/v1/admin/tasks` - Tạo nhiệm vụ mới
- `GET /api/v1/monitoring/status` - Trạng thái hệ thống
- `GET /api/v1/monitoring/queue` - Trạng thái queue

### Student APIs
- `GET /api/v1/student/tasks` - Nhiệm vụ của sinh viên
- `PATCH /api/v1/student/tasks/{id}/status` - Cập nhật trạng thái
- `GET /api/v1/student/calendar` - Lịch trình sinh viên

### Lecturer APIs
- `GET /api/v1/lecturer/tasks` - Nhiệm vụ giảng viên
- `POST /api/v1/lecturer/tasks` - Tạo nhiệm vụ
- `GET /api/v1/lecturer/calendar` - Lịch trình giảng dạy

## 🔧 Troubleshooting

### Lỗi thường gặp

#### 1. "Server trả về response không phải JSON"
- **Nguyên nhân**: Server trả về HTML error page thay vì JSON
- **Giải pháp**: Kiểm tra server logs, đảm bảo API endpoint đúng

#### 2. "Unauthorized" hoặc "Token expired"
- **Nguyên nhân**: Token JWT hết hạn hoặc không hợp lệ
- **Giải pháp**: Đăng nhập lại

#### 3. "Role không phù hợp"
- **Nguyên nhân**: Tài khoản không có quyền truy cập API
- **Giải pháp**: Sử dụng tài khoản có role phù hợp

### Kiểm tra hệ thống

#### Kiểm tra Docker containers
```bash
docker-compose ps
```

#### Xem logs
```bash
docker-compose logs backend
docker-compose logs queue-worker
```

#### Restart services
```bash
docker-compose restart backend
docker-compose restart queue-worker
```

## 📝 Ghi chú

- Tất cả API đều sử dụng JWT authentication
- Response được format JSON
- Các tool có xử lý lỗi robust
- Giao diện responsive, hỗ trợ mobile
- Cache được sử dụng để tối ưu performance

## 🔗 Liên kết hữu ích

- [Laravel Documentation](https://laravel.com/docs)
- [JWT Authentication](https://jwt.io/)
- [API Testing Best Practices](https://restfulapi.net/testing-rest-apis/)
- [Docker Documentation](https://docs.docker.com/)

---

**Lưu ý**: Đảm bảo Laragon MySQL đang chạy trên port 3306 và database `system_services` đã được tạo từ file `db.sql`.
