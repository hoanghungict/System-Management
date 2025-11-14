# 📅 Hướng Dẫn Frontend - Calendar Module

> Quick Start cho FE (Admin/Lecturer/Student)
- Base URLs: `/api/v1/calendar` (Admin), `/api/v1/lecturer-calendar` (Lecturer), `/api/v1/student-calendar` (Student)
- Base host mặc định: `http://localhost:8082` (có thể cấu hình qua `NEXT_PUBLIC_API_URL`)
- Headers bắt buộc: `Authorization: Bearer <JWT>`, `Content-Type: application/json`

Ví dụ khởi tạo Axios nhanh:

```ts
import axios from 'axios';

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8082',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' }
});

export const getBasePath = (role: 'admin' | 'lecturer' | 'student') => {
  if (role === 'admin') return '/api/v1/calendar';
  if (role === 'lecturer') return '/api/v1/lecturer-calendar';
  return '/api/v1/student-calendar';
};
```


## 📋 Mục Lục

1. [Tổng Quan](#tổng-quan)
2. [API Endpoints](#api-endpoints)
3. [TypeScript Interfaces](#typescript-interfaces)
4. [Implementation Guide](#implementation-guide)
5. [Examples](#examples)
6. [Best Practices](#best-practices)

---

## 📖 Tổng Quan

Calendar Module cung cấp chức năng hiển thị và quản lý lịch cho:
- **Admin**: Xem tất cả events trong hệ thống
- **Lecturer**: Xem tasks họ tạo VÀ tasks được assign cho họ
- **Student**: Xem tasks được assign cho họ

### 🎯 Tính Năng Chính

1. **Calendar Views**: Month, Week, Day views
2. **Event Management**: Xem, tạo, cập nhật, xóa events (lecturer only)
3. **Task Integration**: Tự động sync tasks vào calendar
4. **Filtering**: Lọc theo status, priority, date range
5. **Statistics**: Đếm events theo trạng thái

---

## 🔗 API Endpoints

### 📚 Base URLs

- **Admin**: `/api/v1/calendar`
- **Lecturer**: `/api/v1/lecturer-calendar`
- **Student**: `/api/v1/student-calendar`

### 📊 Lecturer Calendar Endpoints

#### 1. **GET** `/api/v1/lecturer-calendar/events`

Lấy danh sách events của lecturer (có pagination)

**Query Parameters:**
```typescript
{
  page?: number;           // Default: 1
  per_page?: number;        // Default: 15
  status?: string;          // pending, in_progress, completed, overdue
  priority?: string;       // low, medium, high, urgent
  date_from?: string;      // Y-m-d format
  date_to?: string;        // Y-m-d format
  search?: string;         // Search in title/description
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lecturer events retrieved successfully",
  "data": [
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
        "type": "lecturer"
      },
      "receivers": [
        {
          "id": 20,
          "type": "student"
        }
      ],
      "files_count": 2,
      "submissions_count": 5
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4,
    "from": 1,
    "to": 15
  }
}
```

#### 2. **GET** `/api/v1/lecturer-calendar/events/by-date?date=2025-02-15`

Lấy events theo ngày cụ thể

**Query Parameters:**
- `date` (required): Format `Y-m-d` (e.g., `2025-02-15`)

**Response:**
```json
{
  "success": true,
  "message": "Events by date retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-02-15 23:59:59",
      "end": "2025-02-15 23:59:59",
      // ... same structure as above
    }
  ]
}
```

#### 3. **GET** `/api/v1/lecturer-calendar/events/by-range?start=2025-02-01&end=2025-02-28`

Lấy events theo khoảng thời gian

**Query Parameters:**
- `start` (required): Start date `Y-m-d` hoặc `Y-m-d H:i:s`
- `end` (required): End date `Y-m-d` hoặc `Y-m-d H:i:s`

**Response:**
```json
{
  "success": true,
  "message": "Events by range retrieved successfully",
  "data": [
    // Array of events in the date range
  ]
}
```

#### 4. **GET** `/api/v1/lecturer-calendar/events/upcoming?limit=10`

Lấy events sắp tới (trong 30 ngày)

**Query Parameters:**
- `limit` (optional): Số lượng events tối đa (default: 10)

**Response:**
```json
{
  "success": true,
  "message": "Upcoming events retrieved successfully",
  "data": [
    // Array of upcoming events
  ]
}
```

#### 5. **GET** `/api/v1/lecturer-calendar/events/overdue`

Lấy events quá hạn

**Response:**
```json
{
  "success": true,
  "message": "Overdue events retrieved successfully",
  "data": [
    // Array of overdue events
  ]
}
```

#### 6. **GET** `/api/v1/lecturer-calendar/events/count-by-status`

Đếm events theo trạng thái

**Response:**
```json
{
  "success": true,
  "message": "Events count by status retrieved successfully",
  "data": {
    "total": 50,
    "pending": 20,
    "in_progress": 15,
    "completed": 10,
    "overdue": 5,
    "upcoming": 30
  }
}
```

#### 7. **POST** `/api/v1/lecturer-calendar/events`

Tạo event mới (calendar event, không phải task)

**Request Body:**
```json
{
  "title": "Meeting",
  "description": "Team meeting",
  "start_time": "2025-02-20 14:00:00",
  "end_time": "2025-02-20 15:00:00",
  "event_type": "event"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Event created successfully",
  "data": {
    "id": 100,
    "title": "Meeting",
    "description": "Team meeting",
    "start_time": "2025-02-20 14:00:00",
    "end_time": "2025-02-20 15:00:00",
    "event_type": "event",
    "task_id": null,
    "status": "scheduled",
    "priority": "medium",
    "creator": {
      "id": 10,
      "type": "lecturer"
    }
  }
}
```

#### 8. **PUT** `/api/v1/lecturer-calendar/events/{eventId}`

Cập nhật event

**Request Body:**
```json
{
  "title": "Updated Meeting",
  "description": "Updated description",
  "start_time": "2025-02-20 15:00:00",
  "end_time": "2025-02-20 16:00:00"
}
```

#### 9. **DELETE** `/api/v1/lecturer-calendar/events/{eventId}`

Xóa event

**Response:**
```json
{
  "success": true,
  "message": "Event deleted successfully"
}
```

---

### 👨‍🎓 Student Calendar Endpoints

#### 1. **GET** `/api/v1/student-calendar/events`

Lấy danh sách events của student (có pagination)

**Query Parameters:** Tương tự lecturer

**Response:** Tương tự lecturer (chỉ events được assign cho student)

#### 2. **GET** `/api/v1/student-calendar/events/by-date?date=2025-02-15`

Lấy events theo ngày

#### 3. **GET** `/api/v1/student-calendar/events/by-range?start_date=2025-02-01&end_date=2025-02-28`

Lấy events theo khoảng thời gian

**Note:** Student endpoints dùng `start_date` và `end_date` thay vì `start` và `end`

#### 4. **GET** `/api/v1/student-calendar/events/upcoming?limit=10`

Lấy events sắp tới

#### 5. **GET** `/api/v1/student-calendar/events/overdue`

Lấy events quá hạn

#### 6. **GET** `/api/v1/student-calendar/events/count-by-status`

Đếm events theo trạng thái

#### 7. **GET** `/api/v1/student-calendar/reminders`

Lấy reminders (tạm thời mock)

#### 8. **POST** `/api/v1/student-calendar/setReminder`

Tạo reminder

**Request Body:**
```json
{
  "title": "Reminder for Assignment 1",
  "remind_at": "2025-02-14 09:00:00",
  "task_id": 1
}
```

---

### 👨‍💼 Admin Calendar Endpoints

#### 1. **GET** `/api/v1/calendar/events`

Lấy tất cả events (có pagination)

#### 2. **GET** `/api/v1/calendar/events/by-type?type=high`

Lấy events theo priority/type

#### 3. **GET** `/api/v1/calendar/events/recurring`

Lấy recurring events (tạm thời mock)

---

## 📝 TypeScript Interfaces

### Event Interfaces

```typescript
/**
 * Calendar Event Interface
 */
export interface CalendarEvent {
  id: number;
  title: string;
  description: string;
  start: string;              // ISO datetime string
  end: string;                 // ISO datetime string
  start_time: string;          // ISO datetime string
  end_time: string;            // ISO datetime string
  event_type: 'task' | 'event' | 'reminder';
  task_id: number | null;
  status: 'pending' | 'in_progress' | 'completed' | 'overdue' | 'scheduled';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  class_id?: number | null;
  creator: {
    id: number;
    type: 'admin' | 'lecturer' | 'student';
    name?: string;
  };
  receivers: Array<{
    id: number;
    type: 'admin' | 'lecturer' | 'student';
    name?: string;
  }>;
  files_count?: number;
  submissions_count?: number;
  created_at?: string;
  updated_at?: string;
}

/**
 * Event Count by Status
 */
export interface EventCountByStatus {
  total: number;
  pending: number;
  in_progress: number;
  completed: number;
  overdue: number;
  upcoming: number;
}

/**
 * Calendar API Response
 */
export interface CalendarApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  pagination?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from?: number;
    to?: number;
  };
  count?: number;
}

/**
 * Calendar Filters
 */
export interface CalendarFilters {
  page?: number;
  per_page?: number;
  status?: 'pending' | 'in_progress' | 'completed' | 'overdue';
  priority?: 'low' | 'medium' | 'high' | 'urgent';
  date_from?: string;      // Y-m-d format
  date_to?: string;       // Y-m-d format
  start_date?: string;     // Y-m-d format (for student)
  end_date?: string;       // Y-m-d format (for student)
  search?: string;
  limit?: number;
}

/**
 * Create Event Request
 */
export interface CreateEventRequest {
  title: string;
  description?: string;
  start_time: string;      // Y-m-d H:i:s format
  end_time: string;        // Y-m-d H:i:s format
  event_type?: 'event' | 'task' | 'reminder';
  task_id?: number | null;
}

/**
 * Update Event Request
 */
export interface UpdateEventRequest {
  title?: string;
  description?: string;
  start_time?: string;
  end_time?: string;
  event_type?: string;
}

/**
 * Set Reminder Request
 */
export interface SetReminderRequest {
  title: string;
  remind_at: string;       // Y-m-d H:i:s format
  task_id?: number;
}
```

---

## 💻 Implementation Guide

### 1. Setup Calendar Service

```typescript
// services/calendarService.ts

import axios, { AxiosInstance } from 'axios';
import {
  CalendarEvent,
  CalendarApiResponse,
  CalendarFilters,
  CreateEventRequest,
  UpdateEventRequest,
  SetReminderRequest,
  EventCountByStatus
} from '@/types/calendar';

class CalendarService {
  private api: AxiosInstance;
  private baseUrl: string;

  constructor(userRole: 'admin' | 'lecturer' | 'student') {
    this.api = axios.create({
      baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    // Setup base URL based on role
    switch (userRole) {
      case 'admin':
        this.baseUrl = '/api/v1/calendar';
        break;
      case 'lecturer':
        this.baseUrl = '/api/v1/lecturer-calendar';
        break;
      case 'student':
        this.baseUrl = '/api/v1/student-calendar';
        break;
    }

    // Add auth token interceptor
    this.api.interceptors.request.use((config) => {
      const token = localStorage.getItem('auth_token');
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    });
  }

  /**
   * Get events with pagination and filters
   */
  async getEvents(filters?: CalendarFilters): Promise<CalendarApiResponse<CalendarEvent[]>> {
    const response = await this.api.get<CalendarApiResponse<CalendarEvent[]>>(
      `${this.baseUrl}/events`,
      { params: filters }
    );
    return response.data;
  }

  /**
   * Get events by date
   */
  async getEventsByDate(date: string): Promise<CalendarApiResponse<CalendarEvent[]>> {
    const response = await this.api.get<CalendarApiResponse<CalendarEvent[]>>(
      `${this.baseUrl}/events/by-date`,
      { params: { date } }
    );
    return response.data;
  }

  /**
   * Get events by date range
   */
  async getEventsByRange(start: string, end: string): Promise<CalendarApiResponse<CalendarEvent[]>> {
    const params = this.baseUrl.includes('student')
      ? { start_date: start, end_date: end }
      : { start, end };

    const response = await this.api.get<CalendarApiResponse<CalendarEvent[]>>(
      `${this.baseUrl}/events/by-range`,
      { params }
    );
    return response.data;
  }

  /**
   * Get upcoming events
   */
  async getUpcomingEvents(limit = 10): Promise<CalendarApiResponse<CalendarEvent[]>> {
    const response = await this.api.get<CalendarApiResponse<CalendarEvent[]>>(
      `${this.baseUrl}/events/upcoming`,
      { params: { limit } }
    );
    return response.data;
  }

  /**
   * Get overdue events
   */
  async getOverdueEvents(): Promise<CalendarApiResponse<CalendarEvent[]>> {
    const response = await this.api.get<CalendarApiResponse<CalendarEvent[]>>(
      `${this.baseUrl}/events/overdue`
    );
    return response.data;
  }

  /**
   * Get events count by status
   */
  async getEventsCountByStatus(): Promise<CalendarApiResponse<EventCountByStatus>> {
    const response = await this.api.get<CalendarApiResponse<EventCountByStatus>>(
      `${this.baseUrl}/events/count-by-status`
    );
    return response.data;
  }

  /**
   * Create event (lecturer only)
   */
  async createEvent(eventData: CreateEventRequest): Promise<CalendarApiResponse<CalendarEvent>> {
    const response = await this.api.post<CalendarApiResponse<CalendarEvent>>(
      `${this.baseUrl}/events`,
      eventData
    );
    return response.data;
  }

  /**
   * Update event (lecturer only)
   */
  async updateEvent(
    eventId: number,
    eventData: UpdateEventRequest
  ): Promise<CalendarApiResponse<CalendarEvent>> {
    const response = await this.api.put<CalendarApiResponse<CalendarEvent>>(
      `${this.baseUrl}/events/${eventId}`,
      eventData
    );
    return response.data;
  }

  /**
   * Delete event (lecturer only)
   */
  async deleteEvent(eventId: number): Promise<CalendarApiResponse<void>> {
    const response = await this.api.delete<CalendarApiResponse<void>>(
      `${this.baseUrl}/events/${eventId}`
    );
    return response.data;
  }

  /**
   * Set reminder (student only)
   */
  async setReminder(reminderData: SetReminderRequest): Promise<CalendarApiResponse<any>> {
    const endpoint = this.baseUrl.includes('student') 
      ? `${this.baseUrl}/setReminder`
      : `${this.baseUrl}/reminders`;
      
    const response = await this.api.post<CalendarApiResponse<any>>(endpoint, reminderData);
    return response.data;
  }
}

export default CalendarService;
```

### 2. React Hook for Calendar

```typescript
// hooks/useCalendar.ts

import { useState, useEffect, useCallback } from 'react';
import { CalendarEvent, CalendarFilters, EventCountByStatus } from '@/types/calendar';
import CalendarService from '@/services/calendarService';
import { useAuth } from '@/hooks/useAuth';

export function useCalendar() {
  const { user } = useAuth();
  const [calendarService] = useState(
    () => new CalendarService(user?.role || 'student')
  );
  
  const [events, setEvents] = useState<CalendarEvent[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [pagination, setPagination] = useState<any>(null);
  const [statistics, setStatistics] = useState<EventCountByStatus | null>(null);

  /**
   * Load events with filters
   */
  const loadEvents = useCallback(async (filters?: CalendarFilters) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await calendarService.getEvents(filters);
      setEvents(response.data);
      setPagination(response.pagination || null);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to load events');
      setEvents([]);
    } finally {
      setLoading(false);
    }
  }, [calendarService]);

  /**
   * Load events by date range
   */
  const loadEventsByRange = useCallback(async (start: string, end: string) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await calendarService.getEventsByRange(start, end);
      setEvents(response.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to load events');
      setEvents([]);
    } finally {
      setLoading(false);
    }
  }, [calendarService]);

  /**
   * Load events by date
   */
  const loadEventsByDate = useCallback(async (date: string) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await calendarService.getEventsByDate(date);
      setEvents(response.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to load events');
      setEvents([]);
    } finally {
      setLoading(false);
    }
  }, [calendarService]);

  /**
   * Load upcoming events
   */
  const loadUpcomingEvents = useCallback(async (limit = 10) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await calendarService.getUpcomingEvents(limit);
      setEvents(response.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to load upcoming events');
      setEvents([]);
    } finally {
      setLoading(false);
    }
  }, [calendarService]);

  /**
   * Load overdue events
   */
  const loadOverdueEvents = useCallback(async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await calendarService.getOverdueEvents();
      setEvents(response.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to load overdue events');
      setEvents([]);
    } finally {
      setLoading(false);
    }
  }, [calendarService]);

  /**
   * Load statistics
   */
  const loadStatistics = useCallback(async () => {
    try {
      const response = await calendarService.getEventsCountByStatus();
      setStatistics(response.data);
    } catch (err: any) {
      console.error('Failed to load statistics:', err);
    }
  }, [calendarService]);

  /**
   * Create event (lecturer only)
   */
  const createEvent = useCallback(async (eventData: any) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await calendarService.createEvent(eventData);
      // Refresh events after creation
      await loadEvents();
      return response.data;
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to create event');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [calendarService, loadEvents]);

  /**
   * Update event (lecturer only)
   */
  const updateEvent = useCallback(async (eventId: number, eventData: any) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await calendarService.updateEvent(eventId, eventData);
      // Refresh events after update
      await loadEvents();
      return response.data;
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to update event');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [calendarService, loadEvents]);

  /**
   * Delete event (lecturer only)
   */
  const deleteEvent = useCallback(async (eventId: number) => {
    setLoading(true);
    setError(null);
    
    try {
      await calendarService.deleteEvent(eventId);
      // Refresh events after deletion
      await loadEvents();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to delete event');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [calendarService, loadEvents]);

  return {
    events,
    loading,
    error,
    pagination,
    statistics,
    loadEvents,
    loadEventsByRange,
    loadEventsByDate,
    loadUpcomingEvents,
    loadOverdueEvents,
    loadStatistics,
    createEvent,
    updateEvent,
    deleteEvent,
  };
}
```

### 3. Calendar Component Example

```typescript
// components/Calendar/CalendarView.tsx

import React, { useState, useEffect } from 'react';
import { useCalendar } from '@/hooks/useCalendar';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { CalendarEvent } from '@/types/calendar';

export function CalendarView() {
  const {
    events,
    loading,
    error,
    loadEventsByRange,
    loadStatistics,
    statistics
  } = useCalendar();

  const [currentDate, setCurrentDate] = useState(new Date());

  useEffect(() => {
    // Load statistics on mount
    loadStatistics();
  }, [loadStatistics]);

  /**
   * Handle date range change (when calendar navigates)
   */
  const handleDatesSet = (arg: any) => {
    const start = arg.startStr.split('T')[0]; // Get Y-m-d format
    const end = arg.endStr.split('T')[0];
    loadEventsByRange(start, end);
  };

  /**
   * Format events for FullCalendar
   */
  const formatEventsForCalendar = (events: CalendarEvent[]) => {
    return events.map(event => ({
      id: String(event.id),
      title: event.title,
      start: event.start || event.start_time,
      end: event.end || event.end_time,
      backgroundColor: getEventColor(event),
      borderColor: getEventColor(event),
      textColor: '#fff',
      extendedProps: {
        ...event
      }
    }));
  };

  /**
   * Get event color based on priority/status
   */
  const getEventColor = (event: CalendarEvent): string => {
    if (event.status === 'overdue') return '#dc2626'; // Red
    if (event.status === 'completed') return '#10b981'; // Green
    if (event.priority === 'urgent') return '#dc2626'; // Red
    if (event.priority === 'high') return '#f59e0b'; // Orange
    if (event.priority === 'medium') return '#3b82f6'; // Blue
    return '#6b7280'; // Gray
  };

  /**
   * Handle event click
   */
  const handleEventClick = (clickInfo: any) => {
    const event = clickInfo.event.extendedProps as CalendarEvent;
    // Open event detail modal or navigate to task detail
    console.log('Event clicked:', event);
  };

  if (loading && events.length === 0) {
    return <div>Loading calendar...</div>;
  }

  if (error) {
    return <div className="error">Error: {error}</div>;
  }

  return (
    <div className="calendar-container">
      {/* Statistics Bar */}
      {statistics && (
        <div className="calendar-statistics">
          <div>Total: {statistics.total}</div>
          <div>Pending: {statistics.pending}</div>
          <div>Upcoming: {statistics.upcoming}</div>
          <div>Overdue: {statistics.overdue}</div>
        </div>
      )}

      {/* FullCalendar */}
      <FullCalendar
        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
        initialView="dayGridMonth"
        headerToolbar={{
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        }}
        events={formatEventsForCalendar(events)}
        datesSet={handleDatesSet}
        eventClick={handleEventClick}
        height="auto"
        locale="vi" // Vietnamese locale if available
      />
    </div>
  );
}
```

### 4. Event Form Component

```typescript
// components/Calendar/EventForm.tsx

import React, { useState } from 'react';
import { useCalendar } from '@/hooks/useCalendar';
import { CreateEventRequest } from '@/types/calendar';

interface EventFormProps {
  onSuccess?: () => void;
  onCancel?: () => void;
}

export function EventForm({ onSuccess, onCancel }: EventFormProps) {
  const { createEvent, loading } = useCalendar();
  
  const [formData, setFormData] = useState<CreateEventRequest>({
    title: '',
    description: '',
    start_time: '',
    end_time: '',
    event_type: 'event'
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      await createEvent(formData);
      onSuccess?.();
    } catch (error) {
      console.error('Failed to create event:', error);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="event-form">
      <div>
        <label>Title *</label>
        <input
          type="text"
          value={formData.title}
          onChange={(e) => setFormData({ ...formData, title: e.target.value })}
          required
        />
      </div>

      <div>
        <label>Description</label>
        <textarea
          value={formData.description}
          onChange={(e) => setFormData({ ...formData, description: e.target.value })}
        />
      </div>

      <div>
        <label>Start Time *</label>
        <input
          type="datetime-local"
          value={formData.start_time}
          onChange={(e) => setFormData({ ...formData, start_time: e.target.value })}
          required
        />
      </div>

      <div>
        <label>End Time *</label>
        <input
          type="datetime-local"
          value={formData.end_time}
          onChange={(e) => setFormData({ ...formData, end_time: e.target.value })}
          required
        />
      </div>

      <div className="form-actions">
        <button type="submit" disabled={loading}>
          {loading ? 'Creating...' : 'Create Event'}
        </button>
        {onCancel && (
          <button type="button" onClick={onCancel}>
            Cancel
          </button>
        )}
      </div>
    </form>
  );
}
```

---

## 🎨 Examples

### Example 1: Display Calendar with Month View

```typescript
import { CalendarView } from '@/components/Calendar/CalendarView';

function CalendarPage() {
  return (
    <div className="calendar-page">
      <h1>Calendar</h1>
      <CalendarView />
    </div>
  );
}
```

### Example 2: Get Events for Current Month

```typescript
import { useCalendar } from '@/hooks/useCalendar';
import { useEffect } from 'react';

function MyComponent() {
  const { loadEventsByRange, events } = useCalendar();

  useEffect(() => {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
      .toISOString().split('T')[0];
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
      .toISOString().split('T')[0];

    loadEventsByRange(start, end);
  }, [loadEventsByRange]);

  return (
    <div>
      {events.map(event => (
        <div key={event.id}>{event.title}</div>
      ))}
    </div>
  );
}
```

### Example 3: Filter Events by Status

```typescript
import { useCalendar } from '@/hooks/useCalendar';

function FilteredEvents() {
  const { loadEvents, events } = useCalendar();

  const handleFilter = (status: string) => {
    loadEvents({ status, per_page: 20 });
  };

  return (
    <div>
      <div className="filters">
        <button onClick={() => handleFilter('pending')}>Pending</button>
        <button onClick={() => handleFilter('completed')}>Completed</button>
        <button onClick={() => handleFilter('overdue')}>Overdue</button>
      </div>
      
      <div className="events-list">
        {events.map(event => (
          <div key={event.id}>{event.title} - {event.status}</div>
        ))}
      </div>
    </div>
  );
}
```

---

## ✅ Best Practices

### 1. **Date Format Handling**

- **Backend**: Expects `Y-m-d` for dates, `Y-m-d H:i:s` for datetime
- **Frontend**: Use ISO format strings, convert before sending to API

```typescript
// Convert to backend format
const formatForBackend = (date: Date): string => {
  return date.toISOString().slice(0, 19).replace('T', ' ');
};

// Parse from backend format
const parseFromBackend = (dateString: string): Date => {
  return new Date(dateString.replace(' ', 'T'));
};
```

### 2. **Error Handling**

```typescript
try {
  const response = await calendarService.getEvents();
  // Handle success
} catch (error: any) {
  if (error.response?.status === 401) {
    // Handle unauthorized
  } else if (error.response?.status === 404) {
    // Handle not found
  } else {
    // Handle other errors
  }
}
```

### 3. **Loading States**

Always show loading indicators during API calls:

```typescript
const { loading, events } = useCalendar();

if (loading) {
  return <Spinner />;
}

return <Calendar events={events} />;
```

### 4. **Caching Strategy**

Cache events for current month/week to reduce API calls:

```typescript
const [cachedEvents, setCachedEvents] = useState<Map<string, CalendarEvent[]>>(new Map());

const getCachedEvents = (key: string) => {
  return cachedEvents.get(key);
};

const cacheEvents = (key: string, events: CalendarEvent[]) => {
  setCachedEvents(prev => new Map(prev.set(key, events)));
};
```

### 5. **Optimistic Updates**

For create/update/delete operations, update UI immediately:

```typescript
const createEvent = async (eventData: CreateEventRequest) => {
  // Optimistically add to UI
  const tempEvent = { ...eventData, id: Date.now() };
  setEvents(prev => [...prev, tempEvent]);
  
  try {
    const response = await calendarService.createEvent(eventData);
    // Replace temp event with real event
    setEvents(prev => prev.map(e => 
      e.id === tempEvent.id ? response.data : e
    ));
  } catch (error) {
    // Remove temp event on error
    setEvents(prev => prev.filter(e => e.id !== tempEvent.id));
  }
};
```

---

## 🔧 Troubleshooting

### Issue: Events not loading

**Solution:**
1. Check authentication token
2. Verify API endpoint URL
3. Check network tab for errors
4. Verify date format matches backend expectations

### Issue: Date format errors

**Solution:**
- Ensure dates are in `Y-m-d` format for date parameters
- Ensure datetime is in `Y-m-d H:i:s` format for datetime parameters

### Issue: Permission denied

**Solution:**
- Verify user role matches endpoint requirements
- Check JWT token permissions
- Ensure user has access to requested events

---

## 📚 Additional Resources

- [FullCalendar Documentation](https://fullcalendar.io/docs)
- [React Calendar Libraries](https://react-day-picker.js.org/)
- [Date-fns](https://date-fns.org/) for date manipulation

---

**Last Updated:** 2025-01-20
**Version:** 2.0.0

