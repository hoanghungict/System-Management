# 📚 Hướng Dẫn Tích Hợp Frontend - Lecturer Task Management (Complete Guide)

**Version:** 1.0.0  
**Last Updated:** 2025-01-XX  
**Base URL:** `http://localhost:8082/api/v1`

---

## 📋 Mục Lục

1. [Tổng Quan](#tổng-quan)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
4. [TypeScript Types](#typescript-types)
5. [React Hooks](#react-hooks)
6. [Use Cases & Examples](#use-cases--examples)
7. [Error Handling](#error-handling)
8. [Best Practices](#best-practices)

---

## 🎯 Tổng Quan

Lecturer (Giảng viên) có các chức năng chính:

1. ✅ **Tạo và quản lý tasks** - Tạo task, cập nhật, xóa
2. ✅ **Giao task cho sinh viên** - Assign tasks cho students
3. ✅ **Nhận task từ admin** - Xem và nộp tasks được admin giao
4. ✅ **Nộp task được giao** - Submit tasks được assign từ admin
5. ✅ **Upload files** - Upload files cho tasks
6. ✅ **Xem submissions** - Xem bài nộp của sinh viên
7. ✅ **Chấm điểm** - Grade và duyệt submissions của sinh viên
8. ✅ **Thống kê** - Xem statistics về tasks

---

## 🔐 Authentication

Tất cả endpoints yêu cầu JWT token trong header:

```typescript
Authorization: Bearer <JWT_TOKEN>
```

### Lấy Token từ JWT Payload:

```typescript
// Token được decode và lưu trong request attributes
interface JWTPayload {
  user_id: number;
  user_type: 'lecturer';
  name?: string;
  email?: string;
  // ... other fields
}
```

---

## 📡 API Endpoints

### Base URL: `/api/v1/lecturer-tasks`

| Endpoint | Method | Mô tả |
|----------|--------|-------|
| `/api/v1/lecturer-tasks` | GET | Lấy danh sách tasks (created + assigned) |
| `/api/v1/lecturer-tasks` | POST | Tạo task mới |
| `/api/v1/lecturer-tasks/created` | GET | Tasks đã tạo bởi lecturer |
| `/api/v1/lecturer-tasks/assigned` | GET | Tasks được giao từ admin |
| `/api/v1/lecturer-tasks/statistics` | GET | Thống kê tasks |
| `/api/v1/lecturer-tasks/{taskId}` | GET | Xem chi tiết task |
| `/api/v1/lecturer-tasks/{taskId}` | PUT | Cập nhật task |
| `/api/v1/lecturer-tasks/{taskId}` | DELETE | Xóa task |
| `/api/v1/lecturer-tasks/{taskId}/assign` | PATCH | Giao task cho sinh viên |
| `/api/v1/lecturer-tasks/{taskId}/revoke` | POST | Thu hồi task |
| `/api/v1/lecturer-tasks/{taskId}/upload-file` | POST | Upload single file |
| `/api/v1/lecturer-tasks/{taskId}/files` | POST | Upload multiple files |
| `/api/v1/lecturer-tasks/{taskId}/files/{fileId}` | DELETE | Xóa file |
| `/api/v1/lecturer-tasks/{taskId}/files/{fileId}/download` | GET | Download file |
| `/api/v1/lecturer-tasks/{taskId}/submit` | POST | Nộp task được giao từ admin |
| `/api/v1/lecturer-tasks/{taskId}/submission` | GET | Xem submission của lecturer |
| `/api/v1/lecturer-tasks/{taskId}/submission` | PUT | Cập nhật submission |
| `/api/v1/lecturer-tasks/{taskId}/submissions` | GET | Xem submissions của sinh viên |
| `/api/v1/lecturer-tasks/{taskId}/submissions/{submissionId}/grade` | POST | Chấm điểm submission |

---

## 📝 Chi Tiết Endpoints

### 1. Lấy Danh Sách Tasks

**Endpoint:** `GET /api/v1/lecturer-tasks`

**Query Parameters:**
```typescript
{
  page?: number;        // Mặc định: 1
  limit?: number;       // Mặc định: 15
  status?: string;      // pending, in_progress, completed, cancelled
  priority?: string;    // low, medium, high
  class_id?: number;
  date_from?: string;   // YYYY-MM-DD
  date_to?: string;     // YYYY-MM-DD
  search?: string;      // Tìm kiếm theo title/description
  sort_by?: string;     // Mặc định: created_at
  sort_order?: string;  // asc, desc (mặc định: desc)
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lecturer tasks retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "description": "Complete assignment",
      "status": "pending",
      "priority": "high",
      "deadline": "2025-12-31 23:59:59",
      "creator_id": 1,
      "creator_type": "lecturer",
      "created_at": "2025-01-01 10:00:00",
      "updated_at": "2025-01-01 10:00:00",
      "receivers": [],
      "files": []
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

---

### 2. Lấy Tasks Đã Tạo

**Endpoint:** `GET /api/v1/lecturer-tasks/created`

**Query Parameters:** (Giống như endpoint trên)

**Response:** (Giống như endpoint trên)

---

### 3. Lấy Tasks Được Giao Từ Admin

**Endpoint:** `GET /api/v1/lecturer-tasks/assigned`

**Query Parameters:** (Giống như endpoint trên)

**Response:** (Giống như endpoint trên)

---

### 4. Thống Kê Tasks

**Endpoint:** `GET /api/v1/lecturer-tasks/statistics`

**Response:**
```json
{
  "success": true,
  "message": "Lecturer statistics retrieved successfully",
  "data": {
    "total": 50,
    "pending": 10,
    "completed": 30,
    "in_progress": 5,
    "cancelled": 3,
    "overdue": 2
  }
}
```

---

### 5. Tạo Task Mới

**Endpoint:** `POST /api/v1/lecturer-tasks`

**Request Body:**
```json
{
  "title": "Assignment 1",
  "description": "Complete this assignment",
  "deadline": "2025-12-31 23:59:59",
  "priority": "high",
  "status": "pending",
  "class_id": 1,
  "receivers": [
    {
      "receiver_id": 1,
      "receiver_type": "student"
    },
    {
      "receiver_id": 2,
      "receiver_type": "student"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 123,
    "title": "Assignment 1",
    "description": "Complete this assignment",
    "status": "pending",
    "priority": "high",
    "deadline": "2025-12-31 23:59:59",
    "creator_id": 1,
    "creator_type": "lecturer",
    "created_at": "2025-01-01 10:00:00",
    "receivers": [...],
    "files": []
  }
}
```

---

### 6. Xem Chi Tiết Task

**Endpoint:** `GET /api/v1/lecturer-tasks/{taskId}`

**Response:**
```json
{
  "success": true,
  "message": "Task retrieved successfully",
  "data": {
    "id": 123,
    "title": "Assignment 1",
    "description": "Complete this assignment",
    "status": "pending",
    "priority": "high",
    "deadline": "2025-12-31 23:59:59",
    "creator_id": 1,
    "creator_type": "lecturer",
    "receivers": [
      {
        "id": 1,
        "receiver_id": 1,
        "receiver_type": "student",
        "student": {
          "id": 1,
          "full_name": "Nguyen Van A"
        }
      }
    ],
    "files": [
      {
        "id": 1,
        "name": "assignment.pdf",
        "path": "task-files/123/xxx.pdf",
        "size": 12345,
        "file_url": "http://localhost:8082/storage/task-files/123/xxx.pdf"
      }
    ]
  }
}
```

---

### 7. Cập Nhật Task

**Endpoint:** `PUT /api/v1/lecturer-tasks/{taskId}`

**Request Body:** (Giống như tạo task, nhưng tất cả fields đều optional)

**Response:** (Giống như response của GET task detail)

---

### 8. Xóa Task

**Endpoint:** `DELETE /api/v1/lecturer-tasks/{taskId}`

**Response:**
```json
{
  "success": true,
  "message": "Task deleted successfully"
}
```

---

### 9. Giao Task Cho Sinh Viên

**Endpoint:** `PATCH /api/v1/lecturer-tasks/{taskId}/assign`

**Request Body:**
```json
{
  "receiver_ids": [1, 2, 3],
  "receiver_type": "student"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Task assigned successfully",
  "data": {
    "id": 123,
    "receivers": [...]
  }
}
```

---

### 10. Thu Hồi Task

**Endpoint:** `POST /api/v1/lecturer-tasks/{taskId}/revoke`

**Request Body:**
```json
{
  "receiver_ids": [1, 2],
  "receiver_type": "student"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Task revoked successfully",
  "data": {
    "id": 123,
    "receivers": [...]
  }
}
```

---

### 11. Upload Single File

**Endpoint:** `POST /api/v1/lecturer-tasks/{taskId}/upload-file`

**Request:** `multipart/form-data`

**FormData:**
```
file: File
```

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 7,                              // ← File ID - QUAN TRỌNG!
    "task_id": 123,
    "lecturer_id": 1,
    "filename": "assignment.pdf",
    "path": "task-files/123/xxx.pdf",
    "size": 12345,
    "file_url": "http://localhost:8082/storage/task-files/123/xxx.pdf",
    "uploaded_at": "2025-01-01 10:00:00"
  }
}
```

---

### 12. Upload Multiple Files

**Endpoint:** `POST /api/v1/lecturer-tasks/{taskId}/files`

**Request:** `multipart/form-data`

**FormData:**
```
files: File[]  // Array of files
```

**Response (Single file):**
```json
{
  "success": true,
  "message": "File(s) uploaded successfully",
  "data": {
    "id": 7,
    "task_id": 123,
    "filename": "assignment.pdf",
    "file_url": "...",
    ...
  },
  "count": 1
}
```

**Response (Multiple files):**
```json
{
  "success": true,
  "message": "File(s) uploaded successfully",
  "data": [
    {
      "id": 7,
      "task_id": 123,
      "filename": "file1.pdf",
      ...
    },
    {
      "id": 8,
      "task_id": 123,
      "filename": "file2.pdf",
      ...
    }
  ],
  "count": 2
}
```

---

### 13. Xóa File

**Endpoint:** `DELETE /api/v1/lecturer-tasks/{taskId}/files/{fileId}`

**Response:**
```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

---

### 14. Download File

**Endpoint:** `GET /api/v1/lecturer-tasks/{taskId}/files/{fileId}/download`

**Response:** File stream với `Content-Disposition` header

---

### 15. Nộp Task Được Giao Từ Admin

**Endpoint:** `POST /api/v1/lecturer-tasks/{taskId}/submit`

**Request Body:**
```json
{
  "submission_content": "Nội dung bài nộp",
  "submission_files": [1, 2, 3],  // File IDs đã upload
  "submission_notes": "Ghi chú",
  "content": "Nội dung (alternative format)",
  "files": [1, 2, 3],  // Alternative format
  "notes": "Ghi chú (alternative format)"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Task submitted successfully",
  "data": {
    "id": 1,
    "task_id": 123,
    "submission_content": "Nội dung bài nộp",
    "submission_files": [1, 2, 3],
    "submitted_at": "2025-01-01 10:00:00",
    "status": "submitted"
  }
}
```

---

### 16. Xem Submission Của Lecturer

**Endpoint:** `GET /api/v1/lecturer-tasks/{taskId}/submission`

**Response:**
```json
{
  "success": true,
  "message": "Task submission retrieved successfully",
  "data": {
    "id": 1,
    "task_id": 123,
    "submission_content": "Nội dung bài nộp",
    "submission_files": [1, 2, 3],
    "submission_notes": "Ghi chú",
    "submitted_at": "2025-01-01 10:00:00",
    "status": "pending",
    "grade": null,
    "feedback": null,
    "graded_at": null,
    "files": [
      {
        "id": 1,
        "file_name": "file1.pdf",
        "file_url": "http://...",
        "file_size": 12345,
        "created_at": "2025-01-01 10:00:00"
      }
    ],
    "created_at": "2025-01-01 10:00:00",
    "updated_at": "2025-01-01 10:00:00"
  }
}
```

**Response (404 - Chưa có submission):**
```json
{
  "success": false,
  "message": "Chưa có bài nộp cho task này",
  "data": null
}
```

---

### 17. Cập Nhật Submission

**Endpoint:** `PUT /api/v1/lecturer-tasks/{taskId}/submission`

**Request Body:**
```json
{
  "submission_content": "Nội dung đã cập nhật",
  "submission_files": [1, 2, 3, 4],
  "submission_notes": "Ghi chú mới"
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
    "submission_content": "Nội dung đã cập nhật",
    "submission_files": [1, 2, 3, 4],
    "submitted_at": "2025-01-01 11:00:00",
    ...
  }
}
```

---

### 18. Xem Submissions Của Sinh Viên

**Endpoint:** `GET /api/v1/lecturer-tasks/{taskId}/submissions`

**Response:**
```json
{
  "success": true,
  "message": "Task submissions retrieved successfully",
  "data": [
    {
      "id": 1,
      "task_id": 123,
      "student_id": 1,
      "student_name": "Nguyen Van A",
      "submission_content": "Bài làm của sinh viên",
      "submitted_at": "2025-01-01 10:00:00",
      "status": "pending",
      "grade": null,
      "feedback": null,
      "graded_at": null,
      "graded_by": null,
      "files": [
        {
          "id": 1,
          "file_name": "assignment.pdf",
          "file_url": "http://...",
          "file_size": 12345
        }
      ],
      "created_at": "2025-01-01 10:00:00",
      "updated_at": "2025-01-01 10:00:00"
    }
  ],
  "count": 1
}
```

---

### 19. Chấm Điểm Submission

**Endpoint:** `POST /api/v1/lecturer-tasks/{taskId}/submissions/{submissionId}/grade`

**Request Body (Đạt - Graded):**
```json
{
  "status": "graded",
  "grade": 8.5,
  "feedback": "Bài làm tốt, nhưng cần cải thiện phần trình bày"
}
```

**Request Body (Chưa đạt - Returned):**
```json
{
  "status": "returned",
  "feedback": "Bài làm chưa đạt yêu cầu, vui lòng làm lại"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Submission graded successfully",
  "data": {
    "id": 1,
    "task_id": 123,
    "student_id": 1,
    "submission_content": "Bài làm của sinh viên",
    "submitted_at": "2025-01-01 10:00:00",
    "status": "graded",
    "grade": 8.5,
    "feedback": "Bài làm tốt, nhưng cần cải thiện phần trình bày",
    "graded_at": "2025-01-01 12:00:00",
    "graded_by": 1,
    "files": [...],
    "updated_at": "2025-01-01 12:00:00"
  }
}
```

**Validation Rules:**
- `status` là bắt buộc và phải là `"graded"` hoặc `"returned"`
- Nếu `status = "graded"` thì `grade` là bắt buộc (0-10)
- `feedback` là optional

---

## 📘 TypeScript Types

```typescript
// ==================== Task Types ====================

export interface Task {
  id: number;
  title: string;
  description: string;
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  priority: 'low' | 'medium' | 'high';
  deadline: string | null;
  creator_id: number;
  creator_type: 'lecturer' | 'admin';
  class_id?: number;
  created_at: string;
  updated_at: string;
  receivers?: TaskReceiver[];
  files?: TaskFile[];
}

export interface TaskReceiver {
  id: number;
  receiver_id: number;
  receiver_type: 'student' | 'lecturer';
  student?: {
    id: number;
    full_name: string;
  };
}

export interface TaskFile {
  id: number;
  task_id: number;
  name: string;
  path: string;
  size: number;
  file_url: string;
  created_at: string;
}

// ==================== Submission Types ====================

export interface LecturerSubmission {
  id: number;
  task_id: number;
  submission_content: string | null;
  submission_files: number[];
  submission_notes: string | null;
  submitted_at: string | null;
  status: 'pending' | 'submitted' | 'graded' | 'returned';
  grade: number | null;
  feedback: string | null;
  graded_at: string | null;
  files: TaskFile[];
  created_at: string;
  updated_at: string;
}

export interface StudentSubmission {
  id: number;
  task_id: number;
  student_id: number;
  student_name: string;
  submission_content: string | null;
  submitted_at: string | null;
  status: 'pending' | 'submitted' | 'graded' | 'returned';
  grade: number | null;
  feedback: string | null;
  graded_at: string | null;
  graded_by: number | null;
  files: TaskFile[];
  created_at: string;
  updated_at: string;
}

// ==================== Statistics Types ====================

export interface LecturerStatistics {
  total: number;
  pending: number;
  completed: number;
  in_progress: number;
  cancelled: number;
  overdue: number;
}

// ==================== Request Types ====================

export interface CreateTaskRequest {
  title: string;
  description: string;
  deadline?: string;
  priority?: 'low' | 'medium' | 'high';
  status?: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  class_id?: number;
  receivers?: Array<{
    receiver_id: number;
    receiver_type: 'student' | 'lecturer';
  }>;
}

export interface UpdateTaskRequest extends Partial<CreateTaskRequest> {}

export interface AssignTaskRequest {
  receiver_ids: number[];
  receiver_type: 'student' | 'lecturer';
}

export interface SubmitTaskRequest {
  submission_content?: string;
  submission_files?: number[];
  submission_notes?: string;
  // Alternative format
  content?: string;
  files?: number[];
  notes?: string;
}

export interface GradeSubmissionRequest {
  status: 'graded' | 'returned';
  grade?: number;  // Required if status = 'graded' (0-10)
  feedback?: string;
}

// ==================== Response Types ====================

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data?: T;
  error?: string;
}

export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface UploadFileResponse {
  success: boolean;
  message: string;
  data: {
    id: number;
    task_id: number;
    lecturer_id: number;
    filename: string;
    path: string;
    size: number;
    file_url: string;
    uploaded_at: string;
  };
}

export interface UploadFilesResponse {
  success: boolean;
  message: string;
  data: UploadFileResponse['data'] | UploadFileResponse['data'][];
  count: number;
}
```

---

## ⚛️ React Hooks

```typescript
// ==================== useLecturerTasks.tsx ====================

import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8082/api/v1/lecturer-tasks';

// Get auth token (adjust based on your auth implementation)
const getAuthToken = () => {
  return localStorage.getItem('token') || '';
};

// ==================== Query Hooks ====================

export function useLecturerTasks(filters?: {
  page?: number;
  limit?: number;
  status?: string;
  priority?: string;
  search?: string;
}) {
  return useQuery({
    queryKey: ['lecturer-tasks', filters],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (filters?.page) params.append('page', filters.page.toString());
      if (filters?.limit) params.append('limit', filters.limit.toString());
      if (filters?.status) params.append('status', filters.status);
      if (filters?.priority) params.append('priority', filters.priority);
      if (filters?.search) params.append('search', filters.search);

      const response = await axios.get(`${API_BASE_URL}?${params}`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    }
  });
}

export function useCreatedTasks(filters?: any) {
  return useQuery({
    queryKey: ['lecturer-tasks', 'created', filters],
    queryFn: async () => {
      const params = new URLSearchParams();
      Object.entries(filters || {}).forEach(([key, value]) => {
        if (value) params.append(key, value.toString());
      });

      const response = await axios.get(`${API_BASE_URL}/created?${params}`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    }
  });
}

export function useAssignedTasks(filters?: any) {
  return useQuery({
    queryKey: ['lecturer-tasks', 'assigned', filters],
    queryFn: async () => {
      const params = new URLSearchParams();
      Object.entries(filters || {}).forEach(([key, value]) => {
        if (value) params.append(key, value.toString());
      });

      const response = await axios.get(`${API_BASE_URL}/assigned?${params}`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    }
  });
}

export function useTaskDetail(taskId: number) {
  return useQuery({
    queryKey: ['lecturer-tasks', taskId],
    queryFn: async () => {
      const response = await axios.get(`${API_BASE_URL}/${taskId}`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    enabled: !!taskId
  });
}

export function useLecturerStatistics() {
  return useQuery({
    queryKey: ['lecturer-tasks', 'statistics'],
    queryFn: async () => {
      const response = await axios.get(`${API_BASE_URL}/statistics`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    }
  });
}

export function useTaskSubmissions(taskId: number) {
  return useQuery({
    queryKey: ['lecturer-tasks', taskId, 'submissions'],
    queryFn: async () => {
      const response = await axios.get(`${API_BASE_URL}/${taskId}/submissions`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    enabled: !!taskId
  });
}

export function useLecturerSubmission(taskId: number) {
  return useQuery({
    queryKey: ['lecturer-tasks', taskId, 'submission'],
    queryFn: async () => {
      const response = await axios.get(`${API_BASE_URL}/${taskId}/submission`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    enabled: !!taskId,
    retry: false // Don't retry on 404
  });
}

// ==================== Mutation Hooks ====================

export function useCreateTask() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: CreateTaskRequest) => {
      const response = await axios.post(API_BASE_URL, data, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks'] });
    }
  });
}

export function useUpdateTask() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ taskId, data }: { taskId: number; data: UpdateTaskRequest }) => {
      const response = await axios.put(`${API_BASE_URL}/${taskId}`, data, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks'] });
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId] });
    }
  });
}

export function useDeleteTask() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (taskId: number) => {
      const response = await axios.delete(`${API_BASE_URL}/${taskId}`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks'] });
    }
  });
}

export function useAssignTask() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ taskId, data }: { taskId: number; data: AssignTaskRequest }) => {
      const response = await axios.patch(`${API_BASE_URL}/${taskId}/assign`, data, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId] });
    }
  });
}

