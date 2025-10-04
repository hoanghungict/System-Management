# Roll Call Flexible API - Hỗ trợ Manual Type

## Tổng quan

Hệ thống roll call đã được mở rộng để hỗ trợ 2 loại điểm danh:

1. **class_based**: Điểm danh theo lớp (như cũ)
2. **manual**: Điểm danh tự chọn sinh viên

## API Endpoints

### 1. Tạo buổi điểm danh theo lớp (class_based)

**POST** `/api/v1/roll-calls`

```json
{
    "type": "class_based",
    "class_id": 1,
    "title": "Điểm danh lớp CNTT01 - 24/09/2025",
    "description": "Buổi học môn Lập trình Web",
    "date": "2025-09-24T08:00:00",
    "created_by": 1
}
```

**Response:**

```json
{
    "success": true,
    "message": "Tạo buổi điểm danh thành công.",
    "data": {
        "id": 1,
        "type": "class_based",
        "class_id": 1,
        "title": "Điểm danh lớp CNTT01 - 24/09/2025",
        "description": "Buổi học môn Lập trình Web",
        "date": "2025-09-24T08:00:00.000000Z",
        "status": "active",
        "expected_participants": null,
        "metadata": null,
        "created_by": 1,
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
            }
            // ... tất cả sinh viên trong lớp
        ]
    }
}
```

### 2. Tạo buổi điểm danh manual (tự chọn sinh viên)

**POST** `/api/v1/roll-calls`

```json
{
    "type": "manual",
    "class_id": null,
    "title": "Thi cuối kỳ Lập trình Web",
    "description": "Điểm danh thí sinh thi cuối kỳ",
    "date": "2025-09-24T14:00:00",
    "created_by": 1,
    "participants": [1, 5, 8, 12, 20],
    "expected_participants": 50
}
```

**Response:**

```json
{
    "success": true,
    "message": "Tạo buổi điểm danh thành công.",
    "data": {
        "id": 2,
        "type": "manual",
        "class_id": null,
        "title": "Thi cuối kỳ Lập trình Web",
        "description": "Điểm danh thí sinh thi cuối kỳ",
        "date": "2025-09-24T14:00:00.000000Z",
        "status": "active",
        "expected_participants": 50,
        "metadata": null,
        "created_by": 1,
        "roll_call_details": [
            {
                "id": 2,
                "roll_call_id": 2,
                "student_id": 1,
                "status": "Vắng Mặt",
                "note": null,
                "checked_at": null,
                "student": {
                    "id": 1,
                    "full_name": "Nguyễn Văn A",
                    "student_code": "SV001"
                }
            }
            // ... chỉ những sinh viên được chọn
        ]
    }
}
```

### 3. Lấy tất cả sinh viên để chọn

**GET** `/api/v1/roll-calls/all-students`

**Response:**

```json
{
    "success": true,
    "message": "Lấy danh sách sinh viên thành công.",
    "data": [
        {
            "id": 1,
            "full_name": "Nguyễn Văn A",
            "student_code": "SV001",
            "email": "nguyenvana@example.com",
            "class_id": 1,
            "account": {
                "id": 1,
                "username": "nguyenvana",
                "is_active": true
            }
        },
        {
            "id": 2,
            "full_name": "Trần Thị B",
            "student_code": "SV002",
            "email": "tranthib@example.com",
            "class_id": 1,
            "account": {
                "id": 2,
                "username": "tranthib",
                "is_active": true
            }
        }
        // ... tất cả sinh viên trong hệ thống
    ]
}
```

### 4. Thêm sinh viên vào buổi điểm danh manual

**POST** `/api/v1/roll-calls/{rollCallId}/participants`

```json
{
    "student_ids": [3, 7, 9, 15]
}
```

**Response:**

```json
{
    "success": true,
    "message": "Thêm sinh viên vào buổi điểm danh thành công."
}
```

### 5. Xóa sinh viên khỏi buổi điểm danh manual

**DELETE** `/api/v1/roll-calls/{rollCallId}/participants/{studentId}`

**Response:**

```json
{
    "success": true,
    "message": "Xóa sinh viên khỏi buổi điểm danh thành công."
}
```

## Validation Rules

### Tạo Roll Call

```php
// Chung cho cả 2 loại
'type' => 'required|in:class_based,manual'
'title' => 'required|string|max:255'
'description' => 'nullable|string|max:1000'
'date' => 'required|date|after_or_equal:today'
'created_by' => 'required|integer|exists:lecturer,id'

// Riêng cho class_based
'class_id' => 'required|integer|exists:class,id'

// Riêng cho manual
'class_id' => 'nullable'
'participants' => 'required|array|min:1'
'participants.*' => 'integer|exists:student,id'
'expected_participants' => 'nullable|integer|min:1'
```

### Thêm Participants

```php
'student_ids' => 'required|array|min:1'
'student_ids.*' => 'integer|exists:student,id'
```

## Database Schema Changes

### Migration: `add_type_to_roll_calls_table`

```sql
ALTER TABLE roll_calls
ADD COLUMN type ENUM('class_based', 'manual') DEFAULT 'class_based' AFTER class_id,
ADD COLUMN expected_participants INT NULL AFTER type,
ADD COLUMN metadata JSON NULL AFTER expected_participants,
ADD INDEX idx_type_status (type, status);
```

## Business Logic

### Class-based Roll Call

1. Bắt buộc có `class_id`
2. Tự động tạo `roll_call_details` cho TẤT CẢ sinh viên trong lớp
3. Trạng thái mặc định: "Vắng Mặt"

### Manual Roll Call

1. `class_id` có thể null
2. Chỉ tạo `roll_call_details` cho sinh viên được chọn trong `participants`
3. Có thể thêm/xóa sinh viên sau khi tạo
4. Trạng thái mặc định: "Vắng Mặt"

## Use Cases

### ✅ Class-based (Hiện tại)

-   Điểm danh lớp học hàng ngày
-   Điểm danh môn học theo lớp cố định
-   Điểm danh sinh hoạt lớp

### ✅ Manual (Mới)

-   Thi cuối kỳ (nhiều lớp thi chung)
-   Hội thảo, workshop (không theo lớp)
-   Sự kiện đặc biệt
-   Khóa học ngắn hạn
-   Thi Olympic, thi HSG
-   Hoạt động ngoại khóa

## Response Format

### Success Response

```json
{
    "success": true,
    "message": "Thông báo thành công",
    "data": {...}
}
```

### Error Response

```json
{
    "success": false,
    "message": "Thông báo lỗi",
    "error": "Chi tiết lỗi (optional)"
}
```

### Validation Error

```json
{
    "success": false,
    "message": "Dữ liệu không hợp lệ",
    "errors": {
        "type": ["Vui lòng chọn loại điểm danh."],
        "participants": ["Vui lòng chọn sinh viên tham gia."]
    }
}
```

## Caching Strategy

-   `classrooms:with_students` - Cache 30 phút
-   `all_students_for_roll_call` - Cache 30 phút
-   `roll_calls:class:{classId}:page:{perPage}` - Auto clear khi update
-   `roll_call_details:{rollCallId}` - Auto clear khi update participants

## Logging

Tất cả operations đều được log với level INFO/ERROR:

-   Roll call creation (cả 2 loại)
-   Participant management (add/remove)
-   Cache operations
-   Errors với full stack trace
