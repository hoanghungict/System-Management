# 📋 Module Task - Checklist Thông Báo Cần Triển Khai

## 🎯 Tổng Quan

Tài liệu này liệt kê **TẤT CẢ** các thông báo cần triển khai trong module Task, bao gồm số lượng cụ thể và mức độ ưu tiên.

---

## 📊 Thống Kê Tổng Quan

| Loại Thông Báo | Số Lượng | Đã Hoàn Thành | Chưa Làm | Tỷ Lệ Hoàn Thành |
|----------------|----------|---------------|----------|------------------|
| **Task Events** | 8 | 0 | 8 | 0% |
| **Calendar Events** | 6 | 0 | 6 | 0% |
| **Email Events** | 2 | 0 | 2 | 0% |
| **Job Events** | 3 | 0 | 3 | 0% |
| **Data Extractors** | 6 | 0 | 6 | 0% |
| **Templates** | 13 | 0 | 13 | 0% |
| **TỔNG CỘNG** | **38** | **0** | **38** | **0%** |

---

Tại Sao Cần Data Extractor:
✅ Lợi Ích:
Tách biệt logic: API response format không ảnh hưởng đến notification logic
Tái sử dụng: Cùng 1 extractor có thể dùng cho nhiều notification handlers
Dễ test: Test riêng từng phần
Dễ maintain: Khi API thay đổi, chỉ cần sửa extractor
�� Các Data Extractor Cần Tạo:
TaskDataExtractor - Trích xuất dữ liệu Task từ API
CalendarDataExtractor - Trích xuất dữ liệu Calendar từ API
UserDataExtractor - Trích xuất thông tin User từ API
SubmissionDataExtractor - Trích xuất dữ liệu bài nộp từ API
ReceiverDataExtractor - Trích xuất danh sách người nhận từ API
EmailDataExtractor - Trích xuất dữ liệu email từ API
Vậy Data Extractor chính là "cầu nối" giữa API response và notification system!

---
## 🔔 Sự Kiện Thông Báo Cần Triển Khai

### 📝 **Nhóm 1: Task Events (8 thông báo)**

#### 🔴 **Mức Độ Ưu Tiên: CAO**

1. **`TaskCreatedNotificationHandler`** ❌
   - **Mô tả**: Thông báo khi có task mới được tạo
   - **Người nhận**: Tất cả người được giao task
   - **Template**: `task.created`
   - **Data Extractor**: `TaskDataExtractor`, `UserDataExtractor`, `ReceiverDataExtractor`

2. **`TaskAssignedNotificationHandler`** ❌
   - **Mô tả**: Thông báo khi task được giao cho người mới
   - **Người nhận**: Người được giao task mới
   - **Template**: `task.assigned`
   - **Data Extractor**: `TaskDataExtractor`, `UserDataExtractor`

3. **`TaskGradedNotificationHandler`** ❌
   - **Mô tả**: Thông báo khi bài tập được chấm điểm
   - **Người nhận**: Sinh viên nộp bài
   - **Template**: `task.graded`
   - **Data Extractor**: `TaskDataExtractor`, `SubmissionDataExtractor`, `UserDataExtractor`

4. **`TaskDeadlineApproachingNotificationHandler`** ❌
   - **Mô tả**: Nhắc nhở hạn nộp bài (24h, 1h trước)
   - **Người nhận**: Sinh viên chưa nộp bài
   - **Template**: `task.deadline_approaching`
   - **Data Extractor**: `TaskDataExtractor`, `UserDataExtractor`

5. **`TaskDeadlineOverdueNotificationHandler`** ❌
   - **Mô tả**: Cảnh báo khi quá hạn nộp bài
   - **Người nhận**: Sinh viên chưa nộp bài
   - **Template**: `task.deadline_overdue`
   - **Data Extractor**: `TaskDataExtractor`, `UserDataExtractor`

#### 🟡 **Mức Độ Ưu Tiên: TRUNG BÌNH**

