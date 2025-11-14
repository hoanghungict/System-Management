# 📊 Task Module API Endpoints - Complete Reference (Updated)

## 🔐 Authentication
Tất cả endpoints đều yêu cầu JWT authentication:
```
Authorization: Bearer <jwt_token>
```

## 📋 Tổng quan Endpoints

**Tổng cộng: 131 API endpoints** được phân chia theo:

- **🔓 Common Routes** (Tất cả user): 55 endpoints
- **👨‍🏫 Lecturer Routes** (Giảng viên): 26 endpoints  
- **👨‍🎓 Student Routes** (Sinh viên): 26 endpoints
  - Task Management: 12 endpoints
  - Calendar: 8 endpoints
  - Class: 6 endpoints
- **🔧 Admin Routes** (Quản trị): 24 endpoints
- **📊 Statistics & Reports**: 23 endpoints
- **🔗 Dependencies & Others**: 42 endpoints

---

## 🔓 COMMON ROUTES (Tất cả người dùng đã đăng nhập)

### **Base URL:** `/api/v1`

---

## 📋 Task Management Endpoints

### **Base URL:** `/api/v1/tasks`

#### **CRUD Operations**
```http
GET    /api/v1/tasks              # Lấy danh sách tasks
GET    /api/v1/tasks/{task}       # Lấy chi tiết task
```

**Note:** Các API sau đã được loại bỏ vì phân quyền rõ ràng:
- **Tạo task** (`POST /api/v1/tasks`) - Admin sử dụng `/api/v1/admin-tasks`, Lecturer sử dụng `/api/v1/lecturer-tasks`
- **Cập nhật task** (`PUT /api/v1/tasks/{task}`) - Chỉ Admin và Lecturer được phép
- **Xóa task** (`DELETE /api/v1/tasks/{task}`) - Chỉ Admin được phép

#### **Additional Endpoints**
```http
GET    /api/v1/tasks/my-tasks                    # Tasks của tôi
GET    /api/v1/tasks/my-assigned-tasks           # Tasks được giao
GET    /api/v1/tasks/statistics/my               # Thống kê cá nhân
PATCH  /api/v1/tasks/{task}/status               # Cập nhật trạng thái
POST   /api/v1/tasks/{task}/submit               # Nộp task
POST   /api/v1/tasks/{task}/files                # Upload files
DELETE /api/v1/tasks/{task}/files/{file}         # Xóa file
GET    /api/v1/tasks/departments                 # Lấy danh sách khoa
GET    /api/v1/tasks/classes/by-department       # Lấy lớp theo khoa
GET    /api/v1/tasks/students/by-class           # Lấy sinh viên theo lớp
GET    /api/v1/tasks/lecturers                   # Lấy danh sách giảng viên
```

---

## 🔗 Task Dependencies Endpoints

### **Base URL:** `/api/v1/task-dependencies`

```http
GET    /api/v1/task-dependencies/task/{taskId}                    # Dependencies của task
POST   /api/v1/task-dependencies                                 # Tạo dependency
GET    /api/v1/task-dependencies/{dependencyId}                  # Chi tiết dependency
PUT    /api/v1/task-dependencies/{dependencyId}                  # Cập nhật dependency
PATCH  /api/v1/task-dependencies/{dependencyId}                  # Cập nhật dependency (partial)
DELETE /api/v1/task-dependencies/{dependencyId}                  # Xóa dependency
GET    /api/v1/task-dependencies/task/{taskId}/with-dependencies  # Task với dependencies
POST   /api/v1/task-dependencies/validate                        # Validate dependency
GET    /api/v1/task-dependencies/task/{taskId}/can-start          # Kiểm tra task có thể bắt đầu
GET    /api/v1/task-dependencies/task/{taskId}/blocked-tasks      # Tasks bị chặn
GET    /api/v1/task-dependencies/task/{taskId}/dependency-chain   # Chuỗi dependency
POST   /api/v1/task-dependencies/bulk-create                     # Tạo nhiều dependencies
DELETE /api/v1/task-dependencies/bulk-delete                     # Xóa nhiều dependencies
```

---

## 📈 Statistics Endpoints

### **Base URL:** `/api/v1/statistics`

```http
GET    /api/v1/statistics/user                      # Thống kê cá nhân
GET    /api/v1/statistics/created                   # Thống kê task đã tạo
GET    /api/v1/statistics/overview                  # Thống kê tổng quan
GET    /api/v1/statistics/completion-rate           # Tỷ lệ hoàn thành
GET    /api/v1/statistics/priority-distribution     # Phân bố độ ưu tiên
GET    /api/v1/statistics/status-distribution       # Phân bố trạng thái
GET    /api/v1/statistics/trend                     # Xu hướng theo thời gian
GET    /api/v1/statistics/breakdown-by-class        # Thống kê theo lớp
GET    /api/v1/statistics/breakdown-by-department   # Thống kê theo khoa
GET    /api/v1/statistics/submission-rate           # Tỷ lệ nộp bài
GET    /api/v1/statistics/grading-status            # Trạng thái chấm điểm
GET    /api/v1/statistics/dependency-statistics     # Thống kê phụ thuộc
```

---

## 📋 Reports Endpoints

### **Base URL:** `/api/v1/reports`

```http
GET    /api/v1/reports/export/excel                 # Xuất Excel
GET    /api/v1/reports/export/pdf                   # Xuất PDF
GET    /api/v1/reports/export/csv                   # Xuất CSV
GET    /api/v1/reports/comprehensive                # Báo cáo tổng hợp
GET    /api/v1/reports/student/{studentId}/progress # Báo cáo sinh viên
GET    /api/v1/reports/class/{classId}/performance  # Báo cáo lớp
GET    /api/v1/reports/formats                      # Định dạng export
GET    /api/v1/reports/dashboard-summary            # Tóm tắt dashboard
GET    /api/v1/reports/recent-activities            # Hoạt động gần đây
GET    /api/v1/reports/overdue-tasks                # Tasks quá hạn
GET    /api/v1/reports/upcoming-deadlines           # Deadline sắp tới
```

---

## 🔔 Reminder Endpoints

### **Base URL:** `/api/v1/reminders`

