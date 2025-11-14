# 📅 Phân Tích Các Chức Năng Calendar Hiện Tại

## 📋 Tổng Quan

Hệ thống Calendar hiện tại cung cấp các chức năng quản lý lịch và sự kiện dựa trên Tasks, được phân quyền theo 3 roles: **Admin**, **Lecturer**, và **Student**.

### 🎯 Kiến Trúc
- **Clean Architecture**: Controllers → Use Cases → Repositories → Models
- **Data Source**: Chủ yếu từ bảng `task` (deadline = event time)
- **Standalone Events**: Bảng `calendar` cho events độc lập (chỉ Lecturer có thể tạo)
- **Reminders**: Bảng `reminders` cho nhắc nhở (đang trong quá trình implement)

---

## 🔐 Phân Quyền Theo Role

### 👨‍💼 **ADMIN** - 10 Endpoints

#### ✅ Chức Năng Đã Implement

1. **Lấy Tất Cả Events** (`GET /api/v1/calendar/events/all`)
   - **Mô tả**: Lấy tất cả events trong hệ thống với pagination
   - **Query Params**: `page`, `per_page`
   - **Dữ liệu trả về**: 
     - Tất cả tasks (không filter theo user)
     - Pagination metadata
   - **Status**: ✅ Hoàn chỉnh

2. **Lấy Events Theo Loại** (`GET /api/v1/calendar/events/by-type`)
   - **Mô tả**: Lọc events theo priority/type
   - **Query Params**: `type` (required) - priority của task
   - **Dữ liệu trả về**: Tasks có priority = type
   - **Status**: ✅ Hoàn chỉnh

3. **Lấy Recurring Events** (`GET /api/v1/calendar/events/recurring`)
   - **Mô tả**: Lấy events lặp lại
   - **Dữ liệu trả về**: Empty array (mock)
   - **Status**: ⚠️ Chưa implement (TODO)

4. **Lấy Events Theo Ngày** (`GET /api/v1/calendar/events/by-date`)
   - **Mô tả**: Lấy events trong một ngày cụ thể
   - **Query Params**: `date` (Y-m-d)
   - **Dữ liệu trả về**: Tất cả tasks có deadline trong ngày
   - **Status**: ✅ Hoàn chỉnh

5. **Lấy Events Theo Khoảng Thời Gian** (`GET /api/v1/calendar/events/by-range`)
   - **Mô tả**: Lấy events trong khoảng thời gian
   - **Query Params**: `start`/`end` hoặc `start_date`/`end_date` (Y-m-d hoặc Y-m-d H:i:s)
   - **Dữ liệu trả về**: Tất cả tasks có deadline trong khoảng
   - **Status**: ✅ Hoàn chỉnh

6. **Lấy Events Sắp Tới** (`GET /api/v1/calendar/events/upcoming`)
   - **Mô tả**: Lấy events trong 30 ngày tới
   - **Query Params**: `limit` (optional, default: 10)
   - **Dữ liệu trả về**: Tasks có deadline từ bây giờ đến 30 ngày tới
   - **Status**: ✅ Hoàn chỉnh

7. **Lấy Events Quá Hạn** (`GET /api/v1/calendar/events/overdue`)
   - **Mô tả**: Lấy tasks đã quá deadline và chưa hoàn thành
   - **Dữ liệu trả về**: Tasks có deadline < now() và status != 'completed'
   - **Status**: ✅ Hoàn chỉnh

8. **Đếm Events Theo Status** (`GET /api/v1/calendar/events/count-by-status`)
   - **Mô tả**: Thống kê số lượng events theo trạng thái
   - **Dữ liệu trả về**: 
     ```json
     {
       "counts": {
         "total": 100,
         "pending": 20,
         "in_progress": 30,
         "completed": 40,
         "overdue": 5,
         "upcoming": 5
       }
     }
     ```
   - **Status**: ✅ Hoàn chỉnh

9. **Lấy Reminders** (`GET /api/v1/calendar/reminders`)
   - **Mô tả**: Lấy reminders của user
   - **Dữ liệu trả về**: Empty array (mock)
   - **Status**: ⚠️ Chưa implement đầy đủ (TODO)

10. **Tạo Reminder** (`POST /api/v1/calendar/reminders`)
    - **Mô tả**: Tạo reminder mới
    - **Request Body**: `title`, `remind_at`, `user_id`, `user_type`
    - **Dữ liệu trả về**: Mock reminder object
    - **Status**: ⚠️ Chưa implement đầy đủ (TODO)

