# Hướng Dẫn Sử Dụng API - Hệ Thống Quản Lý Giáo Dục

## 📋 Tổng Quan

Hệ thống cung cấp các API để quản lý người dùng, xác thực và thông báo trong môi trường giáo dục. Hỗ trợ 3 loại người dùng chính:

-   **Student**: Sinh viên
-   **Lecturer**: Giảng viên
-   **Admin**: Quản trị viên

## 🔐 Xác Thực

### Base URL

```
http://localhost:8000/api
```

### Headers

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

## 📚 API Endpoints

### 1. Xác Thực Người Dùng

#### 1.1 Đăng Nhập Tự Động Xác Định Loại User

**Method:** `POST`  
**URL:** `/v1/login`  
**Headers:**

```
Content-Type: application/json
```

**Request Body:**

```json
{
    "username": "SV001",
    "password": "password123",
    "user_type": "student"
}
```

**user_type có thể là:**

-   `student`: Sinh viên
-   `lecturer`: Giảng viên

**Response Success (200):**

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

**Response Error (401):**

```json
{
    "message": "Thông tin đăng nhập không chính xác"
}
```

#### 1.2 Đăng Nhập Sinh Viên

**Method:** `POST`  
**URL:** `/v1/login/student`  
**Headers:**

```
Content-Type: application/json
```

**Request Body:**

```json
{
    "username": "SV001",
    "password": "password123"
}
```

**Response Success (200):**

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

**Response Error (401):**

```json
{
    "message": "Thông tin đăng nhập không chính xác"
}
```

#### 1.3 Đăng Nhập Giảng Viên

**Method:** `POST`  
**URL:** `/v1/login/lecturer`  
**Headers:**

```
Content-Type: application/json
```

**Request Body:**

```json
{
    "username": "GV001",
    "password": "password123"
}
```

**Response Success (200):**

```json
{
    "data": {
        "id": 1,
        "full_name": "Trần Thị B",
        "lecturer_code": "GV001",
        "email": "tranthib@example.com",
        "department": "Công nghệ thông tin",
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    },
    "message": "Đăng nhập thành công"
}
```

**Response Error (401):**

```json
{
    "message": "Thông tin đăng nhập không chính xác"
}
```

#### 1.4 Làm Mới Token

**Method:** `POST`  
**URL:** `/v1/refresh`  
**Headers:**

```
Authorization: Bearer {OLD_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "message": "Token được làm mới thành công",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

**Response Error (401):**

```json
{
    "message": "Token không hợp lệ"
}
```

#### 1.5 Lấy Thông Tin User

**Method:** `GET`  
**URL:** `/v1/me`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "message": "Thông tin user",
    "data": {
        "sub": 1,
        "email": "nguyenvana@example.com",
        "name": "Nguyễn Văn A",
        "iat": 1703123456,
        "exp": 1703209856
    }
}
```

**Response Error (401):**

```json
{
    "message": "Token không hợp lệ"
}
```

#### 1.6 Đăng Xuất

**Method:** `POST`  
**URL:** `/v1/logout`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "message": "Đăng xuất thành công"
}
```

**Response Error (401):**

```json
{
    "message": "Token không hợp lệ"
}
```

### 2. Quản Lý Lớp Học

#### 2.1 Lấy Danh Sách Lớp

**Method:** `GET`  
**URL:** `/v1/classes`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "class_name": "Lập trình Web",
            "class_code": "WEB101",
            "faculty": "Công nghệ thông tin",
            "lecturer": "Trần Thị B",
            "school_year": "2024-2025"
        }
    ]
}
```

**Response Error (403):**

```json
{
    "message": "Không có quyền truy cập"
}
```

#### 2.2 Tạo Lớp Mới

**Method:** `POST`  
**URL:** `/v1/classes`  
**Headers:**

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:**

```json
{
    "class_name": "Lập trình Mobile",
    "class_code": "MOB101",
    "faculty_id": 1,
    "lecturer_id": 1,
    "school_year": "2024-2025"
}
```

**Response Success (201):**