```http
GET    /api/v1/reminders              # Lấy danh sách reminders
POST   /api/v1/reminders              # Tạo reminder mới
GET    /api/v1/reminders/{id}         # Lấy chi tiết reminder
PUT    /api/v1/reminders/{id}         # Cập nhật reminder
PATCH  /api/v1/reminders/{id}         # Cập nhật reminder (partial)
DELETE /api/v1/reminders/{id}         # Xóa reminder
POST   /api/v1/reminders/process-due  # Xử lý reminders đến hạn
```

---

## 📧 Email Endpoints

### **Base URL:** `/api/v1/email`

```http
POST   /api/v1/email/send-notification        # Gửi email thông báo
```

---

## 👨‍🏫 LECTURER ROUTES (Chỉ dành cho Giảng viên)

### **Base URL:** `/api/v1`

---

## 📋 Lecturer Task Management

### **Base URL:** `/api/v1/lecturer-tasks`

```http
GET    /api/v1/lecturer-tasks                              # Tasks của giảng viên
POST   /api/v1/lecturer-tasks                              # Tạo task
GET    /api/v1/lecturer-tasks/{task}                       # Chi tiết task
PUT    /api/v1/lecturer-tasks/{task}                       # Cập nhật task
DELETE /api/v1/lecturer-tasks/{task}                       # Xóa task
GET    /api/v1/lecturer-tasks/created                      # Tasks đã tạo
GET    /api/v1/lecturer-tasks/assigned                     # Tasks được giao
GET    /api/v1/lecturer-tasks/statistics                   # Thống kê giảng viên
PATCH  /api/v1/lecturer-tasks/{task}/assign                # Giao task
POST   /api/v1/lecturer-tasks/{task}/revoke                # Thu hồi task
POST   /api/v1/lecturer-tasks/recurring                    # Tạo task định kỳ
POST   /api/v1/lecturer-tasks/create-with-permissions      # Tạo task với quyền
POST   /api/v1/lecturer-tasks/{task}/process-files         # Xử lý files
```

---

## 📅 Lecturer Calendar

### **Base URL:** `/api/v1/lecturer-calendar`

```http
GET    /api/v1/lecturer-calendar/events                    # Events của giảng viên
GET    /api/v1/lecturer-calendar/events/by-date            # Events theo ngày
GET    /api/v1/lecturer-calendar/events/by-range           # Events theo khoảng
GET    /api/v1/lecturer-calendar/events/upcoming           # Events sắp tới
GET    /api/v1/lecturer-calendar/events/overdue            # Events quá hạn
GET    /api/v1/lecturer-calendar/events/count-by-status    # Đếm events theo trạng thái
GET    /api/v1/lecturer-calendar/reminders                 # Reminders
POST   /api/v1/lecturer-calendar/reminders                 # Tạo reminder
POST   /api/v1/lecturer-calendar/events                    # Tạo event
PUT    /api/v1/lecturer-calendar/events/{event}            # Cập nhật event
DELETE /api/v1/lecturer-calendar/events/{event}            # Xóa event
```

---

## 🏫 Lecturer Classes

### **Base URL:** `/api/v1/lecturer-classes`

```http
GET    /api/v1/lecturer-classes                    # Lớp của giảng viên
GET    /api/v1/lecturer-classes/{class}/students   # Sinh viên trong lớp
POST   /api/v1/lecturer-classes/{class}/announcements # Tạo thông báo lớp
```

---

## 👨‍🎓 STUDENT ROUTES (Chỉ dành cho Sinh viên)

### **Base URL:** `/api/v1`

**Middleware:** `jwt`, `student`

**Lưu ý:** Tất cả endpoints student tự động lấy `student_id` từ JWT token, không cần truyền trong request.

---

## 📋 Student Task Management

### **Base URL:** `/api/v1/student-tasks`

#### **1. Lấy danh sách tasks của sinh viên**
```http
GET    /api/v1/student-tasks
```

**Query Parameters:**
- `page` (optional): Số trang (default: 1)
- `limit` (optional): Số items per page (default: 15)
- `status` (optional): Lọc theo trạng thái (pending, in_progress, completed, overdue)
- `priority` (optional): Lọc theo độ ưu tiên (low, medium, high, urgent)
- `class_id` (optional): Lọc theo lớp
- `date_from` (optional): Ngày bắt đầu (Y-m-d)
- `date_to` (optional): Ngày kết thúc (Y-m-d)
- `search` (optional): Tìm kiếm theo tiêu đề/mô tả

**Response:**
```json
{
  "success": true,
  "message": "Student tasks retrieved successfully",
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4
  }
}
```

#### **2. Lấy chi tiết task**
```http
GET    /api/v1/student-tasks/{task}
```

**Parameters:**
- `task` (path): ID của task

**Response:**
```json
{
  "success": true,
  "message": "Task retrieved successfully",
  "data": {
    "id": 1,
    "title": "Assignment 1",
    "description": "...",
    "deadline": "2025-02-15 23:59:59",
    "status": "pending",
    "priority": "high",
    "created_at": "...",
    "files": [...],
    "submission": {...}
  }
}
```

#### **3. Lấy tasks đang chờ xử lý**
```http
GET    /api/v1/student-tasks/pending
```

**Query Parameters:** Giống như endpoint `GET /api/v1/student-tasks`

**Response:** Trả về danh sách tasks có status `pending` hoặc `in_progress`

#### **4. Lấy tasks đã nộp**
```http
GET    /api/v1/student-tasks/submitted
```

**Query Parameters:** Giống như endpoint `GET /api/v1/student-tasks`

**Response:** Trả về danh sách tasks đã được submit

#### **5. Lấy tasks quá hạn**
```http
GET    /api/v1/student-tasks/overdue
```

**Query Parameters:** Giống như endpoint `GET /api/v1/student-tasks`

**Response:** Trả về danh sách tasks quá deadline

#### **6. Lấy thống kê tasks của sinh viên**
```http
GET    /api/v1/student-tasks/statistics
```

**Query Parameters:**
- `date_from` (optional): Ngày bắt đầu thống kê
- `date_to` (optional): Ngày kết thúc thống kê
- `class_id` (optional): Lọc theo lớp

**Response:**
```json
{
  "success": true,
  "message": "Student statistics retrieved successfully",
  "data": {
    "total_tasks": 50,
    "pending_tasks": 10,
    "in_progress_tasks": 5,
    "completed_tasks": 30,
    "overdue_tasks": 5,
    "completion_rate": 60.0,
    "average_completion_time": "2.5 days"
  }
}
```