---

### 👨‍🏫 **LECTURER** - 11 Endpoints

#### ✅ Chức Năng Đã Implement

1. **Lấy Events Của Lecturer** (`GET /api/v1/lecturer-calendar/events`)
   - **Mô tả**: Lấy tất cả events liên quan đến lecturer (tạo + được assign)
   - **Query Params**: 
     - `page`, `per_page` (pagination)
     - `status`, `priority` (filter)
     - `date_from`, `date_to` (date range)
     - `search` (tìm kiếm)
   - **Dữ liệu trả về**: 
     - Tasks lecturer tạo (`creator_id`, `creator_type = 'lecturer'`)
     - Tasks được assign cho lecturer (trong `receivers`)
     - Standalone calendar events (từ bảng `calendar`)
     - Pagination metadata
   - **Status**: ✅ Hoàn chỉnh

2. **Tạo Event Mới** (`POST /api/v1/lecturer-calendar/events`)
   - **Mô tả**: Tạo calendar event độc lập (không phải task)
   - **Request Body**: 
     - `title` (required)
     - `description` (optional)
     - `start_time` (required, Y-m-d H:i:s)
     - `end_time` (required, Y-m-d H:i:s)
     - `event_type` (optional, default: 'event')
   - **Dữ liệu trả về**: Created calendar event
   - **Status**: ✅ Hoàn chỉnh
   - **Lưu ý**: Chỉ tạo event trong bảng `calendar`, không tạo task

3. **Cập Nhật Event** (`PUT /api/v1/lecturer-calendar/events/{id}`)
   - **Mô tả**: Cập nhật calendar event (chỉ event của chính lecturer)
   - **Request Body**: Tương tự create event
   - **Permission**: Chỉ có thể update event do mình tạo
   - **Status**: ✅ Hoàn chỉnh

4. **Xóa Event** (`DELETE /api/v1/lecturer-calendar/events/{id}`)
   - **Mô tả**: Xóa calendar event (chỉ event của chính lecturer)
   - **Permission**: Chỉ có thể xóa event do mình tạo
   - **Status**: ✅ Hoàn chỉnh

5. **Lấy Events Theo Ngày** (`GET /api/v1/lecturer-calendar/events/by-date`)
   - **Mô tả**: Lấy events trong một ngày cụ thể
   - **Query Params**: `date` (Y-m-d)
   - **Dữ liệu trả về**: 
     - Tasks có deadline trong ngày (tạo + assigned)
     - Calendar events trong ngày
   - **Status**: ✅ Hoàn chỉnh

6. **Lấy Events Theo Khoảng Thời Gian** (`GET /api/v1/lecturer-calendar/events/by-range`)
   - **Mô tả**: Lấy events trong khoảng thời gian
   - **Query Params**: `start`, `end` (Y-m-d hoặc Y-m-d H:i:s)
   - **Dữ liệu trả về**: 
     - Tasks có deadline trong khoảng (tạo + assigned)
     - Calendar events trong khoảng
     - Được merge và sort theo thời gian
   - **Status**: ✅ Hoàn chỉnh

7. **Lấy Events Sắp Tới** (`GET /api/v1/lecturer-calendar/events/upcoming`)
   - **Mô tả**: Lấy events trong 30 ngày tới
   - **Query Params**: `limit` (optional, default: 10)
   - **Dữ liệu trả về**: Tasks có deadline từ bây giờ đến 30 ngày tới
   - **Status**: ✅ Hoàn chỉnh

8. **Lấy Events Quá Hạn** (`GET /api/v1/lecturer-calendar/events/overdue`)
   - **Mô tả**: Lấy tasks đã quá deadline và chưa hoàn thành
   - **Dữ liệu trả về**: 
     - Tasks lecturer tạo hoặc được assign
     - Deadline < now() và status != 'completed'
   - **Status**: ✅ Hoàn chỉnh

9. **Đếm Events Theo Status** (`GET /api/v1/lecturer-calendar/events/count-by-status`)
   - **Mô tả**: Thống kê số lượng events theo trạng thái
   - **Dữ liệu trả về**: Tương tự Admin nhưng chỉ tính tasks của lecturer
   - **Status**: ✅ Hoàn chỉnh