6. **`TaskUpdatedNotificationHandler`** ❌
   - **Mô tả**: Thông báo khi task được cập nhật
   - **Người nhận**: Tất cả người được giao task + người tạo task
   - **Template**: `task.updated`
   - **Data Extractor**: `TaskDataExtractor`, `UserDataExtractor`

7. **`TaskSubmittedNotificationHandler`** ❌
   - **Mô tả**: Thông báo khi sinh viên nộp bài
   - **Người nhận**: Giảng viên tạo task
   - **Template**: `task.submitted`
   - **Data Extractor**: `TaskDataExtractor`, `SubmissionDataExtractor`, `UserDataExtractor`

8. **`TaskStatusChangedNotificationHandler`** ❌
   - **Mô tả**: Thông báo khi trạng thái task thay đổi
   - **Người nhận**: Tất cả người liên quan đến task
   - **Template**: `task.status_changed`
   - **Data Extractor**: `TaskDataExtractor`, `UserDataExtractor`

---

### 📅 **Nhóm 2: Calendar Events (6 thông báo)**

#### 🔴 **Mức Độ Ưu Tiên: CAO**

9. **`CalendarEventCreatedNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi có sự kiện mới được tạo trong calendar
    - **Người nhận**: Tất cả người liên quan đến sự kiện
    - **Template**: `calendar.event.created`
    - **Data Extractor**: `CalendarDataExtractor`, `UserDataExtractor`

10. **`CalendarEventUpdatedNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi sự kiện calendar được cập nhật
    - **Người nhận**: Tất cả người liên quan đến sự kiện
    - **Template**: `calendar.event.updated`
    - **Data Extractor**: `CalendarDataExtractor`, `UserDataExtractor`

11. **`CalendarEventReminderNotificationHandler`** ❌
    - **Mô tả**: Nhắc nhở sự kiện sắp diễn ra (1h, 24h trước)
    - **Người nhận**: Người tham gia sự kiện
    - **Template**: `calendar.event.reminder`
    - **Data Extractor**: `CalendarDataExtractor`, `UserDataExtractor`

#### 🟡 **Mức Độ Ưu Tiên: TRUNG BÌNH**

12. **`CalendarEventCancelledNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi sự kiện bị hủy
    - **Người nhận**: Tất cả người tham gia sự kiện
    - **Template**: `calendar.event.cancelled`
    - **Data Extractor**: `CalendarDataExtractor`, `UserDataExtractor`

13. **`CalendarEventRescheduledNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi sự kiện được dời lịch
    - **Người nhận**: Tất cả người tham gia sự kiện
    - **Template**: `calendar.event.rescheduled`
    - **Data Extractor**: `CalendarDataExtractor`, `UserDataExtractor`

#### 🟢 **Mức Độ Ưu Tiên: THẤP**

14. **`CalendarEventCompletedNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi sự kiện hoàn thành
    - **Người nhận**: Người tạo sự kiện + Admin
    - **Template**: `calendar.event.completed`
    - **Data Extractor**: `CalendarDataExtractor`, `UserDataExtractor`

---

### 📧 **Nhóm 3: Email Events (2 thông báo)**

#### 🟢 **Mức Độ Ưu Tiên: THẤP**

15. **`EmailSentNotificationHandler`** ❌
   - **Mô tả**: Xác nhận email đã gửi thành công
   - **Người nhận**: Người gửi email
   - **Template**: `email.sent`
   - **Data Extractor**: `EmailDataExtractor`

16. **`EmailFailedNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi gửi email thất bại
    - **Người nhận**: Người gửi email + Admin
    - **Template**: `email.failed`
    - **Data Extractor**: `EmailDataExtractor`

---

### ⚙️ **Nhóm 4: Job Events (3 thông báo)**

#### 🟢 **Mức Độ Ưu Tiên: THẤP**