export function useUploadFile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ taskId, file }: { taskId: number; file: File }) => {
      const formData = new FormData();
      formData.append('file', file);

      const response = await axios.post(`${API_BASE_URL}/${taskId}/upload-file`, formData, {
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'Content-Type': 'multipart/form-data'
        }
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId] });
    }
  });
}

export function useUploadFiles() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ taskId, files }: { taskId: number; files: File[] }) => {
      const formData = new FormData();
      files.forEach(file => {
        formData.append('files[]', file);
      });

      const response = await axios.post(`${API_BASE_URL}/${taskId}/files`, formData, {
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'Content-Type': 'multipart/form-data'
        }
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId] });
    }
  });
}

export function useDeleteFile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ taskId, fileId }: { taskId: number; fileId: number }) => {
      const response = await axios.delete(`${API_BASE_URL}/${taskId}/files/${fileId}`, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId] });
    }
  });
}

export function useSubmitTask() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ taskId, data }: { taskId: number; data: SubmitTaskRequest }) => {
      const response = await axios.post(`${API_BASE_URL}/${taskId}/submit`, data, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId, 'submission'] });
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId] });
    }
  });
}

export function useUpdateSubmission() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ taskId, data }: { taskId: number; data: SubmitTaskRequest }) => {
      const response = await axios.put(`${API_BASE_URL}/${taskId}/submission`, data, {
        headers: { Authorization: `Bearer ${getAuthToken()}` }
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId, 'submission'] });
    }
  });
}

