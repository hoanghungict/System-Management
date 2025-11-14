# 📊 Calendar UI - Data Mapping Guide

## 🎯 Tổng Quan

File này hướng dẫn các **key cần lấy từ API response** để hiển thị trên UI Calendar với format hiện tại (lưới lịch + danh sách sự kiện).

---

## 📋 Cấu Trúc Event Object Từ API

Sau khi `CalendarService` merge `Task` và `Calendar` models, mỗi event object có cấu trúc:

```json
{
  "id": 1,
  "title": "Test Task for SoftDelete",
  "description": "Task description here",
  "start": "2025-01-20 10:00:00",
  "end": "2025-01-20 10:00:00",
  "start_time": "2025-01-20 10:00:00",
  "end_time": "2025-01-20 10:00:00",
  "event_type": "task",
  "task_id": 1,
  "status": "in_progress",
  "priority": "high",
  "class_id": 1,
  "creator": {
    "id": 1,
    "type": "lecturer",
    "name": "Unknown"
  },
  "receivers": [
    {
      "id": 2,
      "type": "student",
      "name": "Student Name"
    }
  ],
  "files_count": 2,
  "submissions_count": 5,
  "created_at": "2025-01-15 08:00:00",
  "updated_at": "2025-01-18 12:00:00"
}
```

---

## 🎨 Mapping Cho UI Components

### 1. **Lưới Lịch (Calendar Grid) - Các Ô Ngày**

#### Keys Cần Dùng:

| UI Element | Key Từ DB | Mô Tả | Ví Dụ |
|------------|-----------|-------|--------|
| **Ngày hiển thị sự kiện** | `start` hoặc `start_time` | Xác định ngày sự kiện diễn ra | `"2025-01-20 10:00:00"` |
| **Tiêu đề sự kiện** | `title` | Hiển thị trong ô ngày (ngắn gọn) | `"Test Task"` |
| **Màu sắc/Icon** | `event_type` | Phân biệt Task vs Calendar Event | `"task"` hoặc `"event"` |
| **Màu sắc trạng thái** | `status` | Màu theo trạng thái | `"pending"`, `"in_progress"`, `"completed"` |
| **Màu sắc độ ưu tiên** | `priority` | Màu theo độ ưu tiên | `"high"`, `"medium"`, `"low"` |
| **ID để click** | `id` | Dùng khi click vào sự kiện | `1` |

#### Code Example:

```javascript
// Lấy ngày từ start_time
const eventDate = new Date(event.start_time).toISOString().split('T')[0]; // "2025-01-20"

// Hiển thị trong ô ngày
if (eventDate === selectedDate) {
  // Hiển thị event với màu theo status/priority
  const color = getEventColor(event.status, event.priority, event.event_type);
  // Render event dot hoặc text
}
```

---

### 2. **Danh Sách Sự Kiện (Event List) - "Sự kiện không có ngày (13)"**

#### Keys Cần Dùng:

| UI Element | Key Từ DB | Mô Tả | Ví Dụ |
|------------|-----------|-------|--------|
| **Tiêu đề** | `title` | Tiêu đề chính của sự kiện | `"Test Task for SoftDelete"` |
| **Mô tả** | `description` | Mô tả chi tiết (optional) | `"Task description here"` |
| **Nhãn Priority** | `priority` | Hiển thị "HIGH", "MEDIUM", "LOW" | `"high"` → `"HIGH"` |
| **Nhãn Status** | `status` | Hiển thị "PENDING", "IN_PROGRESS", "COMPLETED" | `"in_progress"` → `"IN_PROGRESS"` |
| **Thời gian** | `start_time` / `end_time` | Hiển thị thời gian bắt đầu/kết thúc | `"2025-01-20 10:00:00"` |
| **Loại sự kiện** | `event_type` | Icon/màu phân biệt Task vs Event | `"task"` hoặc `"event"` |
| **ID để click** | `id` | Dùng khi click vào sự kiện | `1` |
| **Task ID** | `task_id` | ID của task (nếu là task) | `1` hoặc `null` |
| **Người tạo** | `creator.name` | Tên người tạo (hiện tại là "Unknown") | `"Unknown"` |
| **Số lượng files** | `files_count` | Số file đính kèm | `2` |
| **Số lượng submissions** | `submissions_count` | Số bài nộp (chỉ cho task) | `5` |

