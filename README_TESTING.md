# 🧪 Hướng Dẫn Test API - Hệ Thống Quản Lý Giáo Dục

## 📁 Files Có Sẵn

1. **`API_DOCUMENTATION.md`** - Tài liệu API chi tiết với tất cả endpoints
2. **`test_data.json`** - Dữ liệu test mẫu và mẫu request/response
3. **`README_TESTING.md`** - File này, hướng dẫn sử dụng

## 🚀 Bắt Đầu Test

### Bước 1: Chuẩn Bị

-   Đảm bảo server Laravel đang chạy tại `http://localhost:8000`
-   Cài đặt Postman hoặc công cụ test API khác
-   Import dữ liệu test từ `test_data.json`

### Bước 2: Test Flow Cơ Bản

1. **Đăng nhập** để lấy JWT token
2. **Sử dụng token** để test các API khác
3. **Kiểm tra response** theo mẫu trong documentation

## 🛠️ Công Cụ Test

### 1. Postman (Khuyến nghị)

-   Import collection từ `test_data.json`
-   Sử dụng environment variables cho `base_url` và `token`
-   Test từng endpoint theo thứ tự

### 2. cURL

```bash
# Đăng nhập
curl -X POST http://localhost:8000/api/v1/login/student \
  -H "Content-Type: application/json" \
  -d '{"username": "SV001", "password": "password123"}'

# Sử dụng token
curl -X GET http://localhost:8000/api/v1/students \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 3. JavaScript/Fetch

```javascript
// Xem mẫu trong test_data.json phần "test_api_requests"
```

## 📊 Dữ Liệu Test Có Sẵn

### Users

-   **Sinh viên**: SV001, SV002, SV003 (password: password123)
-   **Giảng viên**: GV001, GV002, GV003 (password: password123)

### Departments

-   Trường → Khoa → Bộ môn (hierarchy)

### Classes

-   WEB101, WEB201, MOB101, MOB201, ECO101

### Notifications

-   Gửi thông báo đơn lẻ, hàng loạt, lên lịch
-   Hỗ trợ đa kênh: email, push, SMS, in-app

## 🔍 Test Cases

### 1. Authentication Flow

-   [ ] Đăng nhập sinh viên (`/v1/login/student`)
-   [ ] Đăng nhập giảng viên (`/v1/login/lecturer`)
-   [ ] Refresh token (`/v1/refresh`)
-   [ ] Lấy thông tin user (`/v1/me`)
-   [ ] Đăng xuất (`/v1/logout`)

### 2. CRUD Operations

-   [ ] Tạo sinh viên mới (`/v1/students`)
-   [ ] Tạo giảng viên mới (`/v1/lecturers`)
-   [ ] Tạo lớp học mới (`/v1/classes`)
-   [ ] Quản lý khoa/phòng ban (`/v1/departments`)

### 3. Data Retrieval

-   [ ] Lấy danh sách sinh viên (`/v1/students`)
-   [ ] Lấy danh sách giảng viên (`/v1/lecturers`)
-   [ ] Lấy danh sách lớp học (`/v1/classes`)
-   [ ] Lấy cây cấu trúc khoa (`/v1/departments/tree`)

### 4. User Profile Management

-   [ ] Xem thông tin cá nhân sinh viên (`/v1/student/profile`)
-   [ ] Cập nhật thông tin cá nhân sinh viên (`/v1/student/profile`)
-   [ ] Xem thông tin cá nhân giảng viên (`/v1/lecturer/profile`)
-   [ ] Cập nhật thông tin cá nhân giảng viên (`/v1/lecturer/profile`)

### 5. Notifications

-   [ ] Gửi thông báo (`/v1/notifications/send`)
-   [ ] Gửi thông báo hàng loạt (`/v1/notifications/send-bulk`)
-   [ ] Lên lịch gửi thông báo (`/v1/notifications/schedule`)
-   [ ] Lấy thông báo của user (`/v1/internal/notifications/user`)
-   [ ] Đánh dấu đã đọc (`/v1/internal/notifications/mark-read`)

## ⚠️ Lưu Ý Quan Trọng

1. **Token JWT**: Có thời hạn 24 giờ
2. **Validation**: Tất cả input đều được validate
3. **Error Handling**: Kiểm tra HTTP status codes
4. **Rate Limiting**: Không spam requests
5. **Phân quyền**: Admin mới có thể quản lý toàn bộ hệ thống

## 🔐 Phân Quyền API

### Public APIs (Không cần authentication)

-   `POST /v1/login` - Đăng nhập
-   `POST /v1/login/student` - Đăng nhập sinh viên
-   `POST /v1/login/lecturer` - Đăng nhập giảng viên
-   `POST /v1/notifications/send` - Gửi thông báo
-   `POST /v1/notifications/send-bulk` - Gửi thông báo hàng loạt
-   `POST /v1/notifications/schedule` - Lên lịch thông báo

### Protected APIs (Cần JWT token)

-   `GET /v1/me` - Thông tin user
-   `POST /v1/refresh` - Làm mới token
-   `POST /v1/logout` - Đăng xuất
-   `GET /v1/student/profile` - Profile sinh viên
-   `PUT /v1/student/profile` - Cập nhật profile sinh viên
-   `GET /v1/lecturer/profile` - Profile giảng viên
-   `PUT /v1/lecturer/profile` - Cập nhật profile giảng viên

### Admin Only APIs (Cần JWT + admin role)

-   `GET /v1/students` - Quản lý sinh viên
-   `POST /v1/students` - Tạo sinh viên
-   `GET /v1/lecturers` - Quản lý giảng viên
-   `POST /v1/lecturers` - Tạo giảng viên
-   `GET /v1/classes` - Quản lý lớp học
-   `POST /v1/classes` - Tạo lớp học
-   `GET /v1/departments` - Quản lý khoa/phòng ban

## 🐛 Troubleshooting

### Lỗi Thường Gặp

-   **401 Unauthorized**: Token hết hạn hoặc không hợp lệ
-   **403 Forbidden**: Không có quyền truy cập (cần admin role)
-   **422 Validation Error**: Dữ liệu input không đúng format
-   **404 Not Found**: URL hoặc ID không tồn tại

### Giải Pháp

-   Gọi API refresh token
-   Kiểm tra quyền của user
-   Kiểm tra format dữ liệu
-   Xác nhận URL và parameters

## 📞 Hỗ Trợ

-   Xem `API_DOCUMENTATION.md` để biết chi tiết
-   Kiểm tra logs server để debug
-   Sử dụng dữ liệu mẫu từ `test_data.json`

---

**Happy Testing! 🎯**