17. **`TaskProcessingStartedNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi bắt đầu xử lý task
    - **Người nhận**: Admin + Người tạo task
    - **Template**: `task.processing_started`
    - **Data Extractor**: `TaskDataExtractor`

18. **`TaskProcessingCompletedNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi hoàn thành xử lý task
    - **Người nhận**: Admin + Người tạo task
    - **Template**: `task.processing_completed`
    - **Data Extractor**: `TaskDataExtractor`

19. **`TaskProcessingFailedNotificationHandler`** ❌
    - **Mô tả**: Thông báo khi xử lý task thất bại
    - **Người nhận**: Admin + Người tạo task
    - **Template**: `task.processing_failed`
    - **Data Extractor**: `TaskDataExtractor`

---

## 🔧 Data Extractors Cần Triển Khai

### 📊 **Nhóm 5: Data Extractors (6 classes)**

20. **`TaskDataExtractor`** ❌
    - **Mô tả**: Trích xuất dữ liệu từ Task Model
    - **Chức năng**: Lấy thông tin cơ bản, format datetime, xử lý boolean
    - **Sử dụng**: Tất cả notification handlers liên quan đến Task

21. **`CalendarDataExtractor`** ❌
    - **Mô tả**: Trích xuất dữ liệu từ Calendar Model
    - **Chức năng**: Lấy thông tin sự kiện, thời gian, loại sự kiện
    - **Sử dụng**: Tất cả notification handlers liên quan đến Calendar

22. **`UserDataExtractor`** ❌
    - **Mô tả**: Trích xuất dữ liệu từ User Model
    - **Chức năng**: Lấy thông tin cá nhân, lớp học, khoa theo role
    - **Sử dụng**: Lấy thông tin người tạo, người nhận, người chấm điểm

23. **`SubmissionDataExtractor`** ❌
    - **Mô tả**: Trích xuất dữ liệu từ TaskSubmission Model
    - **Chức năng**: Lấy nội dung bài nộp, tính toán trạng thái, điểm số
    - **Sử dụng**: Thông báo liên quan đến bài nộp và chấm điểm

24. **`ReceiverDataExtractor`** ❌
    - **Mô tả**: Trích xuất dữ liệu từ TaskReceiver Model
    - **Chức năng**: Xử lý các loại receiver, lấy danh sách người nhận thực tế
    - **Sử dụng**: Xác định ai sẽ nhận thông báo

25. **`EmailDataExtractor`** ❌
    - **Mô tả**: Trích xuất dữ liệu từ Email Events
    - **Chức năng**: Lấy thông tin email, xử lý metadata
    - **Sử dụng**: Thông báo về trạng thái gửi email

---

## 📄 Templates Cần Triển Khai

### 📝 **Nhóm 6: Notification Templates (13 templates)**

#### 🔴 **Mức Độ Ưu Tiên: CAO**

26. **Template `task.created`** ❌
    - **Mô tả**: Template cho thông báo tạo task mới
    - **Sử dụng**: TaskCreatedNotificationHandler
    - **Nội dung**: Tiêu đề, mô tả, deadline, độ ưu tiên, người tạo

27. **Template `task.assigned`** ❌
    - **Mô tả**: Template cho thông báo giao task
    - **Sử dụng**: TaskAssignedNotificationHandler
    - **Nội dung**: Tiêu đề task, người giao, deadline

28. **Template `task.graded`** ❌
    - **Mô tả**: Template cho thông báo chấm điểm
    - **Sử dụng**: TaskGradedNotificationHandler
    - **Nội dung**: Điểm số, nhận xét, giảng viên chấm

29. **Template `task.deadline_approaching`** ❌
    - **Mô tả**: Template cho thông báo nhắc nhở
    - **Sử dụng**: TaskDeadlineApproachingNotificationHandler
    - **Nội dung**: Thời gian còn lại, độ ưu tiên

30. **Template `task.deadline_overdue`** ❌
    - **Mô tả**: Template cho thông báo quá hạn
    - **Sử dụng**: TaskDeadlineOverdueNotificationHandler
    - **Nội dung**: Thời gian quá hạn, cảnh báo

