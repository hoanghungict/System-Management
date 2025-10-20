# 📋 RollCall (Điểm Danh) API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
Tất cả RollCall endpoints yêu cầu JWT token và **lecturer** permissions:
```
Authorization: Bearer {JWT_TOKEN}
```

---

# 📑 Table of Contents
1. [Get Resources](#1-get-resources)
2. [Create Roll Call](#2-create-roll-call)
3. [Query Roll Calls](#3-query-roll-calls)
4. [Update Attendance Status](#4-update-attendance-status)
5. [Manage Participants (Manual)](#5-manage-participants-manual)
6. [Complete & Cancel](#6-complete--cancel)
7. [Statistics](#7-statistics)

---

# 1. Get Resources

## 1.1. Get Classrooms (For Roll Call Creation)

**GET** `/roll-calls/classrooms`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Description:** Lấy danh sách lớp học để tạo điểm danh

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "class_name": "CNTT K15",
      "class_code": "CNTT15",
      "school_year": "2023-2024",
      "department_id": 3,
      "lecturer_id": 1,
      "students": [
        {
          "id": 1,
          "full_name": "Nguyễn Văn A",
          "student_code": "SV001",
          "email": "nguyenvana@email.com"
        }
      ]
    }
  ]
}
```

---

## 1.2. Get Students By Class (For Roll Call)

**GET** `/roll-calls/students/class/{classId}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Description:** Lấy danh sách sinh viên trong lớp để điểm danh

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "full_name": "Nguyễn Văn A",
      "student_code": "SV001",
      "email": "nguyenvana@email.com",
      "phone": "0123456789",
      "class_id": 5,
      "account": {
        "username": "sv_SV001"
      }
    },
    {
      "id": 2,
      "full_name": "Trần Thị B",
      "student_code": "SV002",
      "email": "tranthib@email.com",
      "phone": "0987654321",
      "class_id": 5,
      "account": {
        "username": "sv_SV002"
      }
    }
  ]
}
```

---

## 1.3. Get All Students (For Manual Roll Call)

**GET** `/roll-calls/all-students`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Description:** Lấy TẤT CẢ sinh viên để chọn cho manual roll call

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "full_name": "Nguyễn Văn A",
      "student_code": "SV001",
      "email": "nguyenvana@email.com",
      "class_id": 5,
      "classroom": {
        "id": 5,
        "class_name": "CNTT K15",
        "class_code": "CNTT15"
      }
    },
    {
      "id": 3,
      "full_name": "Lê Văn C",
      "student_code": "SV003",
      "email": "levanc@email.com",
      "class_id": 6,
      "classroom": {
        "id": 6,
        "class_name": "CNTT K16",
        "class_code": "CNTT16"
      }
    }
  ],
  "message": "Lấy danh sách sinh viên thành công."
}
```

---

# 2. Create Roll Call

## 2.1. Create Class-Based Roll Call

**POST** `/roll-calls`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Description:** Tạo buổi điểm danh cho **CẢ LỚP** - tự động lấy tất cả sinh viên

**Request Body:**
```json
{
  "type": "class_based",
  "class_id": 5,
  "title": "Điểm danh buổi 1 - Lập trình Web",
  "description": "Điểm danh môn Lập trình Web - Buổi 1",
  "date": "2024-01-15 08:00:00"
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Tạo buổi điểm danh thành công.",
  "data": {
    "id": 1,
    "class_id": 5,
    "title": "Điểm danh buổi 1 - Lập trình Web",
    "description": "Điểm danh môn Lập trình Web - Buổi 1",
    "date": "2024-01-15T08:00:00.000000Z",
    "status": "active",
    "type": "class_based",
    "created_by": 1,
    "expected_participants": 45,
    "created_at": "2024-01-15T07:00:00.000000Z",
    "class": {
      "id": 5,
      "class_name": "CNTT K15",
      "class_code": "CNTT15"
    },
    "creator": {
      "id": 1,
      "full_name": "Trần Thị B",
      "lecturer_code": "GV001"
    },
    "roll_call_details": [
      {
        "id": 1,
        "roll_call_id": 1,
        "student_id": 1,
        "status": "Vắng Mặt",
        "note": null,
        "checked_at": null,
        "student": {
          "id": 1,
          "full_name": "Nguyễn Văn A",
          "student_code": "SV001"
        }
      },
      {
        "id": 2,
        "roll_call_id": 1,
        "student_id": 2,
        "status": "Vắng Mặt",
        "note": null,
        "checked_at": null,
        "student": {
          "id": 2,
          "full_name": "Trần Thị B",
          "student_code": "SV002"
        }
      }
    ]
  }
}
```

**Response Error (500):**
```json
{
  "success": false,
  "message": "Có lỗi xảy ra khi tạo buổi điểm danh.",
  "error": "Class not found"
}
```

---

## 2.2. Create Manual Roll Call

**POST** `/roll-calls`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Description:** Tạo buổi điểm danh **TỰ CHỌN** sinh viên

**Request Body:**
```json
{
  "type": "manual",
  "class_id": null,
  "title": "Điểm danh nhóm nghiên cứu",
  "description": "Điểm danh sinh viên tham gia dự án",
  "date": "2024-01-15 14:00:00",
  "participants": [1, 3, 5, 7]
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Tạo buổi điểm danh thành công.",
  "data": {
    "id": 2,
    "class_id": null,
    "title": "Điểm danh nhóm nghiên cứu",
    "description": "Điểm danh sinh viên tham gia dự án",
    "date": "2024-01-15T14:00:00.000000Z",
    "status": "active",
    "type": "manual",
    "created_by": 1,
    "expected_participants": 4,
    "created_at": "2024-01-15T07:30:00.000000Z",
    "roll_call_details": [
      {
        "id": 3,
        "roll_call_id": 2,
        "student_id": 1,
        "status": "Vắng Mặt",
        "student": {
          "id": 1,
          "full_name": "Nguyễn Văn A"
        }
      },
      {
        "id": 4,
        "roll_call_id": 2,
        "student_id": 3,
        "status": "Vắng Mặt",
        "student": {
          "id": 3,
          "full_name": "Lê Văn C"
        }
      }
    ]
  }
}
```

---

# 3. Query Roll Calls

## 3.1. Get All Roll Calls (With Filters)

**GET** `/roll-calls?page={page}&per_page={per_page}&status={status}&type={type}&search={search}&class_id={class_id}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Query Parameters:**
- `page` (optional): Trang hiện tại (default: 1)
- `per_page` (optional): Số items mỗi trang (default: 15)
- `status` (optional): Filter theo status (`active`, `completed`, `cancelled`)
- `type` (optional): Filter theo type (`class_based`, `manual`)
- `search` (optional): Tìm kiếm theo title hoặc description
- `class_id` (optional): Filter theo lớp

**Example Request:**
```
GET /roll-calls?page=1&per_page=10&status=active&type=class_based
```

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "class_id": 5,
        "title": "Điểm danh buổi 1 - Lập trình Web",
        "description": "Điểm danh môn Lập trình Web",
        "date": "2024-01-15T08:00:00.000000Z",
        "status": "active",
        "type": "class_based",
        "created_by": 1,
        "expected_participants": 45,
        "class": {
          "id": 5,
          "class_name": "CNTT K15",
          "class_code": "CNTT15"
        },
        "creator": {
          "id": 1,
          "full_name": "Trần Thị B"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/v1/roll-calls?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost:8000/api/v1/roll-calls?page=3",
    "next_page_url": "http://localhost:8000/api/v1/roll-calls?page=2",
    "path": "http://localhost:8000/api/v1/roll-calls",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 25
  }
}
```

---

## 3.2. Get Roll Calls By Class

**GET** `/roll-calls/class/{classId}?per_page={per_page}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Query Parameters:**
- `per_page` (optional): Số items mỗi trang (default: 15)

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "class_id": 5,
        "title": "Điểm danh buổi 1",
        "date": "2024-01-15T08:00:00.000000Z",
        "status": "active",
        "type": "class_based",
        "expected_participants": 45
      }
    ],
    "total": 5,
    "per_page": 15
  }
}
```

---

## 3.3. Get Roll Call Details

**GET** `/roll-calls/{id}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Description:** Lấy chi tiết buổi điểm danh kèm danh sách sinh viên và trạng thái

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "class_id": 5,
    "title": "Điểm danh buổi 1 - Lập trình Web",
    "description": "Điểm danh môn Lập trình Web - Buổi 1",
    "date": "2024-01-15T08:00:00.000000Z",
    "status": "active",
    "type": "class_based",
    "created_by": 1,
    "expected_participants": 45,
    "created_at": "2024-01-15T07:00:00.000000Z",
    "updated_at": "2024-01-15T07:00:00.000000Z",
    "class": {
      "id": 5,
      "class_name": "CNTT K15",
      "class_code": "CNTT15",
      "department": {
        "id": 3,
        "name": "Khoa CNTT"
      }
    },
    "creator": {
      "id": 1,
      "full_name": "Trần Thị B",
      "lecturer_code": "GV001",
      "email": "tranthib@email.com"
    },
    "roll_call_details": [
      {
        "id": 1,
        "roll_call_id": 1,
        "student_id": 1,
        "status": "Có Mặt",
        "note": null,
        "checked_at": "2024-01-15T08:05:00.000000Z",
        "created_at": "2024-01-15T07:00:00.000000Z",
        "updated_at": "2024-01-15T08:05:00.000000Z",
        "student": {
          "id": 1,
          "full_name": "Nguyễn Văn A",
          "student_code": "SV001",
          "email": "nguyenvana@email.com",
          "class_id": 5
        }
      },
      {
        "id": 2,
        "roll_call_id": 1,
        "student_id": 2,
        "status": "Muộn",
        "note": "Muộn 15 phút",
        "checked_at": "2024-01-15T08:15:00.000000Z",
        "student": {
          "id": 2,
          "full_name": "Trần Thị B",
          "student_code": "SV002",
          "email": "tranthib@email.com"
        }
      },
      {
        "id": 3,
        "roll_call_id": 1,
        "student_id": 3,
        "status": "Vắng Mặt",
        "note": null,
        "checked_at": null,
        "student": {
          "id": 3,
          "full_name": "Lê Văn C",
          "student_code": "SV003"
        }
      }
    ]
  }
}
```

**Response Error (500):**
```json
{
  "success": false,
  "message": "Có lỗi xảy ra khi lấy chi tiết buổi điểm danh.",
  "error": "Roll call not found"
}
```

---

# 4. Update Attendance Status

## 4.1. Update Single Student Status

**PUT** `/roll-calls/{rollCallId}/status`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Description:** Cập nhật trạng thái điểm danh cho 1 sinh viên

**Request Body:**
```json
{
  "student_id": 1,
  "status": "Có Mặt",
  "note": "Đến đúng giờ"
}
```

**Status Values:**
- `Có Mặt` (Present)
- `Vắng Mặt` (Absent)
- `Muộn` (Late)
- `Có Phép` (Excused)

**Response Success (200):**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái điểm danh thành công."
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Cập nhật trạng thái điểm danh thất bại."
}
```

---

## 4.2. Bulk Update Status

**PUT** `/roll-calls/{rollCallId}/bulk-status`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Description:** Cập nhật trạng thái cho **NHIỀU** sinh viên cùng lúc

**Request Body:**
```json
{
  "student_statuses": [
    {
      "student_id": 1,
      "status": "Có Mặt",
      "note": null
    },
    {
      "student_id": 2,
      "status": "Muộn",
      "note": "Muộn 10 phút"
    },
    {
      "student_id": 3,
      "status": "Vắng Mặt",
      "note": null
    },
    {
      "student_id": 4,
      "status": "Có Phép",
      "note": "Nghỉ ốm có đơn"
    }
  ]
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái điểm danh hàng loạt thành công."
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Cập nhật trạng thái điểm danh hàng loạt thất bại."
}
```

---

# 5. Manage Participants (Manual)

## 5.1. Add Participants to Manual Roll Call

**POST** `/roll-calls/{rollCallId}/participants`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Description:** Thêm sinh viên vào buổi điểm danh **manual** (chỉ dành cho manual type)

**Request Body:**
```json
{
  "student_ids": [5, 7, 9, 11]
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Thêm sinh viên vào buổi điểm danh thành công."
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Thêm sinh viên vào buổi điểm danh thất bại."
}
```

**Response Error (500):**
```json
{
  "success": false,
  "message": "Có lỗi xảy ra khi thêm sinh viên.",
  "error": "Roll call không tồn tại hoặc không phải loại manual"
}
```

---

## 5.2. Remove Participant from Manual Roll Call

**DELETE** `/roll-calls/{rollCallId}/participants/{studentId}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Description:** Xóa sinh viên khỏi buổi điểm danh **manual**