---

### 👨‍🎓 **STUDENT** - 8 Endpoints

#### ✅ Chức Năng Đã Implement

1. **Lấy Events Của Student** (`GET /api/v1/student-calendar/events`)
   - **Mô tả**: Lấy tất cả tasks được assign cho student
   - **Query Params**: 
     - `page`, `per_page` (pagination)
     - `status`, `priority` (filter)
     - `date_from`, `date_to` (date range)
     - `search` (tìm kiếm)
   - **Dữ liệu trả về**: 
     - Tasks có student trong `receivers`
     - Pagination metadata
   - **Status**: ✅ Hoàn chỉnh
   - **Lưu ý**: Student chỉ xem được tasks được assign, không tạo event mới

2. **Lấy Events Theo Ngày** (`GET /api/v1/student-calendar/events/by-date`)
   - **Mô tả**: Lấy events trong một ngày cụ thể
   - **Query Params**: `date` (Y-m-d)
   - **Dữ liệu trả về**: Tasks có deadline trong ngày và student là receiver
   - **Status**: ✅ Hoàn chỉnh

3. **Lấy Events Theo Khoảng Thời Gian** (`GET /api/v1/student-calendar/events/by-range`)
   - **Mô tả**: Lấy events trong khoảng thời gian
   - **Query Params**: `start_date`, `end_date` (Y-m-d hoặc Y-m-d H:i:s)
   - **Dữ liệu trả về**: Tasks có deadline trong khoảng và student là receiver
   - **Status**: ✅ Hoàn chỉnh
   - **Lưu ý**: Dùng `start_date`/`end_date` thay vì `start`/`end` (khác với Lecturer/Admin)

4. **Lấy Events Sắp Tới** (`GET /api/v1/student-calendar/events/upcoming`)
   - **Mô tả**: Lấy events trong 30 ngày tới
   - **Query Params**: `limit` (optional, default: 10)
   - **Dữ liệu trả về**: Tasks có deadline từ bây giờ đến 30 ngày tới và student là receiver
   - **Status**: ✅ Hoàn chỉnh

5. **Lấy Events Quá Hạn** (`GET /api/v1/student-calendar/events/overdue`)
   - **Mô tả**: Lấy tasks đã quá deadline và chưa hoàn thành
   - **Dữ liệu trả về**: 
     - Tasks có student là receiver
     - Deadline < now() và status != 'completed'
   - **Status**: ✅ Hoàn chỉnh

6. **Đếm Events Theo Status** (`GET /api/v1/student-calendar/events/count-by-status`)
   - **Mô tả**: Thống kê số lượng events theo trạng thái
   - **Dữ liệu trả về**: Tương tự Admin nhưng chỉ tính tasks của student
   - **Status**: ✅ Hoàn chỉnh

7. **Lấy Reminders** (`GET /api/v1/student-calendar/reminders`)
   - **Mô tả**: Lấy reminders của student
   - **Query Params**: `page`, `per_page`, `status`, `type`
   - **Dữ liệu trả về**: Reminders của student với pagination
   - **Status**: ✅ Hoàn chỉnh (có ReminderService)

8. **Tạo Reminder** (`POST /api/v1/student-calendar/reminders`)
   - **Mô tả**: Tạo reminder cho task
   - **Request Body**: 
     - `task_id` (required)
     - `reminder_type` (required: 'email', 'push', 'sms', 'in_app')
     - `reminder_time` (required, Y-m-d H:i:s)
     - `message` (optional)
   - **Dữ liệu trả về**: Created reminder
   - **Status**: ✅ Hoàn chỉnh (có ReminderService)

---

## 📊 Cấu Trúc Dữ Liệu Event

### Event Format (từ Task)

```json
{
  "id": 1,
  "title": "Task Title",
  "description": "Task Description",
  "start": "2025-01-20 10:00:00",
  "end": "2025-01-20 10:00:00",
  "start_time": "2025-01-20 10:00:00",
  "end_time": "2025-01-20 10:00:00",
  "event_type": "task",
  "task_id": 1,
  "status": "pending|in_progress|completed",
  "priority": "low|medium|high|urgent",
  "class_id": 1,
  "creator": {
    "id": 1,
    "type": "lecturer|admin|student",
    "name": "Creator Name"
  },
  "receivers": [
    {
      "id": 2,
      "type": "student|lecturer",
      "name": "Receiver Name"
    }
  ],
  "files_count": 2,
  "submissions_count": 5,
  "created_at": "2025-01-15 08:00:00",
  "updated_at": "2025-01-18 12:00:00"
}
```