#### **7. Submit task**
```http
POST   /api/v1/student-tasks/{task}/submit
```

**Parameters:**
- `task` (path): ID của task

**Request Body:**
```json
{
  "content": "Nội dung bài nộp",  // Required: Nội dung bài nộp
  "files": [1, 2, 3],              // Optional: IDs của files đã upload
  "notes": "Ghi chú thêm"          // Optional: Ghi chú bổ sung
}
```

**Hoặc sử dụng field names đầy đủ:**
```json
{
  "submission_content": "Nội dung bài nộp",  // Required
  "submission_files": [1, 2, 3],             // Optional
  "submission_notes": "Ghi chú thêm"         // Optional
}
```

**Lưu ý:** 
- Field `content` hoặc `submission_content` là **bắt buộc**
- Field `files` hoặc `submission_files` là mảng IDs của files đã upload (optional)
- Field `notes` hoặc `submission_notes` là optional

**Response:**
```json
{
  "success": true,
  "message": "Task submitted successfully",
  "data": {
    "id": 1,
    "task_id": 123,
    "student_id": 456,
    "submission_content": "Nội dung bài nộp",
    "submission_files": [1, 2, 3],
    "submission_notes": null,
    "submitted_at": "2025-01-27 10:30:00",
    "status": "submitted"
  }
}
```

**Error Response (400 - Validation Error):**
```json
{
  "success": false,
  "message": "Failed to submit task: Validation failed: Submission content is required"
}
```

#### **8. Cập nhật bài nộp**
```http
PUT    /api/v1/student-tasks/{task}/submission
```

**Parameters:**
- `task` (path): ID của task

**Request Body:**
```json
{
  "content": "Nội dung bài nộp đã cập nhật",  // Required
  "files": [1, 2, 3],                          // Optional: IDs của files
  "notes": "Ghi chú cập nhật"                 // Optional
}
```

**Hoặc sử dụng field names đầy đủ:**
```json
{
  "submission_content": "Nội dung bài nộp đã cập nhật",
  "submission_files": [1, 2, 3],
  "submission_notes": "Ghi chú cập nhật"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Task submission updated successfully",
  "data": {
    "id": 1,
    "task_id": 123,
    "submission_content": "Nội dung bài nộp đã cập nhật",
    "submission_files": [1, 2, 3],
    "updated_at": "2025-01-27 11:00:00"
  }
}
```

#### **9. Lấy bài nộp**
```http
GET    /api/v1/student-tasks/{task}/submission
```

**Parameters:**
- `task` (path): ID của task

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Task submission retrieved successfully",
  "data": {
    "id": 1,
    "task_id": 123,
    "student_id": 456,
    "content": "Nội dung bài nộp",
    "submission_content": "Nội dung bài nộp",  // Alias
    "submitted_at": "2025-01-27 10:30:00",
    "updated_at": "2025-01-27 11:00:00",
    "status": "submitted",
    "files": [  // ✅ Luôn là array, không phải null
      {
        "id": 1,
        "file_name": "assignment.pdf",
        "name": "assignment.pdf",  // Alias
        "file_path": "tasks/123/assignment.pdf",
        "file_url": "http://localhost:8082/storage/tasks/123/assignment.pdf",
        "file_size": 1024000,
        "size": 1024000,  // Alias
        "mime_type": "application/pdf",
        "created_at": "2025-01-27 10:30:00"
      }
    ],
    "grade": {  // null nếu chưa chấm
      "score": 8.5,
      "feedback": "Tốt",
      "graded_at": "2025-01-27 15:00:00",
      "graded_by": {
        "id": 2,
        "name": "Thầy Nguyễn Văn A"
      }
    }
  }
}
```

**Not Found Response (404) - Chưa có submission:**
```json
{
  "success": false,
  "message": "Chưa có bài nộp cho task này",
  "data": null
}
```

**⚠️ Lưu ý quan trọng:**
- **404** = Chưa có submission (không phải lỗi) → Frontend nên handle như "chưa nộp bài"
- **500** = Lỗi hệ thống thực sự → Frontend nên hiển thị error message
- `files` luôn là array (không phải null), có thể là `[]` nếu không có files
- Files được load từ `submission_files` field (array IDs) trong database

#### **10. Upload file cho task**
```http
POST   /api/v1/student-tasks/{task}/upload-file
```

**Parameters:**
- `task` (path): ID của task

**Request Body (multipart/form-data):**
- `file` (required): File cần upload

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 1,
    "task_id": 123,
    "file_name": "assignment.pdf",
    "file_path": "storage/tasks/123/assignment.pdf",
    "file_size": 1024000,
    "mime_type": "application/pdf",
    "uploaded_at": "2025-01-27 10:30:00"
  }
}
```

#### **11. Xóa file của task**
```http
DELETE /api/v1/student-tasks/{task}/files/{file}
```

**Parameters:**
- `task` (path): ID của task
- `file` (path): ID của file cần xóa