export function useGradeSubmission() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({
      taskId,
      submissionId,
      data
    }: {
      taskId: number;
      submissionId: number;
      data: GradeSubmissionRequest;
    }) => {
      const response = await axios.post(
        `${API_BASE_URL}/${taskId}/submissions/${submissionId}/grade`,
        data,
        {
          headers: { Authorization: `Bearer ${getAuthToken()}` }
        }
      );
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({
        queryKey: ['lecturer-tasks', variables.taskId, 'submissions']
      });
    }
  });
}
```

---

## 💡 Use Cases & Examples

### Example 1: Tạo Task và Upload Files

```typescript
import { useCreateTask, useUploadFile } from './hooks/useLecturerTasks';

function CreateTaskForm() {
  const createTask = useCreateTask();
  const uploadFile = useUploadFile();
  const [fileIds, setFileIds] = useState<number[]>([]);

  const handleFileUpload = async (taskId: number, file: File) => {
    try {
      const result = await uploadFile.mutateAsync({ taskId, file });
      if (result.success && result.data?.id) {
        setFileIds(prev => [...prev, result.data.id]);
      }
    } catch (error) {
      console.error('Upload failed:', error);
    }
  };

  const handleSubmit = async (formData: CreateTaskRequest) => {
    try {
      // 1. Tạo task
      const taskResult = await createTask.mutateAsync(formData);
      const taskId = taskResult.data.id;

      // 2. Upload files nếu có
      // Note: Upload files sau khi tạo task thành công
      // hoặc upload trước và dùng file IDs trong request

      // 3. Nếu cần upload sau khi tạo task:
      // for (const file of files) {
      //   await handleFileUpload(taskId, file);
      // }
    } catch (error) {
      console.error('Create task failed:', error);
    }
  };

  return (
    // Form UI
  );
}
```

### Example 2: Nộp Task Được Giao Từ Admin

```typescript
import { useUploadFile, useSubmitTask } from './hooks/useLecturerTasks';

