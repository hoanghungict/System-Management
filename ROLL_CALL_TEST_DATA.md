# Roll Call API - Test Data Examples

## 1. Login để lấy JWT Token

**POST** `/api/v1/login`

```json
{
    "username": "admin",
    "password": "password"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

## 2. Tạo buổi điểm danh mới

**POST** `/api/v1/roll-calls`

```json
{
    "class_id": 1,
    "title": "Điểm danh lớp CNTT01 - 17/09/2025",
    "description": "Buổi điểm danh sáng thứ 3",
    "date": "2025-09-17T08:00:00",
    "created_by": 1
}
```

## 3. Cập nhật trạng thái điểm danh (1 sinh viên)

**PUT** `/api/v1/roll-calls/1/status`

```json
{
    "student_id": 1,
    "status": "Có Mặt",
    "note": "Có mặt đúng giờ"
}
```

## 4. Cập nhật trạng thái điểm danh (hàng loạt)

**PUT** `/api/v1/roll-calls/1/bulk-status`

```json
{
    "student_statuses": [
        {
            "student_id": 1,
            "status": "Có Mặt",
            "note": "Có mặt đúng giờ"
        },
        {
            "student_id": 2,
            "status": "Muộn",
            "note": "Muộn 5 phút"
        },
        {
            "student_id": 3,
            "status": "Vắng Mặt",
            "note": "Vắng không phép"
        },
        {
            "student_id": 4,
            "status": "Có Phép",
            "note": "Có giấy phép"
        }
    ]
}
```

## 5. Các trạng thái điểm danh hợp lệ

-   `"Có Mặt"` - Có mặt
-   `"Vắng Mặt"` - Vắng mặt
-   `"Muộn"` - Muộn
-   `"Có Phép"` - Có phép

## 6. Test Cases

### Test Case 1: Tạo buổi điểm danh thành công

1. Login để lấy JWT token
2. Gọi API tạo buổi điểm danh
3. Kiểm tra response có `success: true`
4. Kiểm tra data trả về có đầy đủ thông tin

### Test Case 2: Cập nhật trạng thái hàng loạt

1. Tạo buổi điểm danh trước
2. Gọi API cập nhật hàng loạt với 4 sinh viên
3. Kiểm tra response thành công
4. Gọi API chi tiết để xem kết quả

### Test Case 3: Validation errors

1. Gửi request thiếu `class_id` → Kiểm tra error message
2. Gửi status không hợp lệ → Kiểm tra validation
3. Gửi student_id không tồn tại → Kiểm tra error

### Test Case 4: Authorization

1. Gọi API không có JWT token → Kiểm tra 401
2. Gọi API với token hết hạn → Kiểm tra 401
3. Gọi API với user không phải lecturer → Kiểm tra 403

## 7. Headers cần thiết

### Cho tất cả API (trừ login):

```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

### Chỉ cho login:

```
Content-Type: application/json
```

## 8. Response Format

### Success Response:

```json
{
  "success": true,
  "message": "Thông báo thành công",
  "data": {...}
}
```

### Error Response:

```json
{
    "success": false,
    "message": "Thông báo lỗi",
    "error": "Chi tiết lỗi (optional)"
}
```

### Validation Error Response:

```json
{
    "success": false,
    "message": "Dữ liệu không hợp lệ",
    "errors": {
        "class_id": ["Vui lòng chọn lớp học."],
        "title": ["Vui lòng nhập tiêu đề buổi điểm danh."]
    }
}
```

## 9. Environment Variables cho Postman

```
base_url: http://your-domain.com/api/v1
jwt_token: (sẽ được set sau khi login)
```

## 10. Test Flow

1. **Setup**: Login để lấy JWT token
2. **Create**: Tạo buổi điểm danh mới
3. **Read**: Lấy danh sách và chi tiết buổi điểm danh
4. **Update**: Cập nhật trạng thái sinh viên (đơn lẻ + hàng loạt)
5. **Complete**: Hoàn thành buổi điểm danh
6. **Statistics**: Xem thống kê điểm danh
7. **Cleanup**: Hủy buổi điểm danh (nếu cần)