**Response:**
```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

#### **12. Lấy danh sách files của task**
```http
GET    /api/v1/student-tasks/{task}/files
```

**Parameters:**
- `task` (path): ID của task

**Response:**
```json
{
  "success": true,
  "message": "Task files retrieved successfully",
  "data": [
    {
      "id": 1,
      "file_name": "assignment.pdf",
      "file_path": "storage/tasks/123/assignment.pdf",
      "file_size": 1024000,
      "mime_type": "application/pdf",
      "uploaded_at": "2025-01-27 10:30:00"
    }
  ]
}
```

---

## 📅 Student Calendar

### **Base URL:** `/api/v1/student-calendar`

#### **1. Lấy events của sinh viên**
```http
GET    /api/v1/student-calendar/events
```

**Query Parameters:**
- `page` (optional): Số trang (default: 1)
- `limit` (optional): Số items per page (default: 15)
- `status` (optional): Lọc theo trạng thái
- `type` (optional): Lọc theo loại event
- `date_from` (optional): Ngày bắt đầu
- `date_to` (optional): Ngày kết thúc

**Response:**
```json
{
  "success": true,
  "message": "Student events retrieved successfully",
  "data": [...],
  "pagination": {...}
}
```

#### **2. Lấy events theo ngày**
```http
GET    /api/v1/student-calendar/events/by-date
```

**Query Parameters:**
- `date` (required): Ngày cần lấy events (Y-m-d)

**Response:**
```json
{
  "success": true,
  "message": "Events by date retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Deadline Assignment 1",
      "type": "task_deadline",
      "date": "2025-01-27",
      "time": "23:59:59",
      "status": "pending"
    }
  ]
}
```

#### **3. Lấy events theo khoảng thời gian**
```http
GET    /api/v1/student-calendar/events/by-range
```

**Query Parameters:**
- `start_date` (required): Ngày bắt đầu (Y-m-d)
- `end_date` (required): Ngày kết thúc (Y-m-d)

**Response:**
```json
{
  "success": true,
  "message": "Events by range retrieved successfully",
  "data": [...]
}
```

#### **4. Lấy events sắp tới**
```http
GET    /api/v1/student-calendar/events/upcoming
```

**Query Parameters:**
- `limit` (optional): Số lượng events (default: 10)

**Response:**
```json
{
  "success": true,
  "message": "Upcoming events retrieved successfully",
  "data": [...]
}
```

#### **5. Lấy events quá hạn**
```http
GET    /api/v1/student-calendar/events/overdue
```

**Response:**
```json
{
  "success": true,
  "message": "Overdue events retrieved successfully",
  "data": [...]
}
```

#### **6. Đếm events theo trạng thái**
```http
GET    /api/v1/student-calendar/events/count-by-status
```

**Response:**
```json
{
  "success": true,
  "message": "Events count by status retrieved successfully",
  "data": {
    "pending": 5,
    "in_progress": 3,
    "completed": 20,
    "overdue": 2
  }
}
```

#### **7. Lấy reminders của sinh viên**
```http
GET    /api/v1/student-calendar/reminders
```

**Query Parameters:**
- `page` (optional): Số trang
- `limit` (optional): Số items per page
- `status` (optional): Lọc theo trạng thái (active, completed, cancelled)

**Response:**
```json
{
  "success": true,
  "message": "Student reminders retrieved successfully",
  "data": [...],
  "pagination": {...}
}
```

#### **8. Tạo reminder**
```http
POST   /api/v1/student-calendar/setReminder
```

**Request Body:**
```json
{
  "event_id": 123,
  "event_type": "task_deadline",
  "reminder_time": "2025-01-27 09:00:00",
  "reminder_type": "email",  // email, sms, push
  "message": "Nhắc nhở deadline sắp tới"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reminder set successfully",
  "data": {
    "id": 1,
    "event_id": 123,
    "reminder_time": "2025-01-27 09:00:00",
    "status": "active",
    "created_at": "2025-01-27 08:00:00"
  }
}
```

---

## 🏫 Student Class

### **Base URL:** `/api/v1/student-class`

#### **1. Lấy thông tin lớp học**
```http
GET    /api/v1/student-class
```

**Response:**
```json
{
  "success": true,
  "message": "Student class retrieved successfully",
  "data": {
    "id": 1,
    "name": "CNTT2024A",
    "department": {
      "id": 1,
      "name": "Công nghệ thông tin"
    },
    "total_students": 50,
    "total_lecturers": 3,
    "academic_year": "2024-2025"
  }
}
```

#### **2. Lấy danh sách bạn cùng lớp**
```http
GET    /api/v1/student-class/classmates
```

**Query Parameters:**
- `page` (optional): Số trang
- `limit` (optional): Số items per page
- `search` (optional): Tìm kiếm theo tên, mã sinh viên

**Response:**
```json
{
  "success": true,
  "message": "Classmates retrieved successfully",
  "data": [
    {
      "id": 2,
      "student_code": "SV001",
      "name": "Nguyễn Văn A",
      "email": "sv001@example.com"
    }
  ],
  "pagination": {...}
}
```

#### **3. Lấy danh sách giảng viên của lớp**
```http
GET    /api/v1/student-class/lecturers
```

**Response:**
```json
{
  "success": true,
  "message": "Class lecturers retrieved successfully",
  "data": [
    {
      "id": 1,
      "lecturer_code": "GV001",
      "name": "Thầy Nguyễn Văn B",
      "email": "gv001@example.com",
      "subjects": ["Lập trình Web", "Cơ sở dữ liệu"]
    }
  ]
}
```

#### **4. Lấy thông báo của lớp**
```http
GET    /api/v1/student-class/announcements
```

**Query Parameters:**
- `page` (optional): Số trang
- `limit` (optional): Số items per page
- `date_from` (optional): Lọc từ ngày
- `date_to` (optional): Lọc đến ngày

**Response:**
```json
{
  "success": true,
  "message": "Class announcements retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Thông báo lịch thi",
      "content": "Lịch thi sẽ được công bố vào tuần tới...",
      "author": {
        "id": 1,
        "name": "Thầy Nguyễn Văn B"
      },
      "created_at": "2025-01-27 10:00:00",
      "is_important": true
    }
  ],
  "pagination": {...}
}
```

#### **5. Lấy lịch học của lớp**
```http
GET    /api/v1/student-class/schedule
```

**Query Parameters:**
- `week` (optional): Tuần học (1-52)
- `semester` (optional): Học kỳ (1, 2)
- `academic_year` (optional): Năm học

**Response:**
```json
{
  "success": true,
  "message": "Class schedule retrieved successfully",
  "data": {
    "week": 5,
    "semester": 1,
    "academic_year": "2024-2025",
    "schedule": [
      {
        "day": "Monday",
        "time": "07:00-09:00",
        "subject": "Lập trình Web",
        "lecturer": "Thầy Nguyễn Văn B",
        "room": "A101"
      }
    ]
  }
}
```

#### **6. Lấy thông tin điểm danh**
```http
GET    /api/v1/student-class/attendance
```

**Query Parameters:**
- `date_from` (optional): Ngày bắt đầu
- `date_to` (optional): Ngày kết thúc
- `subject_id` (optional): Lọc theo môn học

**Response:**
```json
{
  "success": true,
  "message": "Student attendance retrieved successfully",
  "data": {
    "total_sessions": 30,
    "attended_sessions": 25,
    "absent_sessions": 3,
    "late_sessions": 2,
    "attendance_rate": 83.33,
    "details": [
      {
        "date": "2025-01-20",
        "subject": "Lập trình Web",
        "status": "present",
        "time": "07:00-09:00"
      }
    ]
  }
}
```

---

## 🔧 ADMIN ROUTES (Chỉ dành cho Admin)

### **Base URL:** `/api/v1`

---

## 📋 Admin Task Management

### **Base URL:** `/api/v1/admin-tasks`

```http
GET    /api/v1/admin-tasks                    # Tất cả tasks
POST   /api/v1/admin-tasks                    # Tạo task
GET    /api/v1/admin-tasks/{id}               # Chi tiết task
PUT    /api/v1/admin-tasks/{id}               # Cập nhật task
PATCH  /api/v1/admin-tasks/{id}               # Cập nhật task (partial)
DELETE /api/v1/admin-tasks/{id}               # Xóa task
GET    /api/v1/admin-tasks/system-statistics  # Thống kê hệ thống
PATCH  /api/v1/admin-tasks/{id}/override-status # Ghi đè trạng thái
POST   /api/v1/admin-tasks/bulk-action        # Thao tác hàng loạt
```

---

## 📅 Admin Calendar

### **Base URL:** `/api/v1/calendar`

```http
GET    /api/v1/calendar/events                 # Tất cả events
GET    /api/v1/calendar/events/by-type         # Events theo loại
GET    /api/v1/calendar/events/recurring       # Events định kỳ
```

---

## 📊 Monitoring & Health

### **Base URL:** `/api/v1/monitoring`

```http
GET    /api/v1/monitoring/metrics              # Metrics hệ thống
GET    /api/v1/monitoring/health               # Health check
GET    /api/v1/monitoring/dashboard            # Dashboard monitoring
POST   /api/v1/monitoring/alerts/acknowledge   # Xác nhận alert
GET    /api/v1/monitoring/logs                 # Logs hệ thống
POST   /api/v1/monitoring/maintenance          # Bảo trì hệ thống
```

---

## 💾 Cache Management

### **Base URL:** `/api/v1/cache`

```http
GET    /api/v1/cache/health                    # Health cache
POST   /api/v1/cache/invalidate/student        # Invalidate cache sinh viên
POST   /api/v1/cache/invalidate/lecturer       # Invalidate cache giảng viên
POST   /api/v1/cache/invalidate/department     # Invalidate cache khoa
POST   /api/v1/cache/invalidate/class          # Invalidate cache lớp
POST   /api/v1/cache/invalidate/bulk           # Invalidate cache hàng loạt
POST   /api/v1/cache/invalidate/all            # Invalidate tất cả cache
```

---

## 📝 Common Response Format

### **Success Response:**
```json
{
  "success": true,
  "data": {...},
  "message": "Operation completed successfully"
}
```

### **Error Response:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": {...} // Validation errors (optional)
}
```