function SubmitTaskForm({ taskId }: { taskId: number }) {
  const uploadFile = useUploadFile();
  const submitTask = useSubmitTask();
  const [fileIds, setFileIds] = useState<number[]>([]);
  const [content, setContent] = useState('');

  const handleFileSelect = async (file: File) => {
    try {
      const result = await uploadFile.mutateAsync({ taskId, file });
      if (result.success && result.data?.id) {
        setFileIds(prev => [...prev, result.data.id]);
      }
    } catch (error) {
      console.error('Upload failed:', error);
    }
  };

  const handleSubmit = async () => {
    try {
      await submitTask.mutateAsync({
        taskId,
        data: {
          submission_content: content,
          submission_files: fileIds
        }
      });
      alert('Nộp bài thành công!');
    } catch (error) {
      console.error('Submit failed:', error);
    }
  };

  return (
    <div>
      <textarea
        value={content}
        onChange={(e) => setContent(e.target.value)}
        placeholder="Nhập nội dung bài nộp"
      />
      <input
        type="file"
        onChange={(e) => {
          const file = e.target.files?.[0];
          if (file) handleFileSelect(file);
        }}
      />
      <button onClick={handleSubmit}>Nộp bài</button>
    </div>
  );
}
```

### Example 3: Chấm Điểm Submission

```typescript
import { useGradeSubmission } from './hooks/useLecturerTasks';