### Calendar Event Format (standalone)

```json
{
  "id": 1,
  "title": "Event Title",
  "description": "Event Description",
  "start_time": "2025-01-20 10:00:00",
  "end_time": "2025-01-20 12:00:00",
  "event_type": "event",
  "task_id": null,
  "creator_id": 1,
  "creator_type": "lecturer"
}
```

---

## 🔍 Chi Tiết Các Chức Năng

### 1. **Lọc và Tìm Kiếm**

#### ✅ Đã Implement
- **Filter theo Status**: `pending`, `in_progress`, `completed`
- **Filter theo Priority**: `low`, `medium`, `high`, `urgent`
- **Filter theo Date Range**: `date_from`, `date_to`
- **Search**: Tìm kiếm trong `title` và `description`
- **Pagination**: `page`, `per_page`

#### ⚠️ Chưa Implement
- Filter theo `class_id`
- Filter theo `creator`
- Filter theo `receiver`
- Advanced search với multiple fields
- Sort by multiple columns

---

### 2. **Quản Lý Events**

#### ✅ Đã Implement
- **Lecturer**: Tạo, cập nhật, xóa calendar events (standalone)
- **Admin**: Xem tất cả events
- **Student**: Chỉ xem events được assign

#### ⚠️ Chưa Implement
- **Lecturer**: Không thể tạo task từ calendar (phải dùng Task API)
- **Admin**: Không thể tạo/sửa/xóa events (chỉ xem)
- **Student**: Không thể tạo events
- **Recurring Events**: Chưa có logic xử lý events lặp lại
- **Event Templates**: Chưa có template cho events

---

### 3. **Reminder System**

#### ✅ Đã Implement
- **Model**: `Reminder` với đầy đủ fields
- **Migration**: Bảng `reminders` với indexes
- **Service**: `ReminderService` với các methods:
  - `createReminder()`
  - `getUserReminders()`
  - `updateReminder()`
  - `deleteReminder()`
  - `sendReminder()`
  - `scheduleReminder()`
- **Repository**: `ReminderRepository` với interface
- **Job**: `SendReminderNotificationJob` để gửi reminder
- **Command**: `ProcessRemindersCommand` để xử lý reminders
- **Student API**: Đầy đủ endpoints cho reminders

#### ⚠️ Chưa Implement Đầy Đủ
- **Admin/Lecturer API**: Chưa có endpoints riêng cho reminders (chỉ có mock)
- **CalendarService**: `getReminders()` và `setReminder()` đang trả về mock data
- **Integration**: Chưa tích hợp đầy đủ với NotificationService
- **Scheduling**: Chưa có cron job để tự động xử lý reminders
- **Multiple Reminders**: Chưa hỗ trợ nhiều reminders cho một task

---

### 4. **Thống Kê và Báo Cáo**

#### ✅ Đã Implement
- **Count by Status**: Đếm events theo status (pending, in_progress, completed, overdue, upcoming)
- **Upcoming Events**: Lấy events sắp tới (30 ngày)
- **Overdue Events**: Lấy events quá hạn

#### ⚠️ Chưa Implement
- **Statistics Dashboard**: Chưa có API tổng hợp thống kê
- **Charts Data**: Chưa có API trả về dữ liệu cho biểu đồ
- **Export**: Chưa có export calendar ra file (PDF, Excel, iCal)
- **Reports**: Chưa có báo cáo chi tiết theo thời gian

---

### 5. **Tích Hợp với Task Module**

#### ✅ Đã Implement
- **Task → Event**: Tasks tự động hiển thị trong calendar (deadline = event time)
- **Task Status**: Event hiển thị status của task
- **Task Priority**: Event hiển thị priority của task
- **Task Receivers**: Event hiển thị receivers của task
- **Task Creator**: Event hiển thị creator của task

#### ⚠️ Chưa Implement
- **Event → Task**: Không thể tạo task từ calendar event
- **Task Updates**: Chưa có real-time sync khi task thay đổi
- **Task Dependencies**: Chưa hiển thị dependencies trong calendar
- **Task Files**: Chưa hiển thị files trong event details

