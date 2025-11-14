# 🎓 Student Task Module - UI/UX Design Prompt

## 📋 Tổng quan

Thiết kế giao diện người dùng cho **Sinh viên** trong module Task Management của hệ thống quản lý trường học. Frontend cần xây dựng một dashboard hoàn chỉnh với các chức năng quản lý bài tập, theo dõi deadline, nộp bài và xem thống kê.

---

## 🎯 Mục tiêu chính

Sinh viên cần một giao diện trực quan, dễ sử dụng để:
- 📚 **Xem và quản lý bài tập** được giao
- ⏰ **Theo dõi deadline** và tasks quá hạn
- 📤 **Nộp bài tập** với file đính kèm
- 📊 **Xem thống kê** tiến độ học tập
- 📅 **Quản lý lịch** các sự kiện và deadline
- 👥 **Xem thông tin lớp học** và bạn cùng lớp

---

## 🔐 Authentication & Authorization

**Tất cả API endpoints yêu cầu:**
- JWT Token trong header: `Authorization: Bearer <token>`
- Role: `student`
- Base URL: `/api/v1`

---

## 📱 Layout Structure

### **1. Dashboard Page (Trang chủ)**

**Route:** `/student/dashboard` hoặc `/student/tasks`

**Component Structure:**
```
┌─────────────────────────────────────────────────────────┐
│  Header: Logo | User Info | Notifications | Logout     │
├─────────────────────────────────────────────────────────┤
│  Sidebar Navigation                                     │
│  ├─ 📋 Tasks                                            │
│  ├─ 📅 Calendar                                         │
│  ├─ 📊 Statistics                                       │
│  ├─ 🏫 My Class                                         │
│  └─ ⚙️ Settings                                         │
├─────────────────────────────────────────────────────────┤
│  Main Content Area                                       │
│  ┌─────────────────┐ ┌─────────────────┐              │
│  │ Quick Stats     │ │ Upcoming Tasks  │              │
│  │ Cards           │ │ Widget          │              │
│  └─────────────────┘ └─────────────────┘              │
│  ┌──────────────────────────────────────┐              │
│  │ Task List/Table                      │              │
│  └──────────────────────────────────────┘              │
└─────────────────────────────────────────────────────────┘
```

**Quick Stats Cards:**
- 🟡 **Pending Tasks** - Số bài tập chờ xử lý
- ✅ **Submitted Tasks** - Số bài đã nộp
- 🔴 **Overdue Tasks** - Số bài quá hạn
- 📊 **Completion Rate** - Tỷ lệ hoàn thành

**API Endpoints:**
```typescript
// Get statistics
GET /api/v1/student-tasks/statistics

// Get pending tasks (for widget)
GET /api/v1/student-tasks/pending?limit=5

// Get all tasks
GET /api/v1/student-tasks?page=1&limit=15
```

---

### **2. Task List Page (Danh sách bài tập)**

**Route:** `/student/tasks`

**Features:**
- ✅ **Tabs/Filters:**
  - `All` - Tất cả tasks
  - `Pending` - Chờ xử lý (chưa nộp)
  - `Submitted` - Đã nộp
  - `Overdue` - Quá hạn

- 🔍 **Search & Filter:**
  - Search by title/description
  - Filter by status
  - Filter by priority (high, medium, low)
  - Sort by deadline, created date, priority

- 📋 **Task Card/Table View:**
  - Toggle giữa Card view và Table view
  - Hiển thị: Title, Description, Deadline, Status, Priority, Creator

**UI Components:**

#### **Task Card Component:**
```typescript
interface TaskCard {
  id: number;
  title: string;
  description: string;
  deadline: string; // ISO date
  status: 'pending' | 'in_progress' | 'submitted' | 'completed' | 'overdue';
  priority: 'high' | 'medium' | 'low';
  creator_name: string;
  files_count: number;
  submission_status: 'not_submitted' | 'submitted' | 'graded';
  grade?: number;
  days_remaining: number; // Negative if overdue
}
```