31. **Template `calendar.event.created`** ❌
    - **Mô tả**: Template cho thông báo tạo sự kiện calendar
    - **Sử dụng**: CalendarEventCreatedNotificationHandler
    - **Nội dung**: Tên sự kiện, thời gian, địa điểm, người tạo

32. **Template `calendar.event.updated`** ❌
    - **Mô tả**: Template cho thông báo cập nhật sự kiện
    - **Sử dụng**: CalendarEventUpdatedNotificationHandler
    - **Nội dung**: Thay đổi, thời gian mới, địa điểm mới

33. **Template `calendar.event.reminder`** ❌
    - **Mô tả**: Template cho thông báo nhắc nhở sự kiện
    - **Sử dụng**: CalendarEventReminderNotificationHandler
    - **Nội dung**: Thời gian còn lại, địa điểm, ghi chú

#### 🟡 **Mức Độ Ưu Tiên: TRUNG BÌNH**

34. **Template `task.updated`** ❌
    - **Mô tả**: Template cho thông báo cập nhật task
    - **Sử dụng**: TaskUpdatedNotificationHandler
    - **Nội dung**: Thay đổi, deadline mới, độ ưu tiên mới

35. **Template `task.submitted`** ❌
    - **Mô tả**: Template cho thông báo nộp bài
    - **Sử dụng**: TaskSubmittedNotificationHandler
    - **Nội dung**: Tên sinh viên, tóm tắt bài nộp, file đính kèm

36. **Template `task.status_changed`** ❌
    - **Mô tả**: Template cho thông báo thay đổi trạng thái
    - **Sử dụng**: TaskStatusChangedNotificationHandler
    - **Nội dung**: Trạng thái cũ/mới, người thay đổi

37. **Template `calendar.event.cancelled`** ❌
    - **Mô tả**: Template cho thông báo hủy sự kiện
    - **Sử dụng**: CalendarEventCancelledNotificationHandler
    - **Nội dung**: Lý do hủy, thông tin sự kiện

38. **Template `calendar.event.rescheduled`** ❌
    - **Mô tả**: Template cho thông báo dời lịch sự kiện
    - **Sử dụng**: CalendarEventRescheduledNotificationHandler
    - **Nội dung**: Thời gian cũ/mới, lý do dời lịch

#### 🟢 **Mức Độ Ưu Tiên: THẤP**

39. **Template `email.sent`** ❌
    - **Mô tả**: Template cho thông báo gửi email thành công
    - **Sử dụng**: EmailSentNotificationHandler
    - **Nội dung**: Chủ đề, số lượng người nhận, thời gian

40. **Template `email.failed`** ❌
    - **Mô tả**: Template cho thông báo gửi email thất bại
    - **Sử dụng**: EmailFailedNotificationHandler
    - **Nội dung**: Lý do lỗi, thông tin email

41. **Template `calendar.event.completed`** ❌
    - **Mô tả**: Template cho thông báo hoàn thành sự kiện
    - **Sử dụng**: CalendarEventCompletedNotificationHandler
    - **Nội dung**: Tóm tắt sự kiện, kết quả

---

## 📋 Kế Hoạch Triển Khai

