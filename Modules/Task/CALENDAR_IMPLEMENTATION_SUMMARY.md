# 📅 Calendar Module - Tóm Tắt Implementation

## ✅ Đã Hoàn Thành

### 1. **Viết lại CalendarService** (`app/Services/CalendarService.php`)

**Cải tiến:**
- ✅ Xử lý đầy đủ cho cả 3 roles: Admin, Lecturer, Student
- ✅ Helper methods để query tasks cho từng role đúng logic
- ✅ Lecturer: Lấy cả tasks tạo VÀ tasks được assign
- ✅ Student: Chỉ lấy tasks được assign
- ✅ Admin: Lấy tất cả tasks
- ✅ Format response chuẩn với đầy đủ thông tin
- ✅ Error handling tốt hơn với logging
- ✅ Map tasks thành calendar events format

**Các method chính:**
- `getEventsByDate()` - Lấy events theo ngày
- `getEventsByRange()` - Lấy events theo khoảng thời gian
- `getUpcomingEvents()` - Lấy events sắp tới (30 ngày)
- `getOverdueEvents()` - Lấy events quá hạn
- `getEventsCountByStatus()` - Đếm events theo trạng thái
- `getAllEvents()` - Lấy tất cả events (Admin)
- `getEventsByType()` - Lấy events theo loại
- `mapTasksToEvents()` - Helper map tasks thành events

### 2. **Cải thiện LecturerCalendarRepository** (`app/Lecturer/Repositories/LecturerCalendarRepository.php`)

**Cải tiến:**
- ✅ Lấy cả tasks lecturer tạo VÀ tasks được assign cho lecturer
- ✅ Query logic đúng với receivers relationship
- ✅ CRUD operations đầy đủ cho calendar events
- ✅ Permission checking cho update/delete
- ✅ Merge tasks và calendar events khi lấy theo range
- ✅ Format response chuẩn

**Các method:**
- `getLecturerEvents()` - Lấy events với pagination và filters
- `createEvent()` - Tạo calendar event mới
- `updateEvent()` - Cập nhật event (check permission)
- `deleteEvent()` - Xóa event (check permission)
- `getEventsByDate()` - Events theo ngày
- `getEventsByRange()` - Events theo khoảng (merge tasks + calendar events)
- `getUpcomingEvents()` - Events sắp tới
- `getOverdueEvents()` - Events quá hạn
- `getEventsCountByStatus()` - Đếm theo status

### 3. **Tạo Hướng Dẫn Frontend** (`CALENDAR_FRONTEND_GUIDE.md`)

**Nội dung:**
- ✅ **API Endpoints**: Tất cả endpoints với query params và response format
- ✅ **TypeScript Interfaces**: Đầy đủ interfaces cho calendar
- ✅ **Implementation Guide**: 
  - CalendarService class
  - React Hook (useCalendar)
  - Calendar Component với FullCalendar
  - Event Form Component
- ✅ **Examples**: Các ví dụ thực tế
- ✅ **Best Practices**: Best practices cho frontend
- ✅ **Troubleshooting**: Các vấn đề thường gặp

---

## 📊 API Response Format Chuẩn

### Event Object
```json
{
  "id": 1,
  "title": "Assignment 1",
  "description": "Complete the assignment",
  "start": "2025-02-15 23:59:59",
  "end": "2025-02-15 23:59:59",
  "start_time": "2025-02-15 23:59:59",
  "end_time": "2025-02-15 23:59:59",
  "event_type": "task",
  "task_id": 1,
  "status": "pending",
  "priority": "high",
  "class_id": 5,
  "creator": {
    "id": 10,
    "type": "lecturer",
    "name": "Dr. Smith"
  },
  "receivers": [
    {
      "id": 20,
      "type": "student",
      "name": "John Doe"
    }
  ],
  "files_count": 2,
  "submissions_count": 5,
  "created_at": "2025-01-10 10:00:00",
  "updated_at": "2025-01-15 14:30:00"
}
```

---