```json
{
    "status": true,
    "message": "Lớp học được tạo thành công",
    "data": {
        "id": 2,
        "class_name": "Lập trình Mobile",
        "class_code": "MOB101",
        "faculty_id": 1,
        "lecturer_id": 1,
        "school_year": "2024-2025",
        "created_at": "2024-12-19T10:00:00.000000Z",
        "updated_at": "2024-12-19T10:00:00.000000Z"
    }
}
```

**Response Error (422):**

```json
{
    "message": "Validation error",
    "errors": {
        "class_name": ["Tên lớp học không được để trống"],
        "class_code": ["Mã lớp học đã tồn tại"]
    }
}
```

#### 2.3 Lấy Thông Tin Lớp Theo ID

**Method:** `GET`  
**URL:** `/v1/classes/{id}`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": {
        "id": 1,
        "class_name": "Lập trình Web",
        "class_code": "WEB101",
        "faculty_id": 1,
        "lecturer_id": 1,
        "school_year": "2024-2025",
        "faculty": {
            "id": 1,
            "name": "Công nghệ thông tin"
        },
        "lecturer": {
            "id": 1,
            "full_name": "Trần Thị B"
        }
    }
}
```

**Response Error (404):**

```json
{
    "message": "Không tìm thấy lớp học"
}
```

#### 2.4 Cập Nhật Lớp Học

**Method:** `PUT`  
**URL:** `/v1/classes/{id}`  
**Headers:**

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:**

```json
{
    "class_name": "Lập trình Web nâng cao",
    "school_year": "2024-2025"
}
```

**Response Success (200):**

```json
{
    "status": true,
    "message": "Lớp học được cập nhật thành công",
    "data": {
        "id": 1,
        "class_name": "Lập trình Web nâng cao",
        "class_code": "WEB101",
        "faculty_id": 1,
        "lecturer_id": 1,
        "school_year": "2024-2025",
        "updated_at": "2024-12-19T10:00:00.000000Z"
    }
}
```

#### 2.5 Xóa Lớp Học

**Method:** `DELETE`  
**URL:** `/v1/classes/{id}`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "status": true,
    "message": "Lớp học được xóa thành công"
}
```

**Response Error (404):**

```json
{
    "message": "Không tìm thấy lớp học"
}
```

### 3. Quản Lý Sinh Viên

#### 3.1 Lấy Danh Sách Sinh Viên

**Method:** `GET`  
**URL:** `/v1/students`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "email": "sinhvien@test.com",
            "full_name": "Sinh Viên Mẫu",
            "phone": "0987654321",
            "address": "Hà Nội",
            "user_type": "student",
            "account": {
                "username": "sv_sv001",
                "is_admin": false
            },
            "student_info": {
                "student_code": "SV001",
                "birth_date": "2000-01-01T00:00:00.000000Z",
                "gender": "male",
                "class": {
                    "id": 1,
                    "class_name": "Lớp CNTT K65",
                    "class_code": "CNTT65"
                }
            }
        }
    ]
}
```

**Response Error (403):**

```json
{
    "message": "Không có quyền truy cập"
}
```

#### 3.2 Tạo Sinh Viên Mới

**Method:** `POST`  
**URL:** `/v1/students`  
**Headers:**

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:**

```json
{
    "full_name": "Lê Văn C",
    "student_code": "SV002",
    "email": "levanc@example.com",
    "phone": "0987654321",
    "department_id": 1,
    "class_id": 1,
    "gender": "male",
    "address": "Hà Nội",
    "birth_date": "2000-01-01"
}
```

**Response Success (201):**

```json
{
    "message": "Tạo sinh viên thành công",
    "data": {
        "id": 9,
        "email": "anhduong185203@gmail.com",
        "full_name": "Đỗ Văn Anh",
        "phone": "0123848919",
        "address": "TP.HCM",
        "user_type": "student",
        "account": {
            "username": "sv_SV3",
            "is_admin": false
        },
        "student_info": {
            "student_code": "SV3",
            "birth_date": "2000-02-02T00:00:00.000000Z",
            "gender": "female",
            "class": {
                "id": 1,
                "class_name": "Lớp CNTT K65",
                "class_code": "CNTT65"
            }
        }
    },
    "account_info": {
        "username": "sv_SV3",
        "password": "123456"
    }
}
```

**Response Error (422):**

```json
{
    "message": "Validation error",
    "errors": {
        "email": ["Email đã tồn tại"],
        "student_code": ["Mã sinh viên đã tồn tại"]
    }
}
```

#### 3.3 Xem Thông Tin Cá Nhân (Sinh Viên)

**Method:** `GET`  
**URL:** `/v1/student/profile`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": {
        "id": 1,
        "full_name": "Nguyễn Văn A",
        "student_code": "SV001",
        "email": "nguyenvana@example.com",
        "phone": "0123456789",
        "department": "Công nghệ thông tin",
        "class": "WEB101",
        "gender": "male",
        "address": "Hà Nội",
        "birth_date": "2000-01-01"
    }
}
```