**Visual Design:**
- **Priority Badge:** 🔴 High | 🟡 Medium | 🟢 Low
- **Status Badge:** Color-coded badges
- **Deadline Warning:** Red background nếu < 3 ngày
- **Overdue Indicator:** Red border/shadow nếu quá hạn

**API Endpoints:**
```typescript
// All tasks
GET /api/v1/student-tasks?page=1&limit=15&status=pending

// Pending tasks
GET /api/v1/student-tasks/pending?page=1&limit=15

// Submitted tasks
GET /api/v1/student-tasks/submitted?page=1&limit=15

// Overdue tasks
GET /api/v1/student-tasks/overdue?page=1&limit=15
```

---

### **3. Task Detail Page (Chi tiết bài tập)**

**Route:** `/student/tasks/:taskId`

**Features:**
- 📄 **Task Information:**
  - Title, Description
  - Creator (Lecturer name)
  - Deadline với countdown timer
  - Priority và Status
  - Created date, Updated date

- 📎 **Files Section:**
  - List files đính kèm từ lecturer
  - Download files với tên gốc
  - Preview files (nếu hỗ trợ)

- 📤 **Submission Section:**
  - Form nộp bài:
    - Text content (textarea/rich text editor)
    - File upload (multiple files)
    - Preview files trước khi nộp
    - Delete files đã upload
  - Submission status:
    - Chưa nộp → Show "Submit" button
    - Đã nộp → Show submission details + "Update Submission" button
    - Đã chấm → Show grade + feedback

- 📊 **Dependencies (nếu có):**
  - Hiển thị tasks phụ thuộc (prerequisites)
  - Warning nếu chưa hoàn thành dependencies

**API Endpoints:**
```typescript
// Get task detail
GET /api/v1/student-tasks/{taskId}

// Get submission
GET /api/v1/student-tasks/{taskId}/submission

// Get task files
GET /api/v1/student-tasks/{taskId}/files

// Submit task
PUT /api/v1/student-tasks/{taskId}/submission
Body: {
  content: string,
  files?: File[]
}

// Upload file
POST /api/v1/student-tasks/{taskId}/upload-file
Body: FormData { file: File }

// Delete file
DELETE /api/v1/student-tasks/{taskId}/files/{fileId}

// Download file
GET /api/v1/student-tasks/{taskId}/files/{fileId}/download
```

**UI Flow:**
1. Load task detail → Show task info + files
2. Check submission status
3. If not submitted → Show submission form
4. If submitted → Show submission details + update option
5. If graded → Show grade + feedback prominently

---

### **4. Calendar Page (Lịch)**

**Route:** `/student/calendar`

**Features:**
- 📅 **Calendar View:**
  - Month view (default)
  - Week view
  - Day view
  - List view (agenda)

- 🎯 **Event Types:**
  - 📋 Task deadlines (color: blue)
  - ⏰ Task reminders (color: yellow)
  - 📝 Submission deadlines (color: red)
  - 📊 Exam dates (color: purple) - nếu có

- ⚙️ **Reminders:**
  - Set reminder cho task
  - Notification settings

**API Endpoints:**
```typescript
// Get all events
GET /api/v1/student-calendar/events

// Get events by date
GET /api/v1/student-calendar/events/by-date?date=2025-01-15

// Get events by range
GET /api/v1/student-calendar/events/by-range?start=2025-01-01&end=2025-01-31

// Get upcoming events
GET /api/v1/student-calendar/events/upcoming?limit=10

// Get overdue events
GET /api/v1/student-calendar/events/overdue

// Get events count by status
GET /api/v1/student-calendar/events/count-by-status

// Get reminders
GET /api/v1/student-calendar/reminders

// Set reminder
POST /api/v1/student-calendar/setReminder
Body: {
  task_id: number,
  reminder_date: string,
  reminder_type: 'email' | 'push' | 'both'
}
```

**UI Components:**
- FullCalendar.js hoặc similar calendar library
- Event popup/modal khi click vào event
- Quick add reminder button
- Color coding cho các loại events

---

### **5. Statistics Page (Thống kê)**

**Route:** `/student/statistics`