## 🔑 Điểm Quan Trọng

### 1. **Lecturer Calendar Logic**

Lecturer có thể xem:
- ✅ Tasks họ tạo (`creator_id = lecturer_id AND creator_type = 'lecturer'`)
- ✅ Tasks được assign cho họ (có trong `receivers` với `receiver_id = lecturer_id AND receiver_type = 'lecturer'`)

### 2. **Student Calendar Logic**

Student chỉ xem:
- ✅ Tasks được assign cho họ (có trong `receivers` với `receiver_id = student_id AND receiver_type = 'student'`)

### 3. **Admin Calendar Logic**

Admin xem:
- ✅ Tất cả tasks trong hệ thống (không filter theo user)

### 4. **Date Format**

- **Query Parameters**: `Y-m-d` format (e.g., `2025-02-15`)
- **DateTime Fields**: `Y-m-d H:i:s` format (e.g., `2025-02-15 23:59:59`)
- **Response**: ISO datetime strings hoặc `Y-m-d H:i:s` format

---

## 🎯 Cách Sử Dụng Cho Frontend

### 1. **Setup Calendar Service**

```typescript
import CalendarService from '@/services/calendarService';

const calendarService = new CalendarService('lecturer'); // hoặc 'student', 'admin'
```

### 2. **Load Events**

```typescript
// Load events for current month
const start = '2025-02-01';
const end = '2025-02-28';
const response = await calendarService.getEventsByRange(start, end);
const events = response.data;
```

### 3. **Use React Hook**

```typescript
import { useCalendar } from '@/hooks/useCalendar';

function MyComponent() {
  const { 
    events, 
    loading, 
    loadEventsByRange,
    loadStatistics 
  } = useCalendar();

  useEffect(() => {
    loadEventsByRange('2025-02-01', '2025-02-28');
    loadStatistics();
  }, []);

  return <CalendarView events={events} />;
}
```

### 4. **Display with FullCalendar**

```typescript
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';

<FullCalendar
  plugins={[dayGridPlugin]}
  events={events.map(e => ({
    id: String(e.id),
    title: e.title,
    start: e.start,
    end: e.end,
    backgroundColor: getColorByPriority(e.priority)
  }))}
/>
```

---

## 📝 Lưu Ý Quan Trọng

### 1. **Authentication**

Tất cả endpoints yêu cầu JWT token:
```typescript
headers: {
  'Authorization': `Bearer ${token}`
}
```

### 2. **Error Handling**

Luôn handle errors:
```typescript
try {
  const response = await calendarService.getEvents();
} catch (error) {
  // Handle error
  console.error('Failed to load events:', error);
}
```

### 3. **Date Format Conversion**

Convert giữa frontend và backend format:
```typescript
// Frontend to Backend
const formatForBackend = (date: Date): string => {
  return date.toISOString().slice(0, 19).replace('T', ' ');
};

// Backend to Frontend
const parseFromBackend = (dateString: string): Date => {
  return new Date(dateString.replace(' ', 'T'));
};
```

### 4. **Caching**

Cache events để giảm API calls:
```typescript
// Cache events by date range
const cacheKey = `${start}_${end}`;
if (cachedEvents.has(cacheKey)) {
  return cachedEvents.get(cacheKey);
}
```

---

## 🚀 Next Steps (Tùy Chọn)

1. **Reminder System**: Implement reminder system hoàn chỉnh
2. **Recurring Events**: Implement recurring events
3. **Event Colors**: Custom colors based on priority/status
4. **Drag & Drop**: Implement drag & drop để change event date
5. **Event Details Modal**: Modal hiển thị chi tiết event
6. **Export Calendar**: Export calendar to iCal format

---

## 📚 Tài Liệu Tham Khảo

- **Backend API**: Xem `API_ENDPOINTS.md`
- **Frontend Guide**: Xem `CALENDAR_FRONTEND_GUIDE.md`
- **Task Module**: Xem Task module documentation

---

**Last Updated:** 2025-01-20
**Version:** 2.0.0