**Response Success (200):**
```json
{
  "success": true,
  "message": "Xóa sinh viên khỏi buổi điểm danh thành công."
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Xóa sinh viên khỏi buổi điểm danh thất bại."
}
```

---

# 6. Complete & Cancel

## 6.1. Complete Roll Call

**PATCH** `/roll-calls/{id}/complete`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Description:** Hoàn thành buổi điểm danh (set status = `completed`)

**Response Success (200):**
```json
{
  "success": true,
  "message": "Hoàn thành buổi điểm danh thành công."
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Hoàn thành buổi điểm danh thất bại."
}
```

---

## 6.2. Cancel Roll Call

**PATCH** `/roll-calls/{id}/cancel`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Description:** Hủy buổi điểm danh (set status = `cancelled`)

**Response Success (200):**
```json
{
  "success": true,
  "message": "Hủy buổi điểm danh thành công."
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Hủy buổi điểm danh thất bại."
}
```

---

# 7. Statistics

## 7.1. Get Roll Call Statistics By Class

**GET** `/roll-calls/statistics/class/{classId}?start_date={start_date}&end_date={end_date}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Query Parameters:**
- `start_date` (optional): Ngày bắt đầu (format: YYYY-MM-DD)
- `end_date` (optional): Ngày kết thúc (format: YYYY-MM-DD)

**Example Request:**
```
GET /roll-calls/statistics/class/5?start_date=2024-01-01&end_date=2024-01-31
```

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "total_roll_calls": 10,
    "roll_call_sessions": [
      {
        "roll_call_id": 1,
        "title": "Điểm danh buổi 1 - Lập trình Web",
        "date": "2024-01-15 08:00:00",
        "status": "completed",
        "type": "class_based",
        "students": {
          "total": 45,
          "present": 40,
          "absent": 3,
          "late": 2,
          "excused": 0
        },
        "attendance_rate": 93.33
      },
      {
        "roll_call_id": 2,
        "title": "Điểm danh buổi 2 - Lập trình Web",
        "date": "2024-01-17 08:00:00",
        "status": "completed",
        "type": "class_based",
        "students": {
          "total": 45,
          "present": 42,
          "absent": 2,
          "late": 1,
          "excused": 0
        },
        "attendance_rate": 95.56
      }
    ],
    "summary": {
      "total_students_checked": 450,
      "total_present": 420,
      "total_absent": 18,
      "total_late": 10,
      "total_excused": 2,
      "average_attendance_rate": 95.56
    }
  }
}
```