#### 3.4 Cập Nhật Thông Tin Cá Nhân (Sinh Viên)

**Method:** `PUT`  
**URL:** `/v1/student/profile`  
**Headers:**

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:**

```json
{
    "phone": "0987654321",
    "address": "TP.HCM"
}
```

**Response Success (200):**

```json
{
    "status": true,
    "message": "Thông tin được cập nhật thành công",
    "data": {
        "id": 1,
        "phone": "0987654321",
        "address": "TP.HCM",
        "updated_at": "2024-12-19T10:00:00.000000Z"
    }
}
```

### 4. Quản Lý Giảng Viên

#### 4.1 Lấy Danh Sách Giảng Viên

**Method:** `GET`  
**URL:** `/v1/lecturers`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "full_name": "Trần Thị B",
            "lecturer_code": "GV001",
            "email": "tranthib@example.com",
            "phone": "0123456789",
            "department": "Công nghệ thông tin",
            "gender": "female"
        }
    ]
}
```

**Response Error (403):**

```json
{
    "message": "Không có quyền truy cập"
}
```

#### 4.2 Tạo Giảng Viên Mới

**Method:** `POST`  
**URL:** `/v1/lecturers`  
**Headers:**

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:**

```json
{
    "full_name": "Phạm Văn D",
    "lecturer_code": "GV002",
    "email": "phamvand@example.com",
    "phone": "0987654321",
    "department_id": 1,
    "gender": "male",
    "address": "TP.HCM",
    "birth_date": "1985-01-01"
}
```

**Response Success (201):**

```json
{
    "status": true,
    "message": "Giảng viên được tạo thành công",
    "data": {
        "id": 2,
        "full_name": "Phạm Văn D",
        "lecturer_code": "GV002",
        "email": "phamvand@example.com",
        "phone": "0987654321",
        "department_id": 1,
        "gender": "male",
        "address": "TP.HCM",
        "birth_date": "1985-01-01",
        "created_at": "2024-12-19T10:00:00.000000Z",
        "updated_at": "2024-12-19T10:00:00.000000Z"
    }
}
```

#### 4.3 Xem Thông Tin Cá Nhân (Giảng Viên)

**Method:** `GET`  
**URL:** `/v1/lecturer/profile`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": {
        "id": 1,
        "full_name": "Trần Thị B",
        "lecturer_code": "GV001",
        "email": "tranthib@example.com",
        "phone": "0123456789",
        "department": "Công nghệ thông tin",
        "gender": "female",
        "address": "Hà Nội",
        "birth_date": "1985-01-01"
    }
}
```

#### 4.4 Cập Nhật Thông Tin Cá Nhân (Giảng Viên)

**Method:** `PUT`  
**URL:** `/v1/lecturer/profile`  
**Headers:**

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:**

```json
{
    "phone": "0987654321",
    "address": "TP.HCM"
}
```

**Response Success (200):**

```json
{
    "status": true,
    "message": "Thông tin được cập nhật thành công",
    "data": {
        "id": 1,
        "phone": "0987654321",
        "address": "TP.HCM",
        "updated_at": "2024-12-19T10:00:00.000000Z"
    }
}
```