function GradeSubmissionForm({
  taskId,
  submissionId
}: {
  taskId: number;
  submissionId: number;
}) {
  const gradeSubmission = useGradeSubmission();
  const [grade, setGrade] = useState<number>(0);
  const [feedback, setFeedback] = useState('');
  const [status, setStatus] = useState<'graded' | 'returned'>('graded');

  const handleGrade = async () => {
    try {
      if (status === 'graded' && (!grade || grade < 0 || grade > 10)) {
        alert('Vui lòng nhập điểm từ 0-10');
        return;
      }

      await gradeSubmission.mutateAsync({
        taskId,
        submissionId,
        data: {
          status,
          grade: status === 'graded' ? grade : undefined,
          feedback
        }
      });
      alert('Chấm điểm thành công!');
    } catch (error) {
      console.error('Grade failed:', error);
    }
  };

  return (
    <div>
      <select value={status} onChange={(e) => setStatus(e.target.value as any)}>
        <option value="graded">Đạt</option>
        <option value="returned">Chưa đạt</option>
      </select>
      {status === 'graded' && (
        <input
          type="number"
          min="0"
          max="10"
          step="0.1"
          value={grade}
          onChange={(e) => setGrade(parseFloat(e.target.value))}
          placeholder="Điểm (0-10)"
        />
      )}
      <textarea
        value={feedback}
        onChange={(e) => setFeedback(e.target.value)}
        placeholder="Nhận xét"
      />
      <button onClick={handleGrade}>Chấm điểm</button>
    </div>
  );
}
```

### Example 4: Hiển Thị Danh Sách Tasks với Pagination

```typescript
import { useLecturerTasks } from './hooks/useLecturerTasks';