### **Pagination Response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7,
    "has_more": true
  }
}
```

---

## 🔧 Query Parameters

### **Common Parameters:**
- `page` (optional): Số trang (default: 1)
- `limit` (optional): Số items per page (default: 15)
- `start_date` (optional): Ngày bắt đầu (Y-m-d)
- `end_date` (optional): Ngày kết thúc (Y-m-d)
- `status` (optional): Trạng thái filter
- `priority` (optional): Độ ưu tiên filter
- `sort` (optional): Sắp xếp (created_at, deadline, priority)
- `order` (optional): Thứ tự (asc, desc)

### **Task Parameters:**
- `creator_type` (optional): Loại người tạo (student, lecturer, admin)
- `receiver_type` (optional): Loại người nhận (student, lecturer, admin)
- `class_id` (optional): ID lớp
- `department_id` (optional): ID khoa

---

## 🔧 Usage Examples

### **Frontend Integration Examples:**

#### **1. Dashboard Data:**
```javascript
// Get dashboard summary
const dashboardData = await fetch('/api/v1/reports/dashboard-summary', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
}).then(res => res.json());
```

#### **2. Statistics Chart:**
```javascript
// Get task completion rate for chart
const completionData = await fetch('/api/v1/statistics/completion-rate?period=month', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(res => res.json());
```

#### **3. Export Report:**
```javascript
// Export Excel report
const exportUrl = '/api/v1/reports/export/excel?type=comprehensive&start_date=2025-01-01&end_date=2025-01-31';
window.open(exportUrl, '_blank');
```

#### **4. Task Management:**
```javascript
// Create new task (Lecturer)
const newTask = await fetch('/api/v1/lecturer-tasks', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: 'New Assignment',
    description: 'Complete the project',
    deadline: '2025-02-15 23:59:59',
    priority: 'high',
    receivers: [
      {id: 123, type: 'student'},
      {id: 124, type: 'student'}
    ]
  })
}).then(res => res.json());
```

#### **5. File Upload:**
```javascript
// Upload files to task
const formData = new FormData();
formData.append('files[]', file1);
formData.append('files[]', file2);

const uploadResponse = await fetch('/api/v1/tasks/123/files', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});
```

#### **6. Task Submission:**
```javascript
// Submit task
const submissionData = {
  content: 'Here is my submission...',
  files: [/* file objects */]
};

const submitResponse = await fetch('/api/v1/tasks/123/submit', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(submissionData)
});
```

#### **7. Student Task Management:**
```javascript
// Get student tasks
const studentTasks = await fetch('/api/v1/student-tasks?status=pending&limit=10', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(res => res.json());

// Submit task (Student) - Format đơn giản
const submitTask = await fetch('/api/v1/student-tasks/123/submit', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    content: 'Bài nộp của tôi...',  // Required
    files: [1, 2, 3],                // Optional: IDs của files đã upload
    notes: 'Ghi chú thêm'            // Optional
  })
}).then(res => res.json());

// Hoặc sử dụng format đầy đủ
const submitTaskFull = await fetch('/api/v1/student-tasks/123/submit', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    submission_content: 'Bài nộp của tôi...',
    submission_files: [1, 2, 3],
    submission_notes: 'Ghi chú thêm'
  })
}).then(res => res.json());

// Upload file for task
const formData = new FormData();
formData.append('file', fileInput.files[0]);

const uploadFile = await fetch('/api/v1/student-tasks/123/upload-file', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
}).then(res => res.json());