cấu trúc của task data extractor:
Modules/Notifications/
├── app/
│   ├── Contracts/
│   │   ├── DataExtractorInterface.php
│   │   ├── NotificationHandlerInterface.php
│   │   └── NotificationServiceInterface.php
│   ├── DataExtractors/
│   │   ├── TaskDataExtractor.php
│   │   ├── UserDataExtractor.php
│   │   ├── CalendarDataExtractor.php
│   │   ├── SubmissionDataExtractor.php
│   │   ├── ReceiverDataExtractor.php
│   │   └── EmailDataExtractor.php
│   ├── Handlers/
│   │   ├── Task/
│   │   │   ├── TaskCreatedNotificationHandler.php
│   │   │   ├── TaskUpdatedNotificationHandler.php
│   │   │   ├── TaskAssignedNotificationHandler.php
│   │   │   ├── TaskSubmittedNotificationHandler.php
│   │   │   ├── TaskGradedNotificationHandler.php
│   │   │   ├── TaskDeadlineApproachingNotificationHandler.php
│   │   │   ├── TaskDeadlineOverdueNotificationHandler.php
│   │   │   └── TaskStatusChangedNotificationHandler.php
│   │   ├── Calendar/
│   │   │   ├── CalendarEventCreatedNotificationHandler.php
│   │   │   ├── CalendarEventUpdatedNotificationHandler.php
│   │   │   ├── CalendarEventReminderNotificationHandler.php
│   │   │   ├── CalendarEventCancelledNotificationHandler.php
│   │   │   ├── CalendarEventRescheduledNotificationHandler.php
│   │   │   └── CalendarEventCompletedNotificationHandler.php
│   │   ├── Email/
│   │   │   ├── EmailSentNotificationHandler.php
│   │   │   └── EmailFailedNotificationHandler.php
│   │   └── Job/
│   │       ├── TaskProcessingStartedNotificationHandler.php
│   │       ├── TaskProcessingCompletedNotificationHandler.php
│   │       └── TaskProcessingFailedNotificationHandler.php
│   ├── Services/
│   │   ├── NotificationService.php
│   │   ├── EmailNotificationService.php
│   │   ├── PushNotificationService.php
│   │   └── DatabaseNotificationService.php
│   ├── Templates/
│   │   ├── Task/
│   │   │   ├── task.created.blade.php
│   │   │   ├── task.updated.blade.php
│   │   │   ├── task.assigned.blade.php
│   │   │   ├── task.submitted.blade.php
│   │   │   ├── task.graded.blade.php
│   │   │   ├── task.deadline_approaching.blade.php
│   │   │   ├── task.deadline_overdue.blade.php
│   │   │   └── task.status_changed.blade.php
│   │   ├── Calendar/
│   │   │   ├── calendar.event.created.blade.php
│   │   │   ├── calendar.event.updated.blade.php
│   │   │   ├── calendar.event.reminder.blade.php
│   │   │   ├── calendar.event.cancelled.blade.php
│   │   │   ├── calendar.event.rescheduled.blade.php
│   │   │   └── calendar.event.completed.blade.php
│   │   ├── Email/
│   │   │   ├── email.sent.blade.php
│   │   │   └── email.failed.blade.php
│   │   └── Job/
│   │       ├── task.processing_started.blade.php
│   │       ├── task.processing_completed.blade.php
│   │       └── task.processing_failed.blade.php
│   ├── Events/
│   │   ├── TaskCreatedEvent.php
│   │   ├── TaskUpdatedEvent.php
│   │   ├── CalendarEventCreatedEvent.php
│   │   └── EmailSentEvent.php
│   ├── Listeners/
│   │   ├── TaskCreatedListener.php
│   │   ├── TaskUpdatedListener.php
│   │   └── CalendarEventCreatedListener.php
│   ├── Models/
│   │   ├── Notification.php
│   │   ├── NotificationTemplate.php
│   │   └── NotificationLog.php
│   └── Providers/
│       └── NotificationServiceProvider.php
├── config/
│   └── notifications.php
├── database/
│   ├── migrations/
│   │   ├── create_notifications_table.php
│   │   ├── create_notification_templates_table.php
│   │   └── create_notification_logs_table.php
│   └── seeders/
│       └── NotificationTemplateSeeder.php
└── routes/
    └── api.php
### 🎯 **Phase 1: Core Task & Calendar Notifications (Tuần 1-2)**
**Mục tiêu**: Triển khai 10 thông báo quan trọng nhất

- [ ] TaskCreatedNotificationHandler
- [ ] TaskAssignedNotificationHandler  
- [ ] TaskGradedNotificationHandler
- [ ] TaskDeadlineApproachingNotificationHandler
- [ ] TaskDeadlineOverdueNotificationHandler
- [ ] CalendarEventCreatedNotificationHandler
- [ ] CalendarEventUpdatedNotificationHandler
- [ ] CalendarEventReminderNotificationHandler