**Features:**
- 📊 **Charts & Metrics:**
  - Completion Rate (Pie chart)
  - Tasks by Status (Bar chart)
  - Tasks by Priority (Bar chart)
  - Submission Timeline (Line chart)
  - Grade Distribution (nếu có) (Histogram)

- 📈 **Key Metrics:**
  - Total tasks assigned
  - Completed tasks
  - Pending tasks
  - Overdue tasks
  - Average grade (nếu có)
  - On-time submission rate

**API Endpoints:**
```typescript
// Get statistics
GET /api/v1/student-tasks/statistics

// Response format:
{
  "success": true,
  "data": {
    "total_tasks": 50,
    "completed_tasks": 35,
    "pending_tasks": 10,
    "overdue_tasks": 5,
    "completion_rate": 70,
    "on_time_rate": 85,
    "average_grade": 8.5,
    "tasks_by_status": {...},
    "tasks_by_priority": {...},
    "submission_timeline": [...]
  }
}
```

**UI Components:**
- Chart.js hoặc Recharts cho visualizations
- Metric cards với icons
- Date range picker để filter statistics
- Export to PDF/Excel button

---

### **6. My Class Page (Lớp học của tôi)**

**Route:** `/student/class`

**Features:**
- 🏫 **Class Information:**
  - Class name, code
  - Department/Faculty
  - Lecturer(s) information
  - Class schedule

- 👥 **Classmates:**
  - List bạn cùng lớp
  - Search classmates
  - View profile (nếu có)

- 📢 **Announcements:**
  - Thông báo từ lecturer
  - Filter by date
  - Mark as read/unread

- 📚 **Attendance:**
  - Xem điểm danh
  - Attendance rate

**API Endpoints:**
```typescript
// Get class info
GET /api/v1/student-class

// Get classmates
GET /api/v1/student-class/classmates

// Get lecturers
GET /api/v1/student-class/lecturers

// Get announcements
GET /api/v1/student-class/announcements

// Get schedule
GET /api/v1/student-class/schedule

// Get attendance
GET /api/v1/student-class/attendance
```

---

## 🎨 Design System