#### Code Example:

```javascript
// Render danh sách sự kiện
events.map(event => ({
  id: event.id,
  title: event.title,
  description: event.description,
  priority: event.priority?.toUpperCase(), // "HIGH", "MEDIUM", "LOW"
  status: event.status?.toUpperCase().replace('_', ' '), // "IN PROGRESS", "COMPLETED"
  startTime: event.start_time,
  endTime: event.end_time,
  eventType: event.event_type, // "task" hoặc "event"
  taskId: event.task_id,
  filesCount: event.files_count,
  submissionsCount: event.submissions_count
}))
```

---

## 🔑 Các Key Quan Trọng Nhất

### **Bắt Buộc Phải Có:**

1. **`id`** - ID duy nhất của event (bắt buộc cho mọi thao tác)
2. **`title`** - Tiêu đề sự kiện (hiển thị chính)
3. **`start_time`** hoặc **`start`** - Thời gian bắt đầu (để định vị trên lịch)
4. **`end_time`** hoặc **`end`** - Thời gian kết thúc
5. **`event_type`** - Loại sự kiện (`"task"` hoặc `"event"`)
6. **`status`** - Trạng thái (`"pending"`, `"in_progress"`, `"completed"`)
7. **`priority`** - Độ ưu tiên (`"high"`, `"medium"`, `"low"`)

### **Nên Có (Optional nhưng hữu ích):**

8. **`description`** - Mô tả chi tiết
9. **`task_id`** - ID của task (nếu là task)
10. **`creator`** - Thông tin người tạo
11. **`receivers`** - Danh sách người nhận (chủ yếu cho task)
12. **`files_count`** - Số lượng files
13. **`submissions_count`** - Số lượng submissions (chỉ cho task)

---

## 📅 Mapping Theo Chức Năng UI

### **1. Hiển Thị Trên Lưới Lịch (Calendar Grid)**

```javascript
// Lọc events theo ngày
const eventsForDate = events.filter(event => {
  const eventDate = new Date(event.start_time).toISOString().split('T')[0];
  return eventDate === selectedDate; // selectedDate = "2025-01-20"
});

// Render mỗi event trong ô ngày
eventsForDate.forEach(event => {
  // Dùng các key:
  // - event.title (hiển thị text ngắn)
  // - event.status (màu sắc)
  // - event.priority (màu sắc)
  // - event.event_type (icon/màu phân biệt)
  // - event.id (để click)
});
```

### **2. Hiển Thị Trong Danh Sách Sự Kiện**

```javascript
// Render danh sách
events.map(event => {
  return {
    // Bắt buộc
    id: event.id,
    title: event.title,
    startTime: event.start_time,
    endTime: event.end_time,
    status: event.status,
    priority: event.priority,
    eventType: event.event_type,
    
    // Optional
    description: event.description,
    taskId: event.task_id,
    creator: event.creator,
    receivers: event.receivers,
    filesCount: event.files_count,
    submissionsCount: event.submissions_count
  };
});
```

### **3. Filter và Sort**

```javascript
// Filter theo status
const pendingEvents = events.filter(e => e.status === 'pending');
const inProgressEvents = events.filter(e => e.status === 'in_progress');
const completedEvents = events.filter(e => e.status === 'completed');

// Filter theo priority
const highPriorityEvents = events.filter(e => e.priority === 'high');

// Filter theo event_type
const tasks = events.filter(e => e.event_type === 'task');
const calendarEvents = events.filter(e => e.event_type === 'event');

// Sort theo thời gian
events.sort((a, b) => {
  return new Date(a.start_time) - new Date(b.start_time);
});
```

---

## 🎨 Styling Dựa Trên Keys

### **Màu Sắc Theo Status:**

```javascript
const statusColors = {
  'pending': '#FFA500',      // Orange
  'in_progress': '#2196F3',  // Blue
  'completed': '#4CAF50',    // Green
  'scheduled': '#9E9E9E'     // Gray (cho calendar events)
};
```

### **Màu Sắc Theo Priority:**

```javascript
const priorityColors = {
  'high': '#F44336',    // Red
  'medium': '#FF9800',  // Orange
  'low': '#4CAF50'      // Green
};
```

