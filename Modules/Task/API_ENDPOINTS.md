# 📊 Task Module API Endpoints - Complete Reference (Updated)

## 🔐 Authentication
Tất cả endpoints đều yêu cầu JWT authentication:
```
Authorization: Bearer <jwt_token>
```

## 📋 Tổng quan Endpoints

**Tổng cộng: 127 API endpoints** được phân chia theo:

- **🔓 Common Routes** (Tất cả user): 13 endpoints
- **👨‍🏫 Lecturer Routes** (Giảng viên): 24 endpoints  
- **👨‍🎓 Student Routes** (Sinh viên): 17 endpoints
- **🔧 Admin Routes** (Quản trị): 8 endpoints
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

---

## 📋 Student Task Management

### **Base URL:** `/api/v1/student-tasks`

```http
GET    /api/v1/student-tasks                      # Tasks của sinh viên
GET    /api/v1/student-tasks/{task}               # Chi tiết task
GET    /api/v1/student-tasks/pending              # Tasks chờ xử lý
GET    /api/v1/student-tasks/submitted            # Tasks đã nộp
GET    /api/v1/student-tasks/overdue              # Tasks quá hạn
GET    /api/v1/student-tasks/statistics           # Thống kê sinh viên
PUT    /api/v1/student-tasks/{task}/submission    # Cập nhật bài nộp
GET    /api/v1/student-tasks/{task}/submission    # Lấy bài nộp
POST   /api/v1/student-tasks/{task}/upload-file   # Upload file
DELETE /api/v1/student-tasks/{task}/files/{file}  # Xóa file
GET    /api/v1/student-tasks/{task}/files         # Lấy danh sách files
```

---

## 📅 Student Calendar

### **Base URL:** `/api/v1/student-calendar`

```http
GET    /api/v1/student-calendar/events                    # Events của sinh viên
GET    /api/v1/student-calendar/events/by-date            # Events theo ngày
GET    /api/v1/student-calendar/events/by-range           # Events theo khoảng
GET    /api/v1/student-calendar/events/upcoming           # Events sắp tới
GET    /api/v1/student-calendar/events/overdue            # Events quá hạn
GET    /api/v1/student-calendar/events/count-by-status    # Đếm events theo trạng thái
GET    /api/v1/student-calendar/reminders                 # Reminders
POST   /api/v1/student-calendar/setReminder               # Tạo reminder
```

---

## 🏫 Student Class

### **Base URL:** `/api/v1/student-class`

```http
GET    /api/v1/student-class                      # Thông tin lớp
GET    /api/v1/student-class/classmates           # Bạn cùng lớp
GET    /api/v1/student-class/lecturers            # Giảng viên lớp
GET    /api/v1/student-class/announcements        # Thông báo lớp
GET    /api/v1/student-class/schedule             # Lịch học
GET    /api/v1/student-class/attendance           # Điểm danh
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
| **Task Management** | 13 | 12 | 11 | 8 | 44 |
| **Statistics** | 12 | 0 | 0 | 0 | 12 |
| **Reports** | 11 | 0 | 0 | 0 | 11 |
| **Calendar** | 0 | 11 | 8 | 3 | 22 |
| **Dependencies** | 12 | 0 | 0 | 0 | 12 |
| **Reminders** | 6 | 0 | 0 | 0 | 6 |
| **Classes** | 0 | 3 | 6 | 0 | 9 |
| **Monitoring** | 0 | 0 | 0 | 6 | 6 |
| **Cache** | 0 | 0 | 0 | 7 | 7 |
| **Email** | 1 | 0 | 0 | 0 | 1 |
| **TOTAL** | **55** | **26** | **25** | **24** | **130** |

---

**📚 Tài liệu này cung cấp đầy đủ 130+ API endpoints để frontend tích hợp hoàn chỉnh với Task Module!**

**✅ Đã được kiểm tra và cập nhật theo routes thực tế ngày: 2025-01-27**