### **Color Palette:**
- **Primary:** Blue (#3B82F6) - Main actions, links
- **Success:** Green (#10B981) - Completed, success states
- **Warning:** Yellow (#F59E0B) - Pending, warnings
- **Danger:** Red (#EF4444) - Overdue, errors, delete actions
- **Info:** Cyan (#06B6D4) - Information, calendar events
- **Gray Scale:** For text, borders, backgrounds

### **Typography:**
- **Headings:** Bold, 16px-24px
- **Body:** Regular, 14px-16px
- **Small text:** 12px-14px
- **Font Family:** System fonts hoặc Inter/Roboto

### **Components Style:**
- **Buttons:** Rounded corners (8px), padding 12px 24px
- **Cards:** Shadow (sm), rounded (12px), padding 16px-24px
- **Inputs:** Border 1px, rounded (8px), padding 12px
- **Badges:** Rounded-full, padding 4px 12px

### **Icons:**
- **Library:** Heroicons, Material Icons, hoặc Lucide
- **Size:** 16px-24px (tùy context)

---

## 🔔 Notifications & Alerts

### **Real-time Notifications:**
- **Task assigned** - "Bạn có bài tập mới: [Task Title]"
- **Deadline approaching** - "Bài tập '[Task Title]' sắp đến hạn (còn 1 ngày)"
- **Task overdue** - "⚠️ Bài tập '[Task Title]' đã quá hạn!"
- **Grade available** - "Bạn đã được chấm điểm cho '[Task Title]'"
- **Submission deadline** - "Deadline nộp bài '[Task Title]' là [date]"

### **Notification Badge:**
- Red dot với số lượng notifications chưa đọc
- Dropdown menu khi click vào icon
- Mark as read functionality

---

## 📱 Responsive Design

### **Breakpoints:**
- **Mobile:** < 768px
- **Tablet:** 768px - 1024px
- **Desktop:** > 1024px

### **Mobile Optimizations:**
- Bottom navigation bar thay vì sidebar
- Collapsible sections
- Swipe gestures cho task cards
- Pull-to-refresh
- Simplified calendar view

---

## 🎯 Key User Flows

### **Flow 1: Xem và Nộp Bài Tập**

```
1. Student mở Dashboard
   ↓
2. Click vào "Pending Tasks" tab
   ↓
3. Click vào một task card
   ↓
4. Task Detail page hiển thị:
   - Task info
   - Files từ lecturer
   - Submission form
   ↓
5. Student điền content và upload files
   ↓
6. Click "Submit" button
   ↓
7. Success message + Redirect to task list
   ↓
8. Task chuyển sang "Submitted" tab
```

### **Flow 2: Upload và Quản lý Files**

```
1. Trong Task Detail page
   ↓
2. Click "Upload File" button
   ↓
3. File picker mở → Chọn file(s)
   ↓
4. File được upload → Hiển thị trong list
   ↓
5. Có thể:
   - Preview file (click vào file)
   - Download file (với tên gốc)
   - Delete file (nếu chưa submit)
   ↓
6. Files được lưu khi submit task
```

### **Flow 3: Xem Thống Kê**

```
1. Click "Statistics" trong sidebar
   ↓
2. Statistics page load:
   - Fetch data từ API
   - Render charts
   ↓
3. Student có thể:
   - Filter by date range
   - View different chart types
   - Export to PDF/Excel
```

---

## 🛠️ Technical Requirements

### **Frontend Stack (Gợi ý):**
- **Framework:** React/Vue.js/Next.js
- **State Management:** Redux/Zustand/Pinia
- **UI Library:** Tailwind CSS + Headless UI / Material-UI / Ant Design
- **Charts:** Chart.js / Recharts / ApexCharts
- **Calendar:** FullCalendar.js / react-big-calendar
- **File Upload:** react-dropzone / vue-dropzone
- **HTTP Client:** Axios / Fetch API
- **Form Handling:** React Hook Form / Formik / VeeValidate

### **Key Libraries:**
```json
{
  "dependencies": {
    "axios": "^1.6.0",
    "react-router-dom": "^6.20.0",
    "tailwindcss": "^3.3.0",
    "@headlessui/react": "^1.7.0",
    "chart.js": "^4.4.0",
    "react-chartjs-2": "^5.2.0",
    "fullcalendar": "^6.1.0",
    "react-dropzone": "^14.2.0",
    "react-hook-form": "^7.48.0",
    "date-fns": "^2.30.0",
    "react-query": "^5.17.0"
  }
}
```

---

## 📊 API Response Examples

### **Task List Response:**
```json
{
  "success": true,
  "message": "Student tasks retrieved successfully",
  "data": [
    {
      "id": 125,
      "title": "Bài tập môn Lập trình Web",
      "description": "Xây dựng website...",
      "deadline": "2025-02-15 23:59:59",
      "status": "pending",
      "priority": "high",
      "creator_id": 5,
      "creator_type": "lecturer",
      "creator_name": "Nguyễn Văn A",
      "files_count": 2,
      "created_at": "2025-01-15 10:00:00",
      "days_remaining": 15
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4
  }
}
```

### **Task Detail Response:**
```json
{
  "success": true,
  "message": "Task retrieved successfully",
  "data": {
    "id": 125,
    "title": "Bài tập môn Lập trình Web",
    "description": "Xây dựng website...",
    "deadline": "2025-02-15 23:59:59",
    "status": "pending",
    "priority": "high",
    "creator_id": 5,
    "creator_type": "lecturer",
    "creator_name": "Nguyễn Văn A",
    "files": [
      {
        "id": 1,
        "file_name": "assignment.pdf",
        "file_url": "http://.../storage/task-files/125/abc.pdf",
        "size": 1024000,
        "created_at": "2025-01-15 10:00:00"
      }
    ],
    "files_count": 1,
    "receivers": [...],
    "created_at": "2025-01-15 10:00:00"
  }
}
```

### **Submission Response:**
```json
{
  "success": true,
  "message": "Task submission retrieved successfully",
  "data": {
    "id": 10,
    "task_id": 125,
    "student_id": 123,
    "content": "Đây là bài làm của em...",
    "status": "submitted",
    "grade": null,
    "feedback": null,
    "submitted_at": "2025-01-20 15:30:00",
    "files": [
      {
        "id": 5,
        "file_name": "submission.docx",
        "file_url": "...",
        "size": 2048000
      }
    ]
  }
}
```

---

## ✅ Checklist Implementation

### **Phase 1: Core Features**
- [ ] Dashboard với quick stats
- [ ] Task list với filters và tabs
- [ ] Task detail page
- [ ] Submission form
- [ ] File upload/download

### **Phase 2: Advanced Features**
- [ ] Calendar view
- [ ] Statistics page với charts
- [ ] My Class page
- [ ] Notifications system
- [ ] Reminders

### **Phase 3: Polish**
- [ ] Responsive design
- [ ] Loading states
- [ ] Error handling
- [ ] Empty states
- [ ] Animations/transitions

---

## 🎨 UI Mockups Suggestions

### **Dashboard:**
- Hero section với welcome message
- 4 metric cards (grid layout)
- Quick actions (nộp bài nhanh, xem deadline)
- Recent tasks list
- Upcoming deadlines widget

### **Task Card:**
```
┌─────────────────────────────────────┐
│ [Priority Badge] [Status Badge]    │
│                                     │
│ 📋 Bài tập môn Lập trình Web        │
│                                     │
│ 📝 Xây dựng website responsive...   │
│                                     │
│ 👨‍🏫 Nguyễn Văn A                    │
│ 📎 2 files                           │
│ ⏰ Deadline: 15/02/2025 (15 ngày)  │
│                                     │
│ [View Details] [Submit]            │
└─────────────────────────────────────┘
```

### **Task Detail:**
```
┌─────────────────────────────────────┐
│ ← Back                              │
│                                     │
│ 📋 Bài tập môn Lập trình Web        │
│ 🔴 High Priority | 🟡 Pending      │
│                                     │
│ 👨‍🏫 Lecturer: Nguyễn Văn A          │
│ ⏰ Deadline: 15/02/2025 23:59       │
│                                     │
│ ─────────────────────────────────── │
│ 📄 Description                      │
│ [Task description content...]       │
│                                     │
│ ─────────────────────────────────── │
│ 📎 Files from Lecturer (2)          │
│ • assignment.pdf [Download]          │
│ • guidelines.docx [Download]       │
│                                     │
│ ─────────────────────────────────── │
│ 📤 Your Submission                  │
│ [Submission form or details]        │
│                                     │
└─────────────────────────────────────┘
```

---

## 🚀 Quick Start Code Examples

### **React Hook cho Tasks:**
```typescript
import { useQuery, useMutation } from 'react-query';
import axios from 'axios';

export const useStudentTasks = (filters = {}) => {
  return useQuery({
    queryKey: ['student-tasks', filters],
    queryFn: async () => {
      const response = await axios.get('/api/v1/student-tasks', {
        params: filters,
        headers: {
          Authorization: `Bearer ${getToken()}`
        }
      });
      return response.data;
    }
  });
};

export const useSubmitTask = () => {
  return useMutation({
    mutationFn: async ({ taskId, data }) => {
      const response = await axios.put(
        `/api/v1/student-tasks/${taskId}/submission`,
        data,
        {
          headers: {
            Authorization: `Bearer ${getToken()}`,
            'Content-Type': 'application/json'
          }
        }
      );
      return response.data;
    }
  });
};
```

---

## 📝 Notes cho Frontend Developer

1. **Authentication:** Luôn gửi JWT token trong header
2. **Error Handling:** Xử lý tất cả error cases (401, 403, 404, 500)
3. **Loading States:** Hiển thị skeleton/loading khi fetch data
4. **Optimistic Updates:** Update UI ngay khi user thao tác
5. **Cache Management:** Sử dụng React Query hoặc SWR để cache
6. **File Handling:** Validate file size và type trước khi upload
7. **Date Formatting:** Format dates theo locale (VN: dd/mm/yyyy)
8. **Accessibility:** Đảm bảo WCAG 2.1 AA compliance

---

**Version:** 1.0.0  
**Last Updated:** 2025-01-15  
**Backend API Version:** Laravel 12 + Task Module