// Get student statistics
const statistics = await fetch('/api/v1/student-tasks/statistics', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(res => res.json());
```

#### **8. Student Calendar:**
```javascript
// Get student events
const events = await fetch('/api/v1/student-calendar/events?date_from=2025-01-01&date_to=2025-01-31', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(res => res.json());

// Set reminder
const reminder = await fetch('/api/v1/student-calendar/setReminder', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    event_id: 123,
    event_type: 'task_deadline',
    reminder_time: '2025-01-27 09:00:00',
    reminder_type: 'email',
    message: 'Nhắc nhở deadline sắp tới'
  })
}).then(res => res.json());
```

#### **9. Student Class:**
```javascript
// Get class information
const classInfo = await fetch('/api/v1/student-class', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(res => res.json());

// Get classmates
const classmates = await fetch('/api/v1/student-class/classmates?page=1&limit=20', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(res => res.json());

// Get class schedule
const schedule = await fetch('/api/v1/student-class/schedule?week=5&semester=1', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(res => res.json());
```

---

## 🚀 Quick Start for Frontend

1. **Authentication:** Đăng nhập để lấy JWT token
2. **Dashboard:** Sử dụng `/reports/dashboard-summary` để lấy dữ liệu dashboard
3. **Statistics:** Sử dụng các endpoints `/statistics/*` cho charts và analytics
4. **Task Management:** Sử dụng `/tasks/*` cho CRUD operations
5. **Reports:** Sử dụng `/reports/export/*` để xuất báo cáo
6. **Real-time:** Sử dụng WebSocket hoặc polling cho updates

---

## 📊 Endpoint Summary by Category

| **Category** | **Common** | **Lecturer** | **Student** | **Admin** | **Total** |
|--------------|------------|--------------|-------------|-----------|-----------|
| **Task Management** | 13 | 12 | 12 | 8 | 45 |
| **Statistics** | 12 | 0 | 0 | 0 | 12 |
| **Reports** | 11 | 0 | 0 | 0 | 11 |
| **Calendar** | 0 | 11 | 8 | 3 | 22 |
| **Dependencies** | 12 | 0 | 0 | 0 | 12 |
| **Reminders** | 6 | 0 | 0 | 0 | 6 |
| **Classes** | 0 | 3 | 6 | 0 | 9 |
| **Monitoring** | 0 | 0 | 0 | 6 | 6 |
| **Cache** | 0 | 0 | 0 | 7 | 7 |
| **Email** | 1 | 0 | 0 | 0 | 1 |
| **TOTAL** | **55** | **26** | **26** | **24** | **131** |

---

## 📋 Student Endpoints Chi Tiết

### **Student Task Management (12 endpoints):**
1. `GET /api/v1/student-tasks` - Lấy danh sách tasks
2. `GET /api/v1/student-tasks/{task}` - Chi tiết task
3. `GET /api/v1/student-tasks/pending` - Tasks chờ xử lý
4. `GET /api/v1/student-tasks/submitted` - Tasks đã nộp
5. `GET /api/v1/student-tasks/overdue` - Tasks quá hạn
6. `GET /api/v1/student-tasks/statistics` - Thống kê
7. `POST /api/v1/student-tasks/{task}/submit` - Submit task
8. `PUT /api/v1/student-tasks/{task}/submission` - Cập nhật bài nộp
9. `GET /api/v1/student-tasks/{task}/submission` - Lấy bài nộp
10. `POST /api/v1/student-tasks/{task}/upload-file` - Upload file
11. `DELETE /api/v1/student-tasks/{task}/files/{file}` - Xóa file
12. `GET /api/v1/student-tasks/{task}/files` - Lấy danh sách files

### **Student Calendar (8 endpoints):**
1. `GET /api/v1/student-calendar/events` - Events của sinh viên
2. `GET /api/v1/student-calendar/events/by-date` - Events theo ngày
3. `GET /api/v1/student-calendar/events/by-range` - Events theo khoảng
4. `GET /api/v1/student-calendar/events/upcoming` - Events sắp tới
5. `GET /api/v1/student-calendar/events/overdue` - Events quá hạn
6. `GET /api/v1/student-calendar/events/count-by-status` - Đếm events theo trạng thái
7. `GET /api/v1/student-calendar/reminders` - Reminders
8. `POST /api/v1/student-calendar/setReminder` - Tạo reminder

### **Student Class (6 endpoints):**
1. `GET /api/v1/student-class` - Thông tin lớp
2. `GET /api/v1/student-class/classmates` - Bạn cùng lớp
3. `GET /api/v1/student-class/lecturers` - Giảng viên lớp
4. `GET /api/v1/student-class/announcements` - Thông báo lớp
5. `GET /api/v1/student-class/schedule` - Lịch học
6. `GET /api/v1/student-class/attendance` - Điểm danh

---

---

## 🧪 Hướng dẫn Test với Postman

### **1. Setup Postman Request**

#### **Bước 1: Authentication**
1. Tạo request mới trong Postman
2. Tab **Authorization** → Chọn **Bearer Token**
3. Nhập JWT token vào field **Token**

#### **Bước 2: Headers**
Đảm bảo có các headers sau:
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

### **2. Test Submit Task API**

#### **Request:**
```
POST http://localhost:8082/api/v1/student-tasks/{task_id}/submit
```

**Thay `{task_id}` bằng ID task thực tế, ví dụ:**
```
POST http://localhost:8082/api/v1/student-tasks/121/submit
```

#### **Body (raw JSON):**
```json
{
  "content": "Đây là bài nộp của tôi cho task này",
  "files": [1, 2, 3],
  "notes": "Ghi chú thêm nếu có"
}
```

**Hoặc format đầy đủ:**
```json
{
  "submission_content": "Đây là bài nộp của tôi cho task này",
  "submission_files": [1, 2, 3],
  "submission_notes": "Ghi chú thêm nếu có"
}
```

#### **Lưu ý quan trọng:**
- ✅ Field `content` hoặc `submission_content` là **BẮT BUỘC**
- ✅ Field `files` hoặc `submission_files` là mảng IDs của files đã upload (phải upload file trước)
- ⚠️ Nếu không có `content`, sẽ nhận lỗi: `"Validation failed: Submission content is required"`

### **3. Test Upload File API (Bước trước khi Submit)**

#### **Request:**
```
POST http://localhost:8082/api/v1/student-tasks/{task_id}/upload-file
```

#### **Body (form-data):**
- Key: `file` (type: File)
- Value: Chọn file từ máy tính

#### **Response sẽ trả về file ID:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 1,
    "file_name": "assignment.pdf",
    ...
  }
}
```

**Lưu file ID này để dùng trong submit task (`files: [1]`)**

### **4. Test Update Submission API**

#### **Request:**
```
PUT http://localhost:8082/api/v1/student-tasks/{task_id}/submission
```

#### **Body (raw JSON):**
```json
{
  "content": "Nội dung đã cập nhật",
  "files": [1, 2, 3],
  "notes": "Ghi chú cập nhật"
}
```

### **5. Test Get Submission API**

#### **Request:**
```
GET http://localhost:8082/api/v1/student-tasks/{task_id}/submission
```

Không cần body, chỉ cần JWT token trong Authorization header.

### **6. Common Errors và Solutions**

| **Error** | **Nguyên nhân** | **Giải pháp** |
|-----------|----------------|---------------|
| `500 - Validation failed: Submission content is required` | Thiếu field `content` | Thêm `"content": "..."` vào body |
| `401 - Unauthorized` | Thiếu hoặc sai JWT token | Kiểm tra lại token trong Authorization header |
| `404 - Task not found` | Task ID không tồn tại | Kiểm tra lại task_id trong URL |
| `403 - Access denied` | Task không được giao cho student này | Đảm bảo task được assign cho student đang đăng nhập |

### **7. Test Flow Hoàn Chỉnh**

**Flow đề xuất:**
1. ✅ `GET /api/v1/student-tasks` - Lấy danh sách tasks
2. ✅ `GET /api/v1/student-tasks/{task_id}` - Xem chi tiết task
3. ✅ `POST /api/v1/student-tasks/{task_id}/upload-file` - Upload file (lặp lại nếu nhiều files)
4. ✅ `POST /api/v1/student-tasks/{task_id}/submit` - Submit task với content và file IDs
5. ✅ `GET /api/v1/student-tasks/{task_id}/submission` - Kiểm tra submission đã submit

---

## 📋 Breaking Changes & Frontend Update Guide

### **🔄 Latest Updates (2025-01-27)**

**Priority:** 🔴 HIGH - Frontend cần update ngay để fix lỗi 500

---

### **✅ Các Thay Đổi Chính**

#### **1. GET Submission - Trả về 404 thay vì 500**

**Trước đây:**
- Khi không có submission → Trả về 500 Internal Server Error
- Frontend phải handle 500 như một trường hợp "chưa có submission"

**Bây giờ:**
- Khi không có submission → Trả về **404 Not Found** (đúng chuẩn HTTP)
- Frontend nên handle 404 như "chưa có submission"

#### **2. Files được load từ submission_files**

**Trước đây:**
- Files có thể không được load hoặc load sai
- Response có thể thiếu files array

**Bây giờ:**
- Files được load từ `submission_files` field (array IDs)
- Files luôn là array (không phải null)
- Files chỉ chứa những files đã được submit cùng với submission

#### **3. Response Format Cải Thiện**

- Thêm aliases cho compatibility (`file_name`/`name`, `file_size`/`size`)
- Luôn có `files` array (không phải null)
- Grade được format đầy đủ nếu có

---

### **🔧 Frontend Code Changes**

#### **1. Update GET Submission Handler**

**Before (Sai):**
```typescript
// ❌ Wrong: Handle 500 như "chưa có submission"
async function getSubmission(taskId: number) {
  try {
    const response = await fetch(`/api/v1/student-tasks/${taskId}/submission`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (response.status === 500) {
      return null; // Assume no submission
    }
    
    const data = await response.json();
    return data.data;
  } catch (error) {
    return null;
  }
}
```

**After (Đúng):**
```typescript
// ✅ Correct: Handle 404 như "chưa có submission"
async function getSubmission(taskId: number) {
  try {
    const response = await fetch(`/api/v1/student-tasks/${taskId}/submission`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    // ✅ Handle 404 như "chưa có submission"
    if (response.status === 404) {
      return null;
    }
    
    // ✅ Handle 500 như lỗi hệ thống thực sự
    if (!response.ok) {
      throw new Error('Failed to load submission');
    }
    
    const data = await response.json();
    
    // ✅ Đảm bảo files luôn là array
    return {
      ...data.data,
      files: data.data?.files || []
    };
  } catch (error) {
    console.error('Error loading submission:', error);
    throw error;
  }
}
```

#### **2. Update Component Logic**

**Before:**
```typescript
// ❌ Wrong: Assume files có thể null
const [files, setFiles] = useState(null);

async function loadSubmission() {
  try {
    const data = await getSubmission(taskId);
    setFiles(data?.files || []);
  } catch (error) {
    if (error.status === 500) {  // ❌ Wrong
      setFiles([]);
    }
  }
}
```

**After:**
```typescript
// ✅ Correct: Files luôn là array
const [files, setFiles] = useState([]); // ✅ Empty array

async function loadSubmission() {
  try {
    const data = await getSubmission(taskId);
    
    if (!data) {
      // 404 - Chưa có submission
      setFiles([]);
      return;
    }
    
    setFiles(data.files || []);
  } catch (error) {
    // ✅ 500 là lỗi hệ thống thực sự
    console.error('Failed to load submission:', error);
    showError('Không thể tải bài nộp. Vui lòng thử lại.');
  }
}
```

#### **3. Update File Display Logic**

**Before:**
```typescript
// ❌ Wrong: Check null
{files && files.length > 0 && (
  <FileList files={files} />
)}
```

**After:**
```typescript
// ✅ Correct: Files luôn là array
{files.length > 0 && (
  <FileList files={files} />
)}

{files.length === 0 && (
  <EmptyState message="Chưa có file nào được nộp" />
)}
```

---

### **📝 Checklist cho Frontend**

- [ ] **GET Submission API Call**
  - [ ] Handle 404 như "chưa có submission" (không phải error)
  - [ ] Handle 500 như lỗi hệ thống thực sự
  - [ ] Đảm bảo files luôn là array (không phải null)

- [ ] **Component State**
  - [ ] Initialize files state là `[]` thay vì `null`
  - [ ] Remove null checks không cần thiết

- [ ] **File Display**
  - [ ] Check `files.length > 0` thay vì `files && files.length > 0`
  - [ ] Hiển thị empty state khi `files.length === 0`

- [ ] **Error Messages**
  - [ ] Hiển thị message phù hợp cho 404 vs 500
  - [ ] Log errors đầy đủ để debug

- [ ] **TypeScript Types** (nếu dùng)
  ```typescript
  interface Submission {
    id: number;
    task_id: number;
    student_id: number;
    content: string;
    submission_content: string;  // Alias
    submitted_at: string;
    updated_at: string;
    status: string;
    files: File[];  // ✅ Array, không phải File[] | null
    grade: Grade | null;
  }
  
  interface File {
    id: number;
    file_name: string;
    name: string;  // Alias
    file_path: string;
    file_url: string;
    file_size: number;
    size: number;  // Alias
    mime_type: string | null;
    created_at: string;
  }
  ```

---

### **🧪 Test Cases**

**Test 1: Chưa có submission**
```
GET /api/v1/student-tasks/999/submission
Expected: 404 Not Found
Response: {
  "success": false,
  "message": "Chưa có bài nộp cho task này",
  "data": null
}
// Frontend: Handle như "chưa có submission" (không phải error)
```

**Test 2: Có submission với files**
```
GET /api/v1/student-tasks/119/submission
Expected: 200 OK
Response: {
  "success": true,
  "data": {
    "files": [{ "id": 1, "file_name": "assignment.pdf", ... }]
  }
}
// Frontend: Display files list, files luôn là array
```

**Test 3: Có submission không có files**
```
GET /api/v1/student-tasks/120/submission
Expected: 200 OK
Response: {
  "success": true,
  "data": {
    "files": []  // Empty array, không phải null
  }
}
// Frontend: Show empty files state, không crash
```

---

### **🚀 Migration Steps**

1. **Backup current code**
2. **Update GET submission handler** (handle 404 đúng cách)
3. **Update component state** (files = [] thay vì null)
4. **Update file display logic** (remove null checks)
5. **Test với các scenarios:**
   - Chưa có submission (404)
   - Có submission với files (200)
   - Có submission không có files (200)
   - Lỗi hệ thống (500)

---

### **✅ Summary**

**Những gì đã thay đổi:**
- ✅ 404 thay vì 500 khi không có submission
- ✅ Files luôn là array (không phải null)
- ✅ Files được load từ submission_files IDs
- ✅ Response format có aliases cho compatibility

**Frontend cần làm:**
- ✅ Handle 404 như "chưa có submission"
- ✅ Handle 500 như lỗi hệ thống
- ✅ Đảm bảo files luôn là array
- ✅ Remove null checks không cần thiết

---

---

## 🐛 Debugging Submission Files Issue

### **Vấn Đề: Files không hiện sau khi submit**

**Triệu chứng:**
- File upload thành công
- Submit thành công
- Nhưng GET submission không có files

### **🔍 Debugging Steps**

#### **1. Kiểm tra Logs**

```bash
# Xem logs khi submit
tail -f storage/logs/laravel.log | grep "Submitting task"

# Xem logs khi load submission
tail -f storage/logs/laravel.log | grep "Loading submission files"
```

**Expected logs:**
```
Submitting task: {
  "task_id": 119,
  "student_id": 1,
  "submission_files": [1, 2],  // ✅ Phải có file IDs
  "submission_files_type": "array"
}

Loading submission files: {
  "submission_files_raw": "[1,2]",  // Raw từ DB
  "submission_files_casted": [1, 2],  // Casted thành array
  "file_ids_count": 2
}

Files found: {
  "file_ids_requested": [1, 2],
  "files_found_count": 2
}
```

#### **2. Kiểm tra Database**

```sql
-- Kiểm tra submission có files không
SELECT 
    id,
    task_id,
    student_id,
    submission_files,  -- Phải là JSON: [1] hoặc [1,2,3]
    submitted_at
FROM task_submissions
WHERE task_id = 119
ORDER BY id DESC
LIMIT 1;

-- Kiểm tra files có tồn tại không
SELECT id, task_id, name, path
FROM task_file
WHERE id IN (1, 2) AND task_id = 119;
```

#### **3. Test Flow với Script**

**Sử dụng script test tự động:**
```bash
# Test với file upload
./Modules/Task/test_submission.sh 119 "your_jwt_token" test.pdf

# Test với file ID có sẵn
./Modules/Task/test_submission.sh 119 "your_jwt_token"
# Script sẽ hỏi file ID, nhập: 1
```

**Hoặc test thủ công:**

**Bước 1: Upload file**
```bash
POST /api/v1/student-tasks/119/upload-file
→ Lưu file_id từ response (ví dụ: file_id = 1)
```

**Bước 2: Submit với file ID**
```bash
POST /api/v1/student-tasks/119/submit
Body: {
  "content": "Bài nộp",
  "files": [1]  // ← File ID từ bước 1
}
```

**Bước 3: Kiểm tra submission**
```bash
GET /api/v1/student-tasks/119/submission
→ Kiểm tra files array có file không
```

### **🔧 Common Issues**

| **Issue** | **Triệu chứng** | **Giải pháp** |
|-----------|------------------|---------------|
| Files không được gửi | `submission_files: []` trong log | Kiểm tra frontend có gửi `files: [1,2]` không |
| File IDs sai | `files_found_count: 0` | Kiểm tra file IDs có đúng không, file có tồn tại không |
| File thuộc task khác | `files_found_count: 0` | Kiểm tra file có đúng `task_id` không |
| submission_files null | `submission_files: null` trong DB | Kiểm tra controller có map `files` → `submission_files` không |

**📖 Xem chi tiết:** [TEST_SUBMISSION_FLOW.md](./TEST_SUBMISSION_FLOW.md)

---

**📚 Tài liệu này cung cấp đầy đủ 131+ API endpoints để frontend tích hợp hoàn chỉnh với Task Module!**

**✅ Đã được kiểm tra và cập nhật theo routes thực tế ngày: 2025-01-27**
**🔄 Latest Update: 2025-01-27 - Fixed 500 error, improved response format, added file submission debugging**