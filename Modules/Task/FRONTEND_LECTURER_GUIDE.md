# 🎨 Frontend Integration Guide - Lecturer Task Management

## 📋 Tổng quan

Tài liệu này hướng dẫn chi tiết cách Frontend tích hợp chức năng **Task Management** cho Lecturer (Giảng viên) với Backend Laravel 12. Tương tự như Student nhưng với các chức năng bổ sung như tạo task, giao task, chấm điểm.

---

## 🚀 API Endpoints cho Lecturer

### **Base URL:** `http://localhost:8082/api/v1/lecturer-tasks`

| Endpoint | Method | Mô tả |
|----------|--------|-------|
| `/api/v1/lecturer-tasks` | GET | Lấy danh sách tasks của giảng viên |
| `/api/v1/lecturer-tasks` | POST | Tạo task mới |
| `/api/v1/lecturer-tasks/{taskId}` | GET | Xem chi tiết task |
| `/api/v1/lecturer-tasks/{taskId}` | PUT | Cập nhật task |
| `/api/v1/lecturer-tasks/{taskId}` | DELETE | Xóa task |
| `/api/v1/lecturer-tasks/{taskId}/upload-file` | POST | Upload single file cho task |
| `/api/v1/lecturer-tasks/{taskId}/files` | POST | Upload multiple files cho task |
| `/api/v1/lecturer-tasks/{taskId}/files/{fileId}` | DELETE | Xóa file |
| `/api/v1/lecturer-tasks/{taskId}/files/{fileId}/download` | GET | Download file |
| `/api/v1/lecturer-tasks/{taskId}/assign` | PATCH | Giao task cho sinh viên |
| `/api/v1/lecturer-tasks/{taskId}/revoke` | POST | Thu hồi task |
| `/api/v1/lecturer-tasks/{taskId}/submissions` | GET | Lấy danh sách submissions của task |
| `/api/v1/lecturer-tasks/{taskId}/submissions/{submissionId}/grade` | POST | Chấm điểm bài nộp |
| `/api/v1/lecturer-tasks/created` | GET | Tasks đã tạo |
| `/api/v1/lecturer-tasks/assigned` | GET | Tasks được giao |
| `/api/v1/lecturer-tasks/statistics` | GET | Thống kê giảng viên |

---

## 📤 1. Upload File (Single)

### **Request:**
```http
POST /api/v1/lecturer-tasks/{taskId}/upload-file
Authorization: Bearer <JWT_TOKEN>
Content-Type: multipart/form-data
```

### **Body (FormData):**
```javascript
FormData {
  "file": File  // Single file upload
}
```

### **Response (200 OK):**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 7,                              // ← File ID - QUAN TRỌNG!
    "task_id": 119,
    "lecturer_id": 1,
    "filename": "assignment.pdf",         // Tên file gốc
    "path": "task-files/119/xxx.pdf",    // Path trong storage
    "size": 12345,                        // Kích thước file (bytes)
    "file_url": "http://localhost:8082/storage/task-files/119/xxx.pdf",
    "uploaded_at": "2025-11-01 03:17:17"
  }
}
```

### **TypeScript Interface:**
```typescript
interface UploadFileResponse {
  success: boolean;
  message: string;
  data: {
    id: number;              // ← File ID - Lưu lại để dùng
    task_id: number;
    lecturer_id: number;
    filename: string;
    path: string;
    size: number;
    file_url: string;
    uploaded_at: string;
  };
}
```

### **JavaScript/TypeScript Example:**
```typescript
/**
 * Upload single file cho task (Lecturer)
 * @param taskId - Task ID
 * @param file - File object từ input
 * @param token - JWT token
 * @returns File ID nếu thành công, null nếu thất bại
 */