### 5. Quản Lý Khoa/Phòng Ban

#### 5.1 Lấy Danh Sách Khoa/Phòng Ban

**Method:** `GET`  
**URL:** `/v1/departments`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Trường Đại học Công nghệ",
            "type": "school",
            "parent_id": null
        },
        {
            "id": 2,
            "name": "Khoa Công nghệ thông tin",
            "type": "faculty",
            "parent_id": 1
        }
    ]
}
```

**Response Error (403):**

```json
{
    "message": "Không có quyền truy cập"
}
```

#### 5.2 Lấy Cây Cấu Trúc Khoa/Phòng Ban

**Method:** `GET`  
**URL:** `/v1/departments/tree`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Trường Đại học Công nghệ",
            "type": "school",
            "children": [
                {
                    "id": 2,
                    "name": "Khoa Công nghệ thông tin",
                    "type": "faculty",
                    "children": [
                        {
                            "id": 4,
                            "name": "Bộ môn Lập trình Web",
                            "type": "department"
                        }
                    ]
                }
            ]
        }
    ]
}
```

#### 5.3 Tạo Khoa/Phòng Ban Mới

**Method:** `POST`  
**URL:** `/v1/departments`  
**Headers:**

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:**

```json
{
    "name": "Bộ môn Lập trình Game",
    "type": "department",
    "parent_id": 2
}
```

**Response Success (201):**

```json
{
    "status": true,
    "message": "Khoa/Phòng ban được tạo thành công",
    "data": {
        "id": 6,
        "name": "Bộ môn Lập trình Game",
        "type": "department",
        "parent_id": 2,
        "created_at": "2024-12-19T10:00:00.000000Z",
        "updated_at": "2024-12-19T10:00:00.000000Z"
    }
}
```

### 6. Quản Lý Thông Báo

#### 6.1 Gửi Thông Báo

**Method:** `POST`  
**URL:** `/v1/notifications/send`  
**Headers:**

```
Content-Type: application/json
```

**Request Body:**

```json
{
    "title": "Thông báo bài tập mới",
    "content": "Có bài tập mới cho lớp WEB101",
    "type": "task",
    "priority": "high",
    "recipients": [
        {
            "user_id": 1,
            "user_type": "student"
        }
    ],
    "channels": ["email", "push", "in_app"]
}
```

**Response Success (200):**

```json
{
    "status": true,
    "message": "Thông báo được gửi thành công",
    "data": {
        "id": 1,
        "title": "Thông báo bài tập mới",
        "content": "Có bài tập mới cho lớp WEB101",
        "type": "task",
        "priority": "high",
        "status": "sent",
        "created_at": "2024-12-19T10:00:00.000000Z"
    }
}
```

**Response Error (422):**

```json
{
    "message": "Validation error",
    "errors": {
        "title": ["Tiêu đề không được để trống"],
        "recipients": ["Danh sách người nhận không được để trống"]
    }
}
```

#### 6.2 Gửi Thông Báo Hàng Loạt

**Method:** `POST`  
**URL:** `/v1/notifications/send-bulk`  
**Headers:**

```
Content-Type: application/json
```

**Request Body:**

```json
{
    "notifications": [
        {
            "title": "Thông báo chung",
            "content": "Thông báo cho tất cả sinh viên",
            "type": "general",
            "priority": "medium",
            "recipients": [
                {
                    "user_id": 1,
                    "user_type": "student"
                },
                {
                    "user_id": 2,
                    "user_type": "student"
                }
            ],
            "channels": ["email", "in_app"]
        }
    ]
}
```

**Response Success (200):**

```json
{
    "status": true,
    "message": "Gửi thông báo hàng loạt thành công",
    "data": {
        "total_sent": 2,
        "total_failed": 0,
        "notifications": [
            {
                "id": 2,
                "status": "sent"
            },
            {
                "id": 3,
                "status": "sent"
            }
        ]
    }
}
```

#### 6.3 Lên Lịch Gửi Thông Báo