**Data Extractors cần thiết**:
- [ ] TaskDataExtractor
- [ ] CalendarDataExtractor
- [ ] UserDataExtractor
- [ ] ReceiverDataExtractor
- [ ] SubmissionDataExtractor

**Templates cần thiết**:
- [ ] task.created
- [ ] task.assigned
- [ ] task.graded
- [ ] task.deadline_approaching
- [ ] task.deadline_overdue
- [ ] calendar.event.created
- [ ] calendar.event.updated
- [ ] calendar.event.reminder

### 🎯 **Phase 2: Secondary Notifications (Tuần 3)**
**Mục tiêu**: Triển khai 6 thông báo phụ

- [ ] TaskUpdatedNotificationHandler
- [ ] TaskSubmittedNotificationHandler
- [ ] TaskStatusChangedNotificationHandler
- [ ] CalendarEventCancelledNotificationHandler
- [ ] CalendarEventRescheduledNotificationHandler

**Templates cần thiết**:
- [ ] task.updated
- [ ] task.submitted
- [ ] task.status_changed
- [ ] calendar.event.cancelled
- [ ] calendar.event.rescheduled

### 🎯 **Phase 3: System Notifications (Tuần 4)**
**Mục tiêu**: Triển khai các thông báo hệ thống

- [ ] EmailSentNotificationHandler
- [ ] EmailFailedNotificationHandler
- [ ] TaskProcessingStartedNotificationHandler
- [ ] TaskProcessingCompletedNotificationHandler
- [ ] TaskProcessingFailedNotificationHandler
- [ ] CalendarEventCompletedNotificationHandler

**Data Extractors cần thiết**:
- [ ] EmailDataExtractor

**Templates cần thiết**:
- [ ] email.sent
- [ ] email.failed
- [ ] calendar.event.completed

---

## 📊 Tiến Độ Triển Khai

### ✅ **Checklist Tổng Quan**

| Phase | Số Lượng | Hoàn Thành | Tiến Độ |
|-------|----------|------------|---------|
| **Phase 1** | 21 | 0 | 0% |
| **Phase 2** | 5 | 0 | 0% |
| **Phase 3** | 12 | 0 | 0% |
| **TỔNG CỘNG** | **38** | **0** | **0%** |

### 📈 **Biểu Đồ Tiến Độ**

```
Phase 1 (Core):     ████████████████████████████████████████ 0/21 (0%)
Phase 2 (Secondary): ████████████████████████████████████████ 0/5 (0%)
Phase 3 (System):    ████████████████████████████████████████ 0/12 (0%)

TỔNG TIẾN ĐỘ:        ████████████████████████████████████████ 0/38 (0%)
```

---

## 🎯 Mục Tiêu Hoàn Thành

- **Tuần 1-2**: Hoàn thành Phase 1 (55% tổng công việc)
- **Tuần 3**: Hoàn thành Phase 2 (68% tổng công việc)  
- **Tuần 4**: Hoàn thành Phase 3 (100% tổng công việc)

**Tổng thời gian dự kiến**: 4 tuần
**Tổng số thông báo**: 38
**Mức độ ưu tiên**: Cao → Trung bình → Thấp

---

## 📝 Ghi Chú Quan Trọng

1. **Thứ tự ưu tiên**: Bắt đầu với Phase 1 để có hệ thống thông báo cơ bản hoạt động
2. **Dependencies**: Data Extractors phải được tạo trước khi implement Notification Handlers
3. **Testing**: Mỗi phase cần được test kỹ lưỡng trước khi chuyển sang phase tiếp theo
4. **Documentation**: Cập nhật tài liệu API sau mỗi phase
5. **Calendar Integration**: Calendar notifications cần tích hợp với Task notifications để đồng bộ

---

*📅 Tạo ngày: Tháng 1 năm 2024*
*🔄 Phiên bản: 2.0*
*📋 Module: Task & Calendar Notifications*
*🎯 Trạng thái: Chưa bắt đầu*