function TaskList() {
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState({ status: '', search: '' });
  const { data, isLoading, error } = useLecturerTasks({
    page,
    limit: 10,
    ...filters
  });

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error.message}</div>;

  return (
    <div>
      <input
        type="text"
        placeholder="Tìm kiếm..."
        onChange={(e) => setFilters({ ...filters, search: e.target.value })}
      />
      <select
        onChange={(e) => setFilters({ ...filters, status: e.target.value })}
      >
        <option value="">Tất cả</option>
        <option value="pending">Chờ xử lý</option>
        <option value="in_progress">Đang thực hiện</option>
        <option value="completed">Hoàn thành</option>
      </select>

      {data?.data?.map((task: Task) => (
        <div key={task.id}>
          <h3>{task.title}</h3>
          <p>{task.description}</p>
          <span>Status: {task.status}</span>
        </div>
      ))}

      <div>
        <button
          disabled={page === 1}
          onClick={() => setPage(page - 1)}
        >
          Previous
        </button>
        <span>Page {page} of {data?.pagination?.last_page}</span>
        <button
          disabled={page >= (data?.pagination?.last_page || 1)}
          onClick={() => setPage(page + 1)}
        >
          Next
        </button>
      </div>
    </div>
  );
}
```

---

## ⚠️ Error Handling

### Standard Error Response:

```json
{
  "success": false,
  "message": "Error message",
  "error": "Detailed error message (optional)"
}
```

### HTTP Status Codes:

- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (missing/invalid token)
- `403` - Forbidden (no permission)
- `404` - Not Found
- `422` - Unprocessable Entity (validation failed)
- `500` - Internal Server Error

### Error Handling Example:

```typescript
try {
  const result = await createTask.mutateAsync(data);
  if (result.success) {
    // Success
  }
} catch (error: any) {
  if (error.response) {
    // Server responded with error
    const status = error.response.status;
    const message = error.response.data?.message || 'An error occurred';

    switch (status) {
      case 401:
        // Redirect to login
        break;
      case 403:
        alert('Bạn không có quyền thực hiện hành động này');
        break;
      case 404:
        alert('Không tìm thấy tài nguyên');
        break;
      case 422:
        // Validation errors
        const errors = error.response.data?.errors;
        // Display validation errors
        break;
      default:
        alert(message);
    }
  } else {
    // Network error or other
    alert('Không thể kết nối đến server');
  }
}
```

---

## ✅ Best Practices

### 1. File Upload Flow

```typescript
// ✅ Đúng: Upload file trước, lấy file ID, sau đó submit với file IDs
const fileIds: number[] = [];