**Method:** `POST`  
**URL:** `/v1/notifications/schedule`  
**Headers:**

```
Content-Type: application/json
```

**Request Body:**

```json
{
    "title": "Nhắc nhở deadline",
    "content": "Còn 1 ngày nữa đến hạn nộp bài tập",
    "type": "reminder",
    "priority": "high",
    "scheduled_at": "2024-12-20 09:00:00",
    "recipients": [
        {
            "user_id": 1,
            "user_type": "student"
        }
    ],
    "channels": ["email", "push"]
}
```

**Response Success (200):**

```json
{
    "status": true,
    "message": "Thông báo được lên lịch thành công",
    "data": {
        "id": 4,
        "title": "Nhắc nhở deadline",
        "scheduled_at": "2024-12-20 09:00:00",
        "status": "scheduled"
    }
}
```

#### 6.4 Lấy Danh Sách Template

**Method:** `GET`  
**URL:** `/v1/notifications/templates`  
**Headers:**

```
Content-Type: application/json
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Thông báo bài tập",
            "title": "Có bài tập mới cho lớp {class_name}",
            "content": "Thông báo: {content}",
            "channels": ["email", "push", "in_app"],
            "is_active": true
        }
    ]
}
```

#### 6.5 Kiểm Tra Trạng Thái Thông Báo

**Method:** `GET`  
**URL:** `/v1/notifications/status/{id}`  
**Headers:**

```
Content-Type: application/json
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": {
        "id": 1,
        "title": "Thông báo bài tập mới",
        "status": "sent",
        "sent_at": "2024-12-19T10:00:00.000000Z",
        "delivery_status": {
            "email": "delivered",
            "push": "sent",
            "in_app": "delivered"
        }
    }
}
```

#### 6.6 Lấy Thông Báo Của User (Internal)

**Method:** `GET`  
**URL:** `/v1/internal/notifications/user`  
**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:** Không có

**Response Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "title": "Thông báo bài tập mới",
            "content": "Có bài tập mới cho lớp WEB101",
            "type": "task",
            "priority": "high",
            "is_read": false,
            "created_at": "2024-12-19T10:00:00.000000Z"
        }
    ]
}
```

**Response Error (401):**

```json
{
    "message": "Token không hợp lệ"
}
```

#### 6.7 Đánh Dấu Đã Đọc

**Method:** `POST`  
**URL:** `/v1/internal/notifications/mark-read`  
**Headers:**

```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

**Request Body:**

```json
{
    "notification_ids": [1, 2, 3]
}
```

**Response Success (200):**

```json
{
    "status": true,
    "message": "Đánh dấu đã đọc thành công",
    "data": {
        "marked_count": 3
    }
}
```

## 🧪 Hướng Dẫn Test API

### 1. Sử Dụng Postman

#### Bước 1: Import Collection

1. Tạo collection mới trong Postman
2. Import các request từ file JSON mẫu bên dưới

#### Bước 2: Thiết Lập Environment

```json
{
    "base_url": "http://localhost:8000/api",
    "token": ""
}
```

#### Bước 3: Test Flow

1. **Đăng nhập** → Lấy token
2. **Set token** vào environment variable
3. **Test các API** khác với token

### 2. Sử Dụng cURL

#### Test Đăng Nhập:

```bash
curl -X POST http://localhost:8000/api/v1/login/student \
  -H "Content-Type: application/json" \
  -d '{
    "username": "SV001",
    "password": "password123"
  }'
```

#### Test API Với Token:

```bash
curl -X GET http://localhost:8000/api/v1/students \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 3. Sử Dụng JavaScript/Fetch

```javascript
// Đăng nhập
const loginResponse = await fetch(
    "http://localhost:8000/api/v1/login/student",
    {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            username: "SV001",
            password: "password123",
        }),
    }
);

const loginData = await loginResponse.json();
const token = loginData.data.token;