**Response Error (500):**
```json
{
  "success": false,
  "message": "Có lỗi xảy ra khi lấy thống kê điểm danh.",
  "error": "Error message"
}
```

---

## Status Values Reference

| Status | Vietnamese | Description |
|--------|-----------|-------------|
| `Có Mặt` | Present | Sinh viên có mặt |
| `Vắng Mặt` | Absent | Sinh viên vắng mặt |
| `Muộn` | Late | Sinh viên đến muộn |
| `Có Phép` | Excused | Sinh viên nghỉ có phép |

---

## Roll Call Types

| Type | Description | Features |
|------|-------------|----------|
| `class_based` | Điểm danh theo lớp | Tự động lấy TẤT CẢ sinh viên trong lớp |
| `manual` | Điểm danh tự chọn | Giảng viên tự chọn sinh viên tham gia |

---

## Roll Call Status

| Status | Description |
|--------|-------------|
| `active` | Đang diễn ra |
| `completed` | Đã hoàn thành |
| `cancelled` | Đã hủy |

---

## Error Codes Summary

| Status Code | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden (Only lecturer can access) |
| 404 | Not Found |
| 500 | Internal Server Error |

---

## Notes

1. **Lecturer Only**: Tất cả RollCall endpoints chỉ dành cho giảng viên
2. **Auto Status**: Khi tạo mới, tất cả sinh viên mặc định là `Vắng Mặt`
3. **Real-time**: Có thể cập nhật status nhiều lần trong khi roll call đang `active`
4. **Caching**: Một số endpoints sử dụng cache với TTL ngắn (60-300s)
5. **Manual Flexibility**: Manual roll call cho phép thêm/xóa participants bất cứ lúc nào
6. **Statistics**: Thống kê chỉ tính trên các roll calls đã `completed`

---

## Workflow Example

### Class-Based Roll Call:
```
1. GET /roll-calls/classrooms
   → Chọn lớp cần điểm danh

2. POST /roll-calls
   → Tạo buổi điểm danh với type="class_based"
   → Hệ thống tự động tạo list sinh viên với status="Vắng Mặt"

3. GET /roll-calls/{id}
   → Lấy danh sách sinh viên cần điểm danh

4. PUT /roll-calls/{id}/status (multiple times)
   → Cập nhật từng sinh viên: "Có Mặt", "Muộn", etc.
   hoặc
   PUT /roll-calls/{id}/bulk-status
   → Cập nhật hàng loạt

5. PATCH /roll-calls/{id}/complete
   → Hoàn thành buổi điểm danh
```

### Manual Roll Call:
```
1. GET /roll-calls/all-students
   → Lấy danh sách tất cả sinh viên

2. POST /roll-calls
   → Tạo với type="manual" và chọn students
   → participants: [1, 3, 5, 7]

3. (Optional) POST /roll-calls/{id}/participants
   → Thêm sinh viên mới: [9, 11]

4. PUT /roll-calls/{id}/bulk-status
   → Cập nhật trạng thái

5. PATCH /roll-calls/{id}/complete
   → Hoàn thành
```