async function uploadTaskFile(
  taskId: number,
  file: File,
  token: string
): Promise<number | null> {
  try {
    const formData = new FormData();
    formData.append('file', file);  // Key: 'file' (singular)

    const response = await fetch(
      `http://localhost:8082/api/v1/lecturer-tasks/${taskId}/upload-file`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
          // KHÔNG set Content-Type, browser sẽ tự động set với boundary
        },
        body: formData
      }
    );

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Upload failed');
    }

    const result: UploadFileResponse = await response.json();
    
    if (result.success && result.data?.id) {
      return result.data.id;  // ← Trả về File ID
    }
    
    return null;
  } catch (error) {
    console.error('Upload file error:', error);
    return null;
  }
}
```

---

## 📤 2. Upload Files (Multiple)

### **Request:**
```http
POST /api/v1/lecturer-tasks/{taskId}/files
Authorization: Bearer <JWT_TOKEN>
Content-Type: multipart/form-data
```

### **Body (FormData):**
```javascript
FormData {
  "files[]": File[]  // Multiple files
}
```

### **Response (200 OK):**
```json
{
  "success": true,
  "message": "File(s) uploaded successfully",
  "data": [
    {
      "id": 7,
      "file_name": "assignment.pdf",
      "file_url": "http://localhost:8082/storage/...",
      "created_at": "2025-11-01 03:17:17"
    },
    {
      "id": 8,
      "file_name": "instructions.docx",
      "file_url": "http://localhost:8082/storage/...",
      "created_at": "2025-11-01 03:17:18"
    }
  ],
  "count": 2
}
```

### **Example:**
```typescript
async function uploadMultipleFiles(
  taskId: number,
  files: File[],
  token: string
): Promise<number[]> {
  try {
    const formData = new FormData();
    files.forEach(file => {
      formData.append('files[]', file);
    });

    const response = await fetch(
      `http://localhost:8082/api/v1/lecturer-tasks/${taskId}/files`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      }
    );

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Upload failed');
    }

    const result = await response.json();
    
    if (result.success && Array.isArray(result.data)) {
      return result.data.map((file: any) => file.id);
    }
    
    return [];
  } catch (error) {
    console.error('Upload files error:', error);
    return [];
  }
}
```

---

## 📝 3. Create Task

### **Request:**
```http
POST /api/v1/lecturer-tasks
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

### **Body (JSON):**
```json
{
  "title": "Bài tập tuần 1",
  "description": "Mô tả chi tiết bài tập",
  "deadline": "2025-12-01 23:59:59",
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
  ],
  "files": [7, 8]  // File IDs đã upload trước
}
```

### **Response (201 Created):**
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 120,
    "title": "Bài tập tuần 1",
    "description": "Mô tả chi tiết bài tập",
    "deadline": "2025-12-01 23:59:59",
    "priority": "high",
    "status": "pending",
    "creator_id": 1,
    "creator_type": "lecturer",
    "created_at": "2025-11-01T03:17:17.000000Z",
    "files": [
      {
        "id": 7,
        "file_name": "assignment.pdf",
        "file_url": "http://..."
      }
    ]
  }
}
```

### **TypeScript Interface:**
```typescript
interface CreateTaskRequest {
  title: string;
  description: string;
  deadline: string;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status?: 'pending' | 'in_progress' | 'completed';
  class_id?: number;
  receivers?: Array<{
    receiver_id: number;
    receiver_type: 'student' | 'class';
  }>;
  files?: number[];  // File IDs đã upload
}

