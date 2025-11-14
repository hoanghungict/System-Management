# 🎯 Frontend Update Task API Guide - Next.js/React

**Version:** 1.0.0  
**Last Updated:** 2024-11-03  
**Status:** ✅ Ready for Production

---

## 📋 **Table of Contents**

1. [Permission Flow Overview](#permission-flow-overview)
2. [API Endpoints](#api-endpoints)
3. [TypeScript Types](#typescript-types)
4. [Next.js Implementation](#nextjs-implementation)
5. [Error Handling](#error-handling)
6. [Complete Examples](#complete-examples)
7. [Testing](#testing)

---

## 🔐 **Permission Flow Overview**

### **1. Admin - Toàn Quyền Update**

```typescript
✅ Admin có thể update BẤT KỲ task nào
- Không cần check creator_id
- Không cần check receiver
- Endpoint: PUT/PATCH /api/v1/admin-tasks/{id}
```

### **2. Lecturer - Update Task Họ Tạo HOẶC Được Assign**

```typescript
✅ Lecturer có thể update:
  1. Task họ tạo (creator_id === lecturer.id)
  2. Task họ là receiver (được assign)
  
❌ Lecturer KHÔNG thể update:
  - Task của lecturer khác tạo (không phải receiver)
  
- Endpoint: PUT/PATCH /api/v1/lecturer-tasks/{task}
```

### **3. Student - Chỉ Submit/Update Submission**

```typescript
✅ Student có thể:
  - Submit task: POST /api/v1/student-tasks/{task}/submit
  - Update submission: PUT /api/v1/student-tasks/{task}/submission
  - View submission: GET /api/v1/student-tasks/{task}/submission

❌ Student KHÔNG thể:
  - Update task metadata (title, description, deadline, etc.)
  - Delete task
  - Assign task
```

---

## 🌐 **API Endpoints**

### **1. Admin Update Task**

```typescript
PUT /api/v1/admin-tasks/{id}
PATCH /api/v1/admin-tasks/{id}

Headers:
  Authorization: Bearer <admin_token>
  Content-Type: application/json

Body (all fields optional):
{
  "title"?: string,
  "description"?: string,
  "deadline"?: string, // ISO 8601 format
  "due_date"?: string,
  "status"?: "pending" | "in_progress" | "completed" | "overdue" | "cancelled",
  "priority"?: "low" | "medium" | "high",
  "receivers"?: Array<{
    receiver_id: number,
    receiver_type: "student" | "lecturer" | "classes" | "department" | "all_students" | "all_lecturers"
  }>
}

Response 200:
{
  "success": true,
  "message": "Task updated successfully",
  "data": TaskResource
}

Response 403:
{
  "success": false,
  "message": "Access denied"
}
```

---

### **2. Lecturer Update Task**

```typescript
PUT /api/v1/lecturer-tasks/{task}
PATCH /api/v1/lecturer-tasks/{task}

Headers:
  Authorization: Bearer <lecturer_token>
  Content-Type: application/json

Body (all fields optional):
{
  "title"?: string,
  "description"?: string,
  "deadline"?: string,
  "due_date"?: string,
  "status"?: "pending" | "in_progress" | "completed" | "overdue" | "cancelled",
  "priority"?: "low" | "medium" | "high",
  "receivers"?: Array<{
    receiver_id: number,
    receiver_type: "student" | "lecturer" | "classes" | "department" | "all_students" | "all_lecturers"
  }>
}

Response 200:
{
  "success": true,
  "message": "Task updated successfully",
  "data": TaskResource
}

Response 403:
{
  "success": false,
  "message": "Access denied. You can only update tasks you created or tasks assigned to you."
}
```

---

### **3. Student Update Submission**

```typescript
PUT /api/v1/student-tasks/{task}/submission

Headers:
  Authorization: Bearer <student_token>
  Content-Type: application/json

Body:
{
  "submission_content"?: string,  // or "content"
  "submission_files"?: Array<number>,  // or "files" (array of file IDs)
  "submission_notes"?: string  // or "notes"
}

Response 200:
{
  "success": true,
  "message": "Task submission updated successfully",
  "data": SubmissionResource
}
```

---

## 📝 **TypeScript Types**

### **Create `/types/task.ts`:**

```typescript
// User Roles
export type UserRole = 'admin' | 'lecturer' | 'student';

// Task Status
export type TaskStatus = 
  | 'pending' 
  | 'in_progress' 
  | 'completed' 
  | 'overdue' 
  | 'cancelled';

// Task Priority
export type TaskPriority = 'low' | 'medium' | 'high';

// Receiver Type
export type ReceiverType = 
  | 'student' 
  | 'lecturer' 
  | 'classes' 
  | 'department' 
  | 'all_students' 
  | 'all_lecturers';

// Receiver Object
export interface TaskReceiver {
  receiver_id: number;
  receiver_type: ReceiverType;
}

// Update Task Request (for Admin & Lecturer)
export interface UpdateTaskRequest {
  title?: string;
  description?: string;
  deadline?: string; // ISO 8601 format: "2025-11-10T23:59:59"
  due_date?: string;
  status?: TaskStatus;
  priority?: TaskPriority;
  receivers?: TaskReceiver[];
}

// Update Submission Request (for Student)
export interface UpdateSubmissionRequest {
  submission_content?: string;
  submission_files?: number[]; // Array of file IDs
  submission_notes?: string;
  // Alternative field names (supported by backend)
  content?: string;
  files?: number[];
  notes?: string;
}

// API Response
export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data?: T;
  error?: string;
  errors?: Record<string, string[]>;
}

// Task Resource (from API)
export interface TaskResource {
  id: number;
  title: string;
  description?: string;
  deadline: string;
  due_date?: string;
  status: TaskStatus;
  priority: TaskPriority;
  creator_id: number;
  creator_type: 'lecturer' | 'student';
  created_at: string;
  updated_at: string;
  receivers?: TaskReceiver[];
  files?: TaskFileResource[];
}

// Task File Resource
export interface TaskFileResource {
  id: number;
  task_id: number;
  file_name: string;
  file_url: string;
  download_url: string;
  download_urls: {
    common: string;
    lecturer: string;
    admin: string;
  };
  size: number;
  path: string;
  created_at: string;
}

// Submission Resource
export interface SubmissionResource {
  id: number;
  task_id: number;
  student_id: number;
  submission_content?: string;
  submission_files: number[];
  submission_notes?: string;
  status: 'submitted' | 'graded' | 'returned';
  grade?: number;
  graded_at?: string;
  created_at: string;
  updated_at: string;
}
```

---

## ⚛️ **Next.js Implementation**

### **1. Create API Service: `/lib/api/task.ts`**

```typescript
import { ApiResponse, TaskResource, UpdateTaskRequest, UpdateSubmissionRequest, SubmissionResource } from '@/types/task';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8082';

/**
 * Get Authorization header
 */
function getAuthHeaders(token: string): HeadersInit {
  return {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  };
}

/**
 * Admin: Update Task
 * 
 * Admin có toàn quyền update bất kỳ task nào
 */
export async function adminUpdateTask(
  taskId: number,
  data: UpdateTaskRequest,
  token: string
): Promise<ApiResponse<TaskResource>> {
  const response = await fetch(
    `${API_BASE_URL}/api/v1/admin-tasks/${taskId}`,
    {
      method: 'PATCH', // Hoặc 'PUT'
      headers: getAuthHeaders(token),
      body: JSON.stringify(data),
    }
  );

  const result: ApiResponse<TaskResource> = await response.json();

  if (!response.ok) {
    throw new Error(result.message || 'Failed to update task');
  }

  return result;
}

/**
 * Lecturer: Update Task
 * 
 * Lecturer chỉ có thể update:
 * - Task họ tạo (creator)
 * - Task họ là receiver (assigned)
 */
export async function lecturerUpdateTask(
  taskId: number,
  data: UpdateTaskRequest,
  token: string
): Promise<ApiResponse<TaskResource>> {
  const response = await fetch(
    `${API_BASE_URL}/api/v1/lecturer-tasks/${taskId}`,
    {
      method: 'PATCH', // Hoặc 'PUT'
      headers: getAuthHeaders(token),
      body: JSON.stringify(data),
    }
  );

  const result: ApiResponse<TaskResource> = await response.json();

  if (!response.ok) {
    // Handle 403 - Permission denied
    if (response.status === 403) {
      throw new Error(
        'Access denied. You can only update tasks you created or tasks assigned to you.'
      );
    }
    throw new Error(result.message || 'Failed to update task');
  }

  return result;
}

/**
 * Student: Update Submission
 * 
 * Student chỉ có thể update submission của chính họ
 */
export async function studentUpdateSubmission(
  taskId: number,
  data: UpdateSubmissionRequest,
  token: string
): Promise<ApiResponse<SubmissionResource>> {
  const response = await fetch(
    `${API_BASE_URL}/api/v1/student-tasks/${taskId}/submission`,
    {
      method: 'PUT',
      headers: getAuthHeaders(token),
      body: JSON.stringify(data),
    }
  );

  const result: ApiResponse<SubmissionResource> = await response.json();

  if (!response.ok) {
    throw new Error(result.message || 'Failed to update submission');
  }

  return result;
}

/**
 * Generic Update Task function (auto-detect user role)
 */
export async function updateTask(
  taskId: number,
  data: UpdateTaskRequest,
  token: string,
  userRole: 'admin' | 'lecturer'
): Promise<ApiResponse<TaskResource>> {
  if (userRole === 'admin') {
    return adminUpdateTask(taskId, data, token);
  } else {
    return lecturerUpdateTask(taskId, data, token);
  }
}
```

---

### **2. Create React Hook: `/hooks/useUpdateTask.ts`**

```typescript
import { useState, useCallback } from 'react';
import { updateTask, adminUpdateTask, lecturerUpdateTask } from '@/lib/api/task';
import { ApiResponse, TaskResource, UpdateTaskRequest } from '@/types/task';
import { useAuth } from '@/hooks/useAuth'; // Your auth hook

interface UseUpdateTaskReturn {
  updateTask: (taskId: number, data: UpdateTaskRequest) => Promise<void>;
  updating: boolean;
  error: string | null;
  success: boolean;
}

export function useUpdateTask(): UseUpdateTaskReturn {
  const { token, userRole } = useAuth();
  const [updating, setUpdating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const handleUpdate = useCallback(
    async (taskId: number, data: UpdateTaskRequest) => {
      if (!token || !userRole) {
        setError('User not authenticated');
        return;
      }

      // Student cannot update task
      if (userRole === 'student') {
        setError('Students can only update submissions, not tasks');
        return;
      }

      setUpdating(true);
      setError(null);
      setSuccess(false);

      try {
        let result: ApiResponse<TaskResource>;

        if (userRole === 'admin') {
          result = await adminUpdateTask(taskId, data, token);
        } else {
          result = await lecturerUpdateTask(taskId, data, token);
        }

        if (result.success && result.data) {
          setSuccess(true);
          // Optionally: Refresh task list or navigate
        } else {
          setError(result.message || 'Failed to update task');
        }
      } catch (err: any) {
        setError(err.message || 'An error occurred while updating task');
        console.error('Update task error:', err);
      } finally {
        setUpdating(false);
      }
    },
    [token, userRole]
  );

  return {
    updateTask: handleUpdate,
    updating,
    error,
    success,
  };
}
```

---

### **3. Create React Hook: `/hooks/useUpdateSubmission.ts` (for Student)**

```typescript
import { useState, useCallback } from 'react';
import { studentUpdateSubmission } from '@/lib/api/task';
import { ApiResponse, SubmissionResource, UpdateSubmissionRequest } from '@/types/task';
import { useAuth } from '@/hooks/useAuth';

interface UseUpdateSubmissionReturn {
  updateSubmission: (taskId: number, data: UpdateSubmissionRequest) => Promise<void>;
  updating: boolean;
  error: string | null;
  success: boolean;
}

export function useUpdateSubmission(): UseUpdateSubmissionReturn {
  const { token, userRole } = useAuth();
  const [updating, setUpdating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const handleUpdate = useCallback(
    async (taskId: number, data: UpdateSubmissionRequest) => {
      if (!token) {
        setError('User not authenticated');
        return;
      }

      if (userRole !== 'student') {
        setError('Only students can update submissions');
        return;
      }

      setUpdating(true);
      setError(null);
      setSuccess(false);

      try {
        const result: ApiResponse<SubmissionResource> = 
          await studentUpdateSubmission(taskId, data, token);

        if (result.success && result.data) {
          setSuccess(true);
        } else {
          setError(result.message || 'Failed to update submission');
        }
      } catch (err: any) {
        setError(err.message || 'An error occurred while updating submission');
        console.error('Update submission error:', err);
      } finally {
        setUpdating(false);
      }
    },
    [token, userRole]
  );

  return {
    updateSubmission: handleUpdate,
    updating,
    error,
    success,
  };
}
```

---

### **4. Create Update Task Form Component: `/components/tasks/UpdateTaskForm.tsx`**

```typescript
'use client';

import { useState, useEffect } from 'react';
import { useUpdateTask } from '@/hooks/useUpdateTask';
import { TaskResource, UpdateTaskRequest } from '@/types/task';
import { useAuth } from '@/hooks/useAuth';

interface UpdateTaskFormProps {
  task: TaskResource;
  onSuccess?: (updatedTask: TaskResource) => void;
  onCancel?: () => void;
}

export function UpdateTaskForm({ task, onSuccess, onCancel }: UpdateTaskFormProps) {
  const { userRole } = useAuth();
  const { updateTask, updating, error, success } = useUpdateTask();

  // Form state
  const [formData, setFormData] = useState<UpdateTaskRequest>({
    title: task.title,
    description: task.description || '',
    deadline: task.deadline ? task.deadline.split('T')[0] : '', // Date only
    status: task.status,
    priority: task.priority,
    receivers: task.receivers || [],
  });

  // Update form data when task changes
  useEffect(() => {
    setFormData({
      title: task.title,
      description: task.description || '',
      deadline: task.deadline ? task.deadline.split('T')[0] : '',
      status: task.status,
      priority: task.priority,
      receivers: task.receivers || [],
    });
  }, [task]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // Prepare data
    const updateData: UpdateTaskRequest = {
      title: formData.title,
      description: formData.description || undefined,
      deadline: formData.deadline ? `${formData.deadline}T23:59:59` : undefined,
      status: formData.status,
      priority: formData.priority,
      receivers: formData.receivers?.length ? formData.receivers : undefined,
    };

    await updateTask(task.id, updateData);

    if (success && onSuccess) {
      // Refresh task data
      window.location.reload(); // Or use your state management
    }
  };

  // Check if user can edit this task
  const canEdit = 
    userRole === 'admin' || // Admin can edit all
    (userRole === 'lecturer' && (
      task.creator_id === /* current user id */ && 
      task.creator_type === 'lecturer'
    )) || 
    (userRole === 'lecturer' && (
      task.receivers?.some(r => 
        r.receiver_id === /* current user id */ && 
        r.receiver_type === 'lecturer'
      )
    ));

  if (!canEdit && userRole !== 'admin') {
    return (
      <div className="p-4 bg-yellow-50 border border-yellow-200 rounded">
        <p className="text-yellow-800">
          You can only update tasks you created or tasks assigned to you.
        </p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {/* Error Message */}
      {error && (
        <div className="p-3 bg-red-50 border border-red-200 rounded text-red-800">
          {error}
        </div>
      )}

      {/* Success Message */}
      {success && (
        <div className="p-3 bg-green-50 border border-green-200 rounded text-green-800">
          Task updated successfully!
        </div>
      )}

      {/* Title */}
      <div>
        <label className="block text-sm font-medium mb-1">Title *</label>
        <input
          type="text"
          value={formData.title}
          onChange={(e) => setFormData({ ...formData, title: e.target.value })}
          className="w-full px-3 py-2 border rounded"
          required
        />
      </div>

      {/* Description */}
      <div>
        <label className="block text-sm font-medium mb-1">Description</label>
        <textarea
          value={formData.description}
          onChange={(e) => setFormData({ ...formData, description: e.target.value })}
          className="w-full px-3 py-2 border rounded"
          rows={4}
        />
      </div>

      {/* Deadline */}
      <div>
        <label className="block text-sm font-medium mb-1">Deadline</label>
        <input
          type="date"
          value={formData.deadline}
          onChange={(e) => setFormData({ ...formData, deadline: e.target.value })}
          className="w-full px-3 py-2 border rounded"
          min={new Date().toISOString().split('T')[0]} // Today or future
        />
      </div>

      {/* Status */}
      <div>
        <label className="block text-sm font-medium mb-1">Status</label>
        <select
          value={formData.status}
          onChange={(e) => setFormData({ ...formData, status: e.target.value as any })}
          className="w-full px-3 py-2 border rounded"
        >
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="overdue">Overdue</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      {/* Priority */}
      <div>
        <label className="block text-sm font-medium mb-1">Priority</label>
        <select
          value={formData.priority}
          onChange={(e) => setFormData({ ...formData, priority: e.target.value as any })}
          className="w-full px-3 py-2 border rounded"
        >
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
        </select>
      </div>

      {/* Actions */}
      <div className="flex gap-2">
        <button
          type="submit"
          disabled={updating}
          className="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
        >
          {updating ? 'Updating...' : 'Update Task'}
        </button>
        
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="px-4 py-2 bg-gray-300 rounded"
          >
            Cancel
          </button>
        )}
      </div>
    </form>
  );
}
```

---

### **5. Create Update Submission Form (for Student): `/components/tasks/UpdateSubmissionForm.tsx`**

```typescript
'use client';

import { useState } from 'react';
import { useUpdateSubmission } from '@/hooks/useUpdateSubmission';
import { UpdateSubmissionRequest, TaskResource } from '@/types/task';

interface UpdateSubmissionFormProps {
  taskId: number;
  currentSubmission?: {
    submission_content?: string;
    submission_files: number[];
    submission_notes?: string;
  };
  onSuccess?: () => void;
}

export function UpdateSubmissionForm({
  taskId,
  currentSubmission,
  onSuccess,
}: UpdateSubmissionFormProps) {
  const { updateSubmission, updating, error, success } = useUpdateSubmission();

  const [formData, setFormData] = useState<UpdateSubmissionRequest>({
    submission_content: currentSubmission?.submission_content || '',
    submission_files: currentSubmission?.submission_files || [],
    submission_notes: currentSubmission?.submission_notes || '',
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    await updateSubmission(taskId, formData);

    if (success && onSuccess) {
      onSuccess();
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {error && (
        <div className="p-3 bg-red-50 border border-red-200 rounded text-red-800">
          {error}
        </div>
      )}

      {success && (
        <div className="p-3 bg-green-50 border border-green-200 rounded text-green-800">
          Submission updated successfully!
        </div>
      )}

      <div>
        <label className="block text-sm font-medium mb-1">Content</label>
        <textarea
          value={formData.submission_content}
          onChange={(e) =>
            setFormData({ ...formData, submission_content: e.target.value })
          }
          className="w-full px-3 py-2 border rounded"
          rows={6}
        />
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Notes</label>
        <textarea
          value={formData.submission_notes}
          onChange={(e) =>
            setFormData({ ...formData, submission_notes: e.target.value })
          }
          className="w-full px-3 py-2 border rounded"
          rows={3}
        />
      </div>

      <button
        type="submit"
        disabled={updating}
        className="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
      >
        {updating ? 'Updating...' : 'Update Submission'}
      </button>
    </form>
  );
}
```

---

## ❌ **Error Handling**

### **Handle Permission Errors:**

```typescript
try {
  await lecturerUpdateTask(taskId, data, token);
} catch (error: any) {
  if (error.message.includes('Access denied')) {
    // Show user-friendly message
    toast.error('You can only update tasks you created or tasks assigned to you.');
  } else if (error.message.includes('Task not found')) {
    toast.error('Task not found');
  } else {
    toast.error('Failed to update task. Please try again.');
  }
}
```

### **Handle Validation Errors:**

```typescript
try {
  await updateTask(taskId, data, token, userRole);
} catch (error: any) {
  if (error.response?.status === 422) {
    // Validation errors
    const errors = error.response.data.errors;
    Object.keys(errors).forEach((field) => {
      toast.error(`${field}: ${errors[field][0]}`);
    });
  }
}
```

---

## 🧪 **Testing**

### **Test Admin Update:**

```typescript
// Test admin can update any task
const adminToken = 'admin_token';
const anyTaskId = 1;

await adminUpdateTask(anyTaskId, {
  title: 'Updated by Admin',
  status: 'completed',
}, adminToken);
// ✅ Should succeed
```

### **Test Lecturer Update (Creator):**

```typescript
// Test lecturer can update task they created
const lecturerToken = 'lecturer_token';
const myTaskId = 10; // Task created by this lecturer

await lecturerUpdateTask(myTaskId, {
  title: 'Updated by Creator',
}, lecturerToken);
// ✅ Should succeed
```

### **Test Lecturer Update (Receiver):**

```typescript
// Test lecturer can update task they're assigned to
const lecturerToken = 'lecturer_token';
const assignedTaskId = 20; // Task where lecturer is receiver

await lecturerUpdateTask(assignedTaskId, {
  title: 'Updated by Receiver',
}, lecturerToken);
// ✅ Should succeed
```

### **Test Lecturer Update (Forbidden):**

```typescript
// Test lecturer CANNOT update task of another lecturer
const lecturerToken = 'lecturer_token';
const otherTaskId = 30; // Task created by another lecturer

try {
  await lecturerUpdateTask(otherTaskId, {
    title: 'Should Fail',
  }, lecturerToken);
} catch (error) {
  // ✅ Should throw "Access denied"
  expect(error.message).toContain('Access denied');
}
```

---

## ✅ **Best Practices**

### **1. Always Check Permission Before Showing Update Button:**

```typescript
const canUpdate = 
  userRole === 'admin' ||
  (userRole === 'lecturer' && (
    task.creator_id === currentUserId ||
    task.receivers?.some(r => r.receiver_id === currentUserId)
  ));

{canUpdate && (
  <button onClick={() => setEditing(true)}>
    Edit Task
  </button>
)}
```

### **2. Use PATCH for Partial Updates:**

```typescript
// ✅ Good - Only send changed fields
await updateTask(taskId, {
  title: 'New Title',
}, token, userRole);

// ❌ Avoid - Sending all fields every time
await updateTask(taskId, {
  title: task.title,
  description: task.description,
  deadline: task.deadline,
  // ... all fields
}, token, userRole);
```

### **3. Handle Optimistic Updates:**

```typescript
const [localTask, setLocalTask] = useState(task);

const handleUpdate = async (data: UpdateTaskRequest) => {
  // Optimistic update
  setLocalTask({ ...localTask, ...data });
  
  try {
    await updateTask(taskId, data, token, userRole);
  } catch (error) {
    // Revert on error
    setLocalTask(task);
    toast.error('Failed to update task');
  }
};
```

### **4. Show Loading States:**

```typescript
{updating && (
  <div className="flex items-center gap-2">
    <Spinner />
    <span>Updating task...</span>
  </div>
)}
```

---

## 📚 **Complete Example Page**

### **`/app/tasks/[id]/edit/page.tsx`:**

```typescript
'use client';

import { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';
import { useAuth } from '@/hooks/useAuth';
import { useUpdateTask } from '@/hooks/useUpdateTask';
import { TaskResource, UpdateTaskRequest } from '@/types/task';

export default function EditTaskPage() {
  const params = useParams();
  const taskId = parseInt(params.id as string);
  const { token, userRole } = useAuth();
  const { updateTask, updating, error, success } = useUpdateTask();
  
  const [task, setTask] = useState<TaskResource | null>(null);
  const [formData, setFormData] = useState<UpdateTaskRequest>({});

  // Fetch task
  useEffect(() => {
    fetch(`/api/v1/tasks/${taskId}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then(res => res.json())
      .then(data => {
        setTask(data.data);
        setFormData({
          title: data.data.title,
          description: data.data.description,
          status: data.data.status,
        });
      });
  }, [taskId, token]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await updateTask(taskId, formData);
    if (success) {
      router.push(`/tasks/${taskId}`);
    }
  };

  if (!task) return <div>Loading...</div>;

  return (
    <div className="container mx-auto p-4">
      <h1 className="text-2xl font-bold mb-4">Edit Task</h1>
      
      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Form fields */}
        <input
          value={formData.title}
          onChange={(e) => setFormData({ ...formData, title: e.target.value })}
        />
        
        <button type="submit" disabled={updating}>
          {updating ? 'Updating...' : 'Update Task'}
        </button>
      </form>
      
      {error && <div className="text-red-600">{error}</div>}
    </div>
  );
}
```

---

## 🎯 **Summary**

### **✅ Do's:**

1. ✅ Check user role before showing update UI
2. ✅ Use PATCH for partial updates
3. ✅ Handle permission errors gracefully
4. ✅ Show loading states
5. ✅ Validate data client-side before sending
6. ✅ Use TypeScript types for type safety

### **❌ Don'ts:**

1. ❌ Don't allow student to update task metadata
2. ❌ Don't show update button without permission check
3. ❌ Don't send all fields if only one changed
4. ❌ Don't ignore error responses
5. ❌ Don't forget to refresh data after update

---

## 🔗 **Related Documentation**

- [Frontend File Download Integration](./FRONTEND_FILE_DOWNLOAD_INTEGRATION.md)
- [Update Task Fix Complete](./UPDATE_TASK_FIX_COMPLETE.md)
- [Student File Upload Guide](./STUDENT_FILE_UPLOAD_GUIDE.md)

---

**🎉 Ready to implement! Follow this guide for proper permission handling and API usage.**