### **Icon Theo Event Type:**

```javascript
const eventTypeIcons = {
  'task': '📋',      // Task icon
  'event': '📅'     // Calendar event icon
};
```

---

## 📡 API Response Format

### **Response từ `GET /api/v1/calendar/events/by-range`:**

```json
{
  "success": true,
  "data": {
    "start_date": "2025-01-01 00:00:00",
    "end_date": "2025-01-31 23:59:59",
    "events": [
      {
        "id": 1,
        "title": "Test Task for SoftDelete",
        "description": "Task description",
        "start": "2025-01-20 10:00:00",
        "end": "2025-01-20 10:00:00",
        "start_time": "2025-01-20 10:00:00",
        "end_time": "2025-01-20 10:00:00",
        "event_type": "task",
        "task_id": 1,
        "status": "in_progress",
        "priority": "high",
        "class_id": 1,
        "creator": {
          "id": 1,
          "type": "lecturer",
          "name": "Unknown"
        },
        "receivers": [],
        "files_count": 2,
        "submissions_count": 5,
        "created_at": "2025-01-15 08:00:00",
        "updated_at": "2025-01-18 12:00:00"
      },
      {
        "id": 2,
        "title": "Meeting with Students",
        "description": "Discuss project progress",
        "start": "2025-01-25 14:00:00",
        "end": "2025-01-25 16:00:00",
        "start_time": "2025-01-25 14:00:00",
        "end_time": "2025-01-25 16:00:00",
        "event_type": "event",
        "task_id": null,
        "status": "scheduled",
        "priority": "medium",
        "class_id": null,
        "creator": {
          "id": 1,
          "type": "lecturer",
          "name": "Unknown"
        },
        "receivers": [],
        "files_count": 0,
        "submissions_count": 0,
        "created_at": null,
        "updated_at": null
      }
    ],
    "count": 2
  },
  "message": "Events retrieved successfully"
}
```

---

## 🔍 Lưu Ý Quan Trọng

### **1. Về "Sự kiện không có ngày (13)"**

Phần này trong UI có thể là:
- **Danh sách sự kiện cho ngày đang được chọn**: Gọi `GET /api/v1/calendar/events/by-date?date=2025-01-20`
- **Danh sách sự kiện sắp tới**: Gọi `GET /api/v1/calendar/events/upcoming`
- **Danh sách sự kiện quá hạn**: Gọi `GET /api/v1/calendar/events/overdue`

**Tất cả events từ API đều có `start_time` và `end_time`**, không có sự kiện "không có ngày". Có thể đây là:
- Sự kiện chưa được assign ngày cụ thể (nhưng vẫn có start_time)
- Hoặc là danh sách sự kiện cho ngày được chọn

### **2. Về Creator Name**

Hiện tại `creator.name` luôn là `"Unknown"` vì model Task không có relationship `creator()`. Nếu cần hiển thị tên người tạo, cần:
- Thêm relationship `creator()` vào model Task
- Hoặc query riêng để lấy thông tin creator

### **3. Về Event Type**

- **`event_type === "task"`**: Là task từ bảng `task`, có `task_id`, có thể có `files_count`, `submissions_count`
- **`event_type === "event"`**: Là calendar event từ bảng `calendar`, `task_id` là `null`, không có `files_count`, `submissions_count`

---

## ✅ Checklist Cho Frontend

- [ ] Lấy `id`, `title`, `start_time`, `end_time` từ mỗi event
- [ ] Lấy `status`, `priority`, `event_type` để styling
- [ ] Parse `start_time` để xác định ngày hiển thị trên lưới lịch
- [ ] Filter events theo ngày được chọn
- [ ] Hiển thị `title` trong danh sách sự kiện
- [ ] Hiển thị `priority` và `status` dưới dạng nhãn (HIGH, IN_PROGRESS, etc.)
- [ ] Dùng `event_type` để phân biệt Task vs Calendar Event
- [ ] Dùng `id` để click vào sự kiện và lấy chi tiết
- [ ] Xử lý trường hợp `creator.name` là "Unknown"
- [ ] Xử lý trường hợp `task_id` là `null` (cho calendar events)

---

**Last Updated**: 2025-01-20  
**Version**: 1.0.0