interface CreateTaskResponse {
  success: boolean;
  message: string;
  data: {
    id: number;
    title: string;
    description: string;
    deadline: string;
    priority: string;
    status: string;
    creator_id: number;
    creator_type: string;
    created_at: string;
    files?: Array<{
      id: number;
      file_name: string;
      file_url: string;
    }>;
  };
}
```

### **Example:**
```typescript
async function createTask(
  taskData: CreateTaskRequest,
  token: string
): Promise<CreateTaskResponse | null> {
  try {
    const response = await fetch(
      'http://localhost:8082/api/v1/lecturer-tasks',
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(taskData)
      }
    );

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Create task failed');
    }

    const result: CreateTaskResponse = await response.json();
    return result;
  } catch (error) {
    console.error('Create task error:', error);
    return null;
  }
}
```

---

## 📋 4. Get Tasks

### **Request:**
```http
GET /api/v1/lecturer-tasks?page=1&limit=20&status=pending
Authorization: Bearer <JWT_TOKEN>
```

### **Response (200 OK):**
```json
{
  "success": true,
  "message": "Lecturer tasks retrieved successfully",
  "data": [
    {
      "id": 119,
      "title": "Bài tập tuần 1",
      "description": "...",
      "deadline": "2025-12-01 23:59:59",
      "priority": "high",
      "status": "pending",
      "creator_id": 1,
      "creator_type": "lecturer",
      "files": [...],
      "receivers": [...]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  }
}
```

---

## 🔧 5. Update Task

### **Request:**
```http
PUT /api/v1/lecturer-tasks/{taskId}
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

### **Body (JSON):**
```json
{
  "title": "Bài tập tuần 1 (Updated)",
  "description": "Mô tả đã cập nhật",
  "deadline": "2025-12-05 23:59:59",
  "priority": "urgent",
  "files": [7, 8, 9]  // File IDs mới
}
```

---

## 🗑️ 6. Delete File

### **Request:**
```http
DELETE /api/v1/lecturer-tasks/{taskId}/files/{fileId}
Authorization: Bearer <JWT_TOKEN>
```

### **Response (200 OK):**
```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

---

## 📥 7. Download File

### **Request:**
```http
GET /api/v1/lecturer-tasks/{taskId}/files/{fileId}/download
Authorization: Bearer <JWT_TOKEN>
```

### **Response:**
File download với tên gốc (Content-Disposition header)

### **Example:**
```typescript
async function downloadFile(
  taskId: number,
  fileId: number,
  fileName: string,
  token: string
): Promise<void> {
  try {
    const response = await fetch(
      `http://localhost:8082/api/v1/lecturer-tasks/${taskId}/files/${fileId}/download`,
      {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    if (!response.ok) {
      throw new Error('Download failed');
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(link);
  } catch (error) {
    console.error('Download error:', error);
    alert('Failed to download file');
  }
}
```

---

## 🎯 8. Assign Task

### **Request:**
```http
PATCH /api/v1/lecturer-tasks/{taskId}/assign
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

### **Body (JSON):**
```json
{
  "receiver_ids": [1, 2, 3],  // Student IDs
  "receiver_type": "student"
}
```

---

## 🔄 Complete Flow Example (React)

### **Component: CreateTaskForm**

```typescript
import React, { useState } from 'react';

interface CreateTaskFormProps {
  token: string;
  onSuccess?: (taskId: number) => void;
}

export const CreateTaskForm: React.FC<CreateTaskFormProps> = ({
  token,
  onSuccess
}) => {
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    deadline: '',
    priority: 'medium' as const,
    class_id: undefined as number | undefined,
    receivers: [] as Array<{ receiver_id: number; receiver_type: 'student' }>
  });
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const [uploadedFileIds, setUploadedFileIds] = useState<number[]>([]);
  const [uploading, setUploading] = useState(false);
  const [creating, setCreating] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    if (event.target.files) {
      setSelectedFiles(Array.from(event.target.files));
    }
  };

  const handleUploadFiles = async () => {
    if (selectedFiles.length === 0) return;

    setUploading(true);
    setError(null);

    // Upload files và lấy IDs
    const newFileIds: number[] = [];
    for (const file of selectedFiles) {
      const fileId = await uploadTaskFile(formData.class_id || 0, file, token);
      if (fileId) {
        newFileIds.push(fileId);
      }
    }

    if (newFileIds.length > 0) {
      setUploadedFileIds(prev => [...prev, ...newFileIds]);
      setSelectedFiles([]);
    }

    setUploading(false);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.title.trim() || !formData.description.trim()) {
      setError('Tiêu đề và mô tả là bắt buộc');
      return;
    }

    setCreating(true);
    setError(null);

    try {
      const result = await createTask(
        {
          ...formData,
          files: uploadedFileIds
        },
        token
      );

      if (result && result.success) {
        if (onSuccess && result.data?.id) {
          onSuccess(result.data.id);
        }
        alert('Tạo task thành công!');
        // Reset form
        setFormData({
          title: '',
          description: '',
          deadline: '',
          priority: 'medium',
          class_id: undefined,
          receivers: []
        });
        setUploadedFileIds([]);
      } else {
        setError('Tạo task thất bại');
      }
    } catch (err: any) {
      setError(err.message || 'Tạo task thất bại');
    } finally {
      setCreating(false);
    }
  };

  return (
    <div className="create-task-form">
      <h2>Tạo Task Mới</h2>

      {error && (
        <div className="error-message" style={{ color: 'red' }}>
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        {/* Title */}
        <div className="form-group">
          <label>
            Tiêu đề <span style={{ color: 'red' }}>*</span>
          </label>
          <input
            type="text"
            value={formData.title}
            onChange={(e) => setFormData(prev => ({ ...prev, title: e.target.value }))}
            required
            placeholder="Nhập tiêu đề task..."
          />
        </div>

        {/* Description */}
        <div className="form-group">
          <label>
            Mô tả <span style={{ color: 'red' }}>*</span>
          </label>
          <textarea
            value={formData.description}
            onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
            rows={5}
            required
            placeholder="Nhập mô tả task..."
          />
        </div>

        {/* Deadline */}
        <div className="form-group">
          <label>
            Deadline <span style={{ color: 'red' }}>*</span>
          </label>
          <input
            type="datetime-local"
            value={formData.deadline}
            onChange={(e) => setFormData(prev => ({ ...prev, deadline: e.target.value }))}
            required
          />
        </div>

        {/* Priority */}
        <div className="form-group">
          <label>Độ ưu tiên</label>
          <select
            value={formData.priority}
            onChange={(e) => setFormData(prev => ({ ...prev, priority: e.target.value as any }))}
          >
            <option value="low">Thấp</option>
            <option value="medium">Trung bình</option>
            <option value="high">Cao</option>
            <option value="urgent">Khẩn cấp</option>
          </select>
        </div>

        {/* File Upload */}
        <div className="form-group">
          <label>Đính kèm files</label>
          
          <input
            type="file"
            multiple
            onChange={handleFileSelect}
            disabled={uploading}
          />

          {selectedFiles.length > 0 && (
            <button
              type="button"
              onClick={handleUploadFiles}
              disabled={uploading}
            >
              {uploading ? 'Đang upload...' : `Upload ${selectedFiles.length} file(s)`}
            </button>
          )}

          {uploadedFileIds.length > 0 && (
            <div className="uploaded-files">
              <p>Đã upload {uploadedFileIds.length} file(s)</p>
            </div>
          )}
        </div>

        {/* Submit Button */}
        <button
          type="submit"
          disabled={creating || !formData.title.trim() || !formData.description.trim()}
        >
          {creating ? 'Đang tạo...' : 'Tạo Task'}
        </button>
      </form>
    </div>
  );
};
```

---

## 📋 9. Get Task Submissions

### **Request:**
```http
GET /api/v1/lecturer-tasks/{taskId}/submissions
Authorization: Bearer <JWT_TOKEN>
```

### **Response (200 OK):**
```json
{
  "success": true,
  "message": "Task submissions retrieved successfully",
  "data": [
    {
      "id": 7,
      "task_id": 119,
      "student_id": 1,
      "student_name": "Nguyễn Văn A",
      "submission_content": "Nội dung bài nộp",
      "submitted_at": "2025-11-01 03:17:17",
      "status": "pending",
      "grade": null,
      "feedback": null,
      "graded_at": null,
      "graded_by": null,
      "files": [
        {
          "id": 7,
          "file_name": "test.pdf",
          "file_url": "http://localhost:8082/storage/...",
          "file_size": 12345,
          "created_at": "2025-11-01 03:17:17"
        }
      ],
      "created_at": "2025-11-01 03:17:17",
      "updated_at": "2025-11-01 03:17:17"
    }
  ]
}
```

### **Example:**
```typescript
async function getTaskSubmissions(
  taskId: number,
  token: string
): Promise<any[]> {
  try {
    const response = await fetch(
      `http://localhost:8082/api/v1/lecturer-tasks/${taskId}/submissions`,
      {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Get submissions failed');
    }

    const result = await response.json();
    return result.data || [];
  } catch (error) {
    console.error('Get submissions error:', error);
    return [];
  }
}
```

---

## ✅ 10. Grade Submission (Chấm điểm)

### **Request:**
```http
POST /api/v1/lecturer-tasks/{taskId}/submissions/{submissionId}/grade
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

### **Body (JSON):**
```json
{
  "status": "graded",        // "graded" (đạt) hoặc "returned" (chưa đạt)
  "grade": 8.5,              // Điểm số (0-10), bắt buộc nếu status = "graded"
  "feedback": "Bài làm tốt, cần cải thiện phần trình bày"  // Optional
}
```

### **Response (200 OK):**
```json
{
  "success": true,
  "message": "Submission graded successfully",
  "data": {
    "id": 7,
    "task_id": 119,
    "student_id": 1,
    "submission_content": "Nội dung bài nộp",
    "submitted_at": "2025-11-01 03:17:17",
    "status": "graded",
    "grade": 8.5,
    "feedback": "Bài làm tốt",
    "graded_at": "2025-11-01 10:30:00",
    "graded_by": 1,
    "files": [...],
    "updated_at": "2025-11-01 10:30:00"
  }
}
```

### **TypeScript Interface:**
```typescript
interface GradeSubmissionRequest {
  status: 'graded' | 'returned';  // Bắt buộc
  grade?: number;                  // Bắt buộc nếu status = "graded" (0-10)
  feedback?: string;               // Optional
}

interface GradeSubmissionResponse {
  success: boolean;
  message: string;
  data: {
    id: number;
    task_id: number;
    student_id: number;
    submission_content: string;
    submitted_at: string;
    status: 'graded' | 'returned' | 'pending';
    grade: number | null;
    feedback: string | null;
    graded_at: string | null;
    graded_by: number | null;
    files: Array<{
      id: number;
      file_name: string;
      file_url: string;
      file_size: number;
      created_at: string;
    }>;
    updated_at: string;
  };
}
```

### **Example:**
```typescript
async function gradeSubmission(
  taskId: number,
  submissionId: number,
  data: GradeSubmissionRequest,
  token: string
): Promise<GradeSubmissionResponse | null> {
  try {
    // Validate
    if (data.status === 'graded' && (data.grade === undefined || data.grade === null)) {
      throw new Error('Grade is required when status is "graded"');
    }

    if (data.grade !== undefined && (data.grade < 0 || data.grade > 10)) {
      throw new Error('Grade must be between 0 and 10');
    }

    const response = await fetch(
      `http://localhost:8082/api/v1/lecturer-tasks/${taskId}/submissions/${submissionId}/grade`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      }
    );

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Grade submission failed');
    }

    const result: GradeSubmissionResponse = await response.json();
    return result;
  } catch (error) {
    console.error('Grade submission error:', error);
    return null;
  }
}
```

### **React Component Example:**
```typescript
const GradeSubmissionForm: React.FC<{
  taskId: number;
  submissionId: number;
  token: string;
  onSuccess?: () => void;
}> = ({ taskId, submissionId, token, onSuccess }) => {
  const [status, setStatus] = useState<'graded' | 'returned'>('graded');
  const [grade, setGrade] = useState<number>(0);
  const [feedback, setFeedback] = useState('');
  const [grading, setGrading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setGrading(true);
    setError(null);

    try {
      const result = await gradeSubmission(
        taskId,
        submissionId,
        {
          status,
          grade: status === 'graded' ? grade : undefined,
          feedback: feedback.trim() || undefined
        },
        token
      );

      if (result && result.success) {
        alert('Chấm điểm thành công!');
        if (onSuccess) onSuccess();
      } else {
        setError('Chấm điểm thất bại');
      }
    } catch (err: any) {
      setError(err.message || 'Chấm điểm thất bại');
    } finally {
      setGrading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <div className="form-group">
        <label>Trạng thái *</label>
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value as 'graded' | 'returned')}
        >
          <option value="graded">Đạt</option>
          <option value="returned">Chưa đạt</option>
        </select>
      </div>

      {status === 'graded' && (
        <div className="form-group">
          <label>Điểm số (0-10) *</label>
          <input
            type="number"
            min="0"
            max="10"
            step="0.1"
            value={grade}
            onChange={(e) => setGrade(parseFloat(e.target.value))}
            required
          />
        </div>
      )}

      <div className="form-group">
        <label>Nhận xét</label>
        <textarea
          value={feedback}
          onChange={(e) => setFeedback(e.target.value)}
          rows={5}
          placeholder="Nhập nhận xét cho sinh viên..."
        />
      </div>

      {error && <div className="error">{error}</div>}

      <button type="submit" disabled={grading}>
        {grading ? 'Đang chấm...' : 'Chấm điểm'}
      </button>
    </form>
  );
};
```

---

## ⚠️ Important Notes

### **1. Upload Flow cho Create Task:**
- ✅ **Bước 1:** Upload files trước → Nhận File IDs
- ✅ **Bước 2:** Tạo task với File IDs trong `files` array
- ❌ **KHÔNG** gửi File objects trực tiếp trong create task request

### **2. File ID là bắt buộc:**
- Sau khi upload file thành công, **PHẢI** lưu `data.id` từ response
- File ID này sẽ được dùng trong create/update task: `files: [fileId1, fileId2]`

### **3. Required Fields:**
- `title`: Bắt buộc
- `description`: Bắt buộc
- `deadline`: Bắt buộc
- `files`: Optional (array of file IDs)

### **4. Error Handling:**
- **401**: Chưa đăng nhập hoặc token hết hạn
- **403**: Không có quyền truy cập task này
- **404**: Task không tồn tại
- **500**: Lỗi server

### **5. Response Structure:**
- Tất cả responses đều có format: `{ success: boolean, message: string, data: ... }`
- Upload single file: `data` là object với `id`
- Upload multiple files: `data` là array of file objects

---

## 🧪 Testing Checklist

- [ ] Upload single file → Nhận File ID
- [ ] Upload multiple files → Nhận multiple File IDs
- [ ] Create task với files → Success
- [ ] Create task không có files → Success
- [ ] Update task → Success
- [ ] Delete file → Success
- [ ] Download file → Success
- [ ] Assign task → Success
- [ ] Get tasks list → Success
- [ ] Get task submissions → Success
- [ ] Grade submission (đạt) → Success
- [ ] Grade submission (chưa đạt) → Success
- [ ] Error handling cho các trường hợp lỗi

---

## 📚 Related Documentation

- [API Endpoints](./API_ENDPOINTS.md)
- [Task File Upload Guide](./TASK_FILE_UPLOAD_GUIDE.md)
- [Student Frontend Guide](./FRONTEND_UPDATE_GUIDE.md)

---

**Version**: 1.0.0  
**Last Updated**: 2025-11-01  
**Backend Version**: Laravel 12 + Task Module  
**Role**: Lecturer (Giảng viên)