// Sử dụng token cho API khác
const studentsResponse = await fetch("http://localhost:8000/api/v1/students", {
    headers: {
        Authorization: `Bearer ${token}`,
    },
});
```

## 📝 Mẫu JSON Test

### 1. Test Data - Sinh Viên

```json
{
    "students": [
        {
            "full_name": "Nguyễn Văn A",
            "student_code": "SV001",
            "email": "nguyenvana@example.com",
            "phone": "0123456789",
            "gender": "male",
            "address": "Hà Nội",
            "birth_date": "2000-01-01"
        },
        {
            "full_name": "Lê Thị B",
            "student_code": "SV002",
            "email": "lethib@example.com",
            "phone": "0987654321",
            "gender": "female",
            "address": "TP.HCM",
            "birth_date": "2000-02-02"
        }
    ]
}
```

### 2. Test Data - Giảng Viên

```json
{
    "lecturers": [
        {
            "full_name": "Trần Văn C",
            "lecturer_code": "GV001",
            "email": "tranvanc@example.com",
            "phone": "0123456789",
            "gender": "male",
            "address": "Hà Nội",
            "birth_date": "1985-01-01"
        },
        {
            "full_name": "Phạm Thị D",
            "lecturer_code": "GV002",
            "email": "phamthid@example.com",
            "phone": "0987654321",
            "gender": "female",
            "address": "TP.HCM",
            "birth_date": "1985-02-02"
        }
    ]
}
```

### 3. Test Data - Lớp Học

```json
{
    "classes": [
        {
            "class_name": "Lập trình Web",
            "class_code": "WEB101",
            "faculty_id": 1,
            "lecturer_id": 1,
            "school_year": "2024-2025"
        },
        {
            "class_name": "Lập trình Mobile",
            "class_code": "MOB101",
            "faculty_id": 1,
            "lecturer_id": 2,
            "school_year": "2024-2025"
        }
    ]
}
```

## ⚠️ Lưu Ý Quan Trọng

### 1. Bảo Mật

-   Luôn sử dụng HTTPS trong môi trường production
-   Token JWT có thời hạn 24 giờ
-   Không lưu trữ token ở client-side storage không an toàn

### 2. Rate Limiting

-   API có giới hạn số request để tránh spam
-   Vui lòng không gửi quá nhiều request trong thời gian ngắn

### 3. Validation

-   Tất cả input đều được validate
-   Kiểm tra format email, số điện thoại
-   Độ dài tối đa cho các trường text

### 4. Error Handling

-   HTTP status codes chuẩn
-   Error messages rõ ràng bằng tiếng Việt
-   Logs chi tiết cho debugging

### 5. Phân Quyền

-   **Admin**: Có thể quản lý tất cả (students, lecturers, classes, departments)
-   **Lecturer**: Chỉ xem và cập nhật thông tin cá nhân
-   **Student**: Chỉ xem và cập nhật thông tin cá nhân

## 🔧 Troubleshooting

### Lỗi Thường Gặp

#### 1. Token Expired (401)

```json
{
    "message": "Token không hợp lệ"
}
```

**Giải pháp:** Gọi API refresh token

#### 2. Validation Error (422)

```json
{
    "message": "Validation error",
    "errors": {
        "email": ["Email không đúng định dạng"],
        "password": ["Mật khẩu phải có ít nhất 6 ký tự"]
    }
}
```

**Giải pháp:** Kiểm tra format dữ liệu input

#### 3. Unauthorized (401)

```json
{
    "message": "Token không được cung cấp"
}
```

**Giải pháp:** Thêm header Authorization

#### 4. Not Found (404)

```json
{
    "message": "Không tìm thấy tài nguyên"
}
```

**Giải pháp:** Kiểm tra URL và ID

#### 5. Forbidden (403)

```json
{
    "message": "Không có quyền truy cập"
}
```

**Giải pháp:** Kiểm tra quyền của user

## 📞 Hỗ Trợ

Nếu gặp vấn đề hoặc cần hỗ trợ:

-   Email: dovananh145203@gmail.com

---

**Phiên bản:** 1.0.0  
**Cập nhật lần cuối:** 19/12/2024  
**Tác giả:** Development Team