// Upload files
for (const file of files) {
  const uploadResult = await uploadFile.mutateAsync({ taskId, file });
  if (uploadResult.success && uploadResult.data?.id) {
    fileIds.push(uploadResult.data.id);
  }
}

// Submit với file IDs
await submitTask.mutateAsync({
  taskId,
  data: {
    submission_content: content,
    submission_files: fileIds  // ← Dùng file IDs
  }
});
```

### 2. Optimistic Updates

```typescript
const updateTask = useMutation({
  mutationFn: async ({ taskId, data }) => {
    // API call
  },
  onMutate: async (variables) => {
    // Cancel outgoing queries
    await queryClient.cancelQueries({ queryKey: ['lecturer-tasks', variables.taskId] });

    // Snapshot previous value
    const previous = queryClient.getQueryData(['lecturer-tasks', variables.taskId]);

    // Optimistically update
    queryClient.setQueryData(['lecturer-tasks', variables.taskId], (old: any) => ({
      ...old,
      data: { ...old.data, ...variables.data }
    }));

    return { previous };
  },
  onError: (err, variables, context) => {
    // Rollback on error
    queryClient.setQueryData(['lecturer-tasks', variables.taskId], context?.previous);
  },
  onSettled: (_, __, variables) => {
    // Refetch to ensure consistency
    queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', variables.taskId] });
  }
});
```

### 3. Handle Loading States

```typescript
const { data, isLoading, isFetching, isError, error } = useLecturerTasks();

// isLoading: true only on first load
// isFetching: true on any fetch (including refetch)
// Use isLoading for initial skeleton, isFetching for inline loading indicator
```

### 4. Cache Management

```typescript
// Invalidate related queries after mutations
onSuccess: () => {
  queryClient.invalidateQueries({ queryKey: ['lecturer-tasks'] });
  queryClient.invalidateQueries({ queryKey: ['lecturer-tasks', 'statistics'] });
}
```

### 5. Type Safety

```typescript
// Always type your API responses
const response = await axios.get<ApiResponse<Task>>(`${API_BASE_URL}/${taskId}`);
// TypeScript will enforce type safety
```

---

## 🔗 Related Documentation

- [Student Task Guide](./FRONTEND_UPDATE_GUIDE.md)
- [API Documentation](http://localhost:8082/api/documentation)
- [Backend Repository](https://github.com/your-repo)

---

## 📞 Support

Nếu có vấn đề hoặc câu hỏi, vui lòng liên hệ:
- **Email:** support@example.com
- **Slack:** #frontend-support

---

**Last Updated:** 2025-01-XX  
**Version:** 1.0.0