---

## 🎯 So Sánh Theo Role

| Chức Năng | Admin | Lecturer | Student |
|-----------|-------|----------|---------|
| **Xem Events** | ✅ Tất cả | ✅ Tạo + Assigned | ✅ Chỉ Assigned |
| **Tạo Event** | ❌ | ✅ Standalone | ❌ |
| **Sửa Event** | ❌ | ✅ Chỉ của mình | ❌ |
| **Xóa Event** | ❌ | ✅ Chỉ của mình | ❌ |
| **Tạo Task** | ✅ (Task API) | ✅ (Task API) | ❌ |
| **Reminders** | ⚠️ Mock | ⚠️ Mock | ✅ Đầy đủ |
| **Statistics** | ✅ Tất cả | ✅ Của mình | ✅ Của mình |
| **Filter/Search** | ✅ Tất cả | ✅ Của mình | ✅ Của mình |

---

## 📈 Tỷ Lệ Hoàn Thành

### Theo Module
- **Core Calendar Functions**: 90% ✅
- **Event Management**: 75% ⚠️ (thiếu recurring, templates)
- **Reminder System**: 60% ⚠️ (có service nhưng chưa tích hợp đầy đủ)
- **Statistics/Reports**: 50% ⚠️ (có count, thiếu dashboard)
- **Integration**: 80% ✅ (tích hợp tốt với Task)

### Theo Role
- **Admin**: 85% ✅
- **Lecturer**: 90% ✅
- **Student**: 85% ✅

---

## 🚀 Các Tính Năng Có Thể Mở Rộng

### 1. **Recurring Events**
- Events lặp lại theo pattern (daily, weekly, monthly, yearly)
- Custom recurrence rules
- Exception dates

### 2. **Event Templates**
- Tạo template cho events thường dùng
- Apply template để tạo event nhanh

### 3. **Calendar Sharing**
- Share calendar với users khác
- Public/Private calendar settings
- Permission levels (view, edit)

### 4. **Event Categories/Tags**
- Phân loại events bằng categories
- Tags cho events
- Color coding

### 5. **Event Attachments**
- Attach files to calendar events
- Link to tasks, documents

### 6. **Event Notifications**
- Email notifications
- Push notifications
- SMS notifications
- In-app notifications

### 7. **Calendar Views**
- Month view
- Week view
- Day view
- Agenda view
- Timeline view

### 8. **Event Conflicts Detection**
- Detect overlapping events
- Suggest alternative times
- Auto-resolve conflicts

### 9. **Event Export/Import**
- Export to iCal format
- Import from Google Calendar
- Export to PDF/Excel

### 10. **Event Analytics**
- Event attendance tracking
- Event completion rates
- Time spent on events
- Event patterns analysis

---

## 🔧 Technical Details

### Database Tables
1. **`task`**: Source chính cho calendar events
2. **`calendar`**: Standalone calendar events (chỉ Lecturer tạo)
3. **`reminders`**: Reminders cho tasks/events

### Key Services
1. **`CalendarService`**: Common calendar operations
2. **`LecturerCalendarRepository`**: Lecturer-specific operations
3. **`StudentCalendarRepository`**: Student-specific operations
4. **`ReminderService`**: Reminder management

### Key Controllers
1. **`CalendarController`**: Common endpoints (Admin)
2. **`LecturerCalendarController`**: Lecturer endpoints
3. **`StudentCalendarController`**: Student endpoints

---

## 📝 Kết Luận

Hệ thống Calendar hiện tại đã cung cấp đầy đủ các chức năng cơ bản cho việc quản lý lịch và sự kiện dựa trên Tasks. Các chức năng chính đã được implement và hoạt động tốt:

✅ **Điểm Mạnh**:
- Phân quyền rõ ràng theo role
- Tích hợp tốt với Task module
- API đầy đủ và nhất quán
- Clean Architecture pattern
- Hỗ trợ pagination, filter, search

⚠️ **Cần Cải Thiện**:
- Hoàn thiện Reminder System (tích hợp đầy đủ)
- Implement Recurring Events
- Thêm Statistics Dashboard
- Cải thiện Event Management (templates, categories)
- Export/Import functionality

🎯 **Tỷ Lệ Hoàn Thành Tổng Thể**: **~80%**

---

**Last Updated**: 2025-01-20
**Version**: 2.0.0

