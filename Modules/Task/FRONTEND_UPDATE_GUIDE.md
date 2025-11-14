# 🎨 Frontend Integration Guide - Student Task Submission

## 📋 Tổng quan

Tài liệu này hướng dẫn chi tiết cách Frontend tích hợp chức năng **Task Submission** cho Student với Backend Laravel 12. Dựa trên kết quả test thực tế từ terminal output.

---

## 🚀 API Endpoints cho Student

### **Base URL:** `http://localhost:8082/api/v1/student-tasks`

| Endpoint | Method | Mô tả |
|----------|--------|-------|
| `/api/v1/student-tasks/{taskId}/upload-file` | POST | Upload file cho task |
| `/api/v1/student-tasks/{taskId}/submit` | POST | Submit task với content và files |
| `/api/v1/student-tasks/{taskId}/submission` | GET | Lấy submission đã submit |
| `/api/v1/student-tasks/{taskId}/submission` | PUT | Cập nhật submission |
| `/api/v1/student-tasks/{taskId}/files/{fileId}` | DELETE | Xóa file đã upload |
| `/api/v1/student-tasks/{taskId}/files` | GET | Lấy danh sách files |

---

## 📤 1. Upload File

### **Request:**
```http
POST /api/v1/student-tasks/{taskId}/upload-file
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
    "task_id": "119",
    "student_id": 1,
    "filename": "test.pdf",               // Tên file gốc
    "path": "task-files/119/xxx.pdf",     // Path trong storage
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
    id: number;              // ← File ID - Lưu lại để submit
    task_id: string | number;
    student_id: number;
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
 * Upload single file cho task
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
      `http://localhost:8082/api/v1/student-tasks/${taskId}/upload-file`,
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

### **React Hook Example:**
```typescript
import { useState } from 'react';

interface UseUploadFileReturn {
  uploadFile: (file: File) => Promise<number | null>;
  uploading: boolean;
  error: string | null;
}

export function useUploadFile(
  taskId: number,
  token: string
): UseUploadFileReturn {
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const uploadFile = async (file: File): Promise<number | null> => {
    setUploading(true);
    setError(null);

    try {
      const formData = new FormData();
      formData.append('file', file);

      const response = await fetch(
        `http://localhost:8082/api/v1/student-tasks/${taskId}/upload-file`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`
          },
          body: formData
        }
      );

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Upload failed');
      }

      const result: UploadFileResponse = await response.json();
      
      if (result.success && result.data?.id) {
        return result.data.id;
      }
      
      throw new Error('Invalid response format');
    } catch (err: any) {
      const errorMessage = err.message || 'Upload failed';
      setError(errorMessage);
      console.error('Upload error:', err);
      return null;
    } finally {
      setUploading(false);
    }
  };

  return { uploadFile, uploading, error };
}
```

---

## 📝 2. Submit Task

### **Request:**
```http
POST /api/v1/student-tasks/{taskId}/submit
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

### **Body (JSON):**
```json
{
  "content": "Đây là nội dung bài nộp của tôi",  // ← BẮT BUỘC
  "files": [7, 8, 9],                            // ← Array of File IDs (đã upload trước)
  "notes": "Ghi chú thêm nếu có"                 // ← Optional
}
```

**Hoặc format đầy đủ (cũng được hỗ trợ):**
```json
{
  "submission_content": "Đây là nội dung bài nộp",
  "submission_files": [7, 8, 9],
  "submission_notes": "Ghi chú"
}
```

### **Response (200 OK):**
```json
{
  "success": true,
  "message": "Task submitted successfully",
  "data": {
    "id": 7,
    "task_id": 119,
    "student_id": 1,
    "submission_content": "Đây là nội dung bài nộp",
    "submission_files": [7],                      // Array of file IDs
    "submitted_at": "2025-11-01T03:17:17.000000Z",
    "status": "pending",
    "grade": null,
    "feedback": null,
    "graded_at": null,
    "graded_by": null,
    "created_at": "2025-11-01T02:35:47.000000Z",
    "updated_at": "2025-11-01T03:17:17.000000Z",
    "deleted_at": null
  }
}
```

### **TypeScript Interface:**
```typescript
interface SubmitTaskRequest {
  content: string;              // Bắt buộc
  files?: number[];              // Array of file IDs (optional)
  notes?: string;                // Optional
}

interface SubmitTaskResponse {
  success: boolean;
  message: string;
  data: {
    id: number;
    task_id: number;
    student_id: number;
    submission_content: string;
    submission_files: number[];
    submitted_at: string;
    status: 'pending' | 'graded' | 'returned';
    grade: number | null;
    feedback: string | null;
    graded_at: string | null;
    graded_by: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
  };
}
```

### **JavaScript/TypeScript Example:**
```typescript
/**
 * Submit task với content và files
 * @param taskId - Task ID
 * @param content - Nội dung bài nộp (bắt buộc)
 * @param fileIds - Array of file IDs đã upload (optional)
 * @param notes - Ghi chú (optional)
 * @param token - JWT token
 */
async function submitTask(
  taskId: number,
  content: string,
  fileIds: number[] = [],
  notes?: string,
  token?: string
): Promise<SubmitTaskResponse | null> {
  try {
    const response = await fetch(
      `http://localhost:8082/api/v1/student-tasks/${taskId}/submit`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          content,      // hoặc submission_content
          files: fileIds,  // hoặc submission_files
          notes         // hoặc submission_notes
        })
      }
    );

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Submit failed');
    }

    const result: SubmitTaskResponse = await response.json();
    return result;
  } catch (error) {
    console.error('Submit task error:', error);
    return null;
  }
}
```

---

## 📥 3. Get Submission

### **Request:**
```http
GET /api/v1/student-tasks/{taskId}/submission
Authorization: Bearer <JWT_TOKEN>
```

### **Response (200 OK - Có submission):**
```json
{
  "success": true,
  "message": "Task submission retrieved successfully",
  "data": {
    "id": 7,
    "task_id": 119,
    "student_id": 1,
    "content": "Đây là nội dung bài nộp",
    "submission_content": "Đây là nội dung bài nộp",
    "submitted_at": "2025-11-01 03:17:17",
    "updated_at": "2025-11-01 03:17:17",
    "status": "pending",
    "files": [                                    // ← Array of file objects
      {
        "id": 7,
        "file_name": "test.pdf",
        "name": "test.pdf",
        "file_path": "task-files/119/xxx.pdf",
        "file_url": "http://localhost:8082/storage/task-files/119/xxx.pdf",
        "file_size": 0,
        "size": 0,
        "mime_type": null,
        "created_at": "2025-11-01 03:17:17"
      }
    ],
    "grade": null,
    "feedback": null
  }
}
```

### **Response (404 - Chưa có submission):**
```json
{
  "success": false,
  "message": "Chưa có bài nộp cho task này",
  "data": null
}
```

### **TypeScript Interface:**
```typescript
interface SubmissionFile {
  id: number;
  file_name: string;
  name: string;
  file_path: string;
  file_url: string;
  file_size: number;
  size: number;
  mime_type: string | null;
  created_at: string;
}

interface GetSubmissionResponse {
  success: boolean;
  message: string;
  data: {
    id: number;
    task_id: number;
    student_id: number;
    content: string;
    submission_content: string;
    submitted_at: string;
    updated_at: string;
    status: 'pending' | 'graded' | 'returned';
    files: SubmissionFile[];      // ← Luôn là array, không phải null
    grade: number | null;
    feedback: string | null;
  } | null;
}
```

### **JavaScript/TypeScript Example:**
```typescript
/**
 * Lấy submission của task
 * @param taskId - Task ID
 * @param token - JWT token
 * @returns Submission data hoặc null nếu chưa có
 */
async function getSubmission(
  taskId: number,
  token: string
): Promise<GetSubmissionResponse['data'] | null> {
  try {
    const response = await fetch(
      `http://localhost:8082/api/v1/student-tasks/${taskId}/submission`,
      {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    if (response.status === 404) {
      // Chưa có submission - không phải lỗi
      return null;
    }

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Get submission failed');
    }

    const result: GetSubmissionResponse = await response.json();
    
    if (result.success && result.data) {
      return result.data;
    }
    
    return null;
  } catch (error) {
    console.error('Get submission error:', error);
    return null;
  }
}
```

---

## 🔄 4. Complete Flow Example (React)

### **Component: TaskSubmissionForm**

```typescript
import React, { useState, useEffect } from 'react';
import { useUploadFile } from './hooks/useUploadFile';
import { submitTask, getSubmission } from './services/taskService';

interface TaskSubmissionFormProps {
  taskId: number;
  token: string;
  onSuccess?: () => void;
}

export const TaskSubmissionForm: React.FC<TaskSubmissionFormProps> = ({
  taskId,
  token,
  onSuccess
}) => {
  const [content, setContent] = useState('');
  const [notes, setNotes] = useState('');
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const [uploadedFileIds, setUploadedFileIds] = useState<number[]>([]);
  const [uploading, setUploading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [existingSubmission, setExistingSubmission] = useState<any>(null);

  const { uploadFile, uploading: fileUploading } = useUploadFile(taskId, token);

  // Load existing submission khi component mount
  useEffect(() => {
    loadExistingSubmission();
  }, [taskId, token]);

  const loadExistingSubmission = async () => {
    const submission = await getSubmission(taskId, token);
    if (submission) {
      setExistingSubmission(submission);
      setContent(submission.content || '');
      setNotes(submission.notes || '');
      // File IDs từ submission (nếu có)
      if (submission.files && submission.files.length > 0) {
        setUploadedFileIds(submission.files.map((f: any) => f.id));
      }
    }
  };

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    if (event.target.files) {
      setSelectedFiles(Array.from(event.target.files));
    }
  };

  const handleUploadFiles = async () => {
    if (selectedFiles.length === 0) return;

    setUploading(true);
    setError(null);

    const newFileIds: number[] = [];

    for (const file of selectedFiles) {
      const fileId = await uploadFile(file);
      if (fileId) {
        newFileIds.push(fileId);
      } else {
        setError('Failed to upload some files');
        break;
      }
    }

    if (newFileIds.length > 0) {
      setUploadedFileIds(prev => [...prev, ...newFileIds]);
      setSelectedFiles([]); // Clear selected files
    }

    setUploading(false);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!content.trim()) {
      setError('Nội dung bài nộp là bắt buộc');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const result = await submitTask(
        taskId,
        content,
        uploadedFileIds,
        notes,
        token
      );

      if (result && result.success) {
        // Success
        if (onSuccess) {
          onSuccess();
        }
        // Reload submission
        await loadExistingSubmission();
        alert('Nộp bài thành công!');
      } else {
        setError('Nộp bài thất bại');
      }
    } catch (err: any) {
      setError(err.message || 'Nộp bài thất bại');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="task-submission-form">
      <h2>Nộp bài</h2>

      {error && (
        <div className="error-message" style={{ color: 'red' }}>
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        {/* Content */}
        <div className="form-group">
          <label>
            Nội dung bài nộp <span style={{ color: 'red' }}>*</span>
          </label>
          <textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
            rows={10}
            required
            placeholder="Nhập nội dung bài nộp..."
          />
        </div>

        {/* File Upload */}
        <div className="form-group">
          <label>Đính kèm files</label>
          
          {/* File Input */}
          <input
            type="file"
            multiple
            onChange={handleFileSelect}
            disabled={uploading || fileUploading}
          />

          {/* Upload Button */}
          {selectedFiles.length > 0 && (
            <button
              type="button"
              onClick={handleUploadFiles}
              disabled={uploading || fileUploading}
            >
              {uploading || fileUploading ? 'Đang upload...' : `Upload ${selectedFiles.length} file(s)`}
            </button>
          )}

          {/* Uploaded Files List */}
          {uploadedFileIds.length > 0 && (
            <div className="uploaded-files">
              <h4>Files đã upload:</h4>
              <ul>
                {existingSubmission?.files?.map((file: any) => (
                  <li key={file.id}>
                    <a href={file.file_url} target="_blank" rel="noopener noreferrer">
                      {file.file_name}
                    </a>
                    <span> ({file.file_size} bytes)</span>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>

        {/* Notes */}
        <div className="form-group">
          <label>Ghi chú (tùy chọn)</label>
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            rows={3}
            placeholder="Ghi chú thêm nếu có..."
          />
        </div>

        {/* Submit Button */}
        <button
          type="submit"
          disabled={submitting || !content.trim()}
        >
          {submitting ? 'Đang nộp...' : 'Nộp bài'}
        </button>
      </form>

      {/* Existing Submission Info */}
      {existingSubmission && (
        <div className="submission-info">
          <h3>Bài nộp hiện tại:</h3>
          <p>Trạng thái: {existingSubmission.status}</p>
          <p>Nộp lúc: {new Date(existingSubmission.submitted_at).toLocaleString()}</p>
          {existingSubmission.grade !== null && (
            <>
              <p>Điểm: {existingSubmission.grade}</p>
              {existingSubmission.feedback && (
                <p>Nhận xét: {existingSubmission.feedback}</p>
              )}
            </>
          )}
        </div>
      )}
    </div>
  );
};
```

---

## 🗑️ 5. Delete File

### **Request:**
```http
DELETE /api/v1/student-tasks/{taskId}/files/{fileId}
Authorization: Bearer <JWT_TOKEN>
```

### **Response (200 OK):**
```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

### **Example:**
```typescript
async function deleteTaskFile(
  taskId: number,
  fileId: number,
  token: string
): Promise<boolean> {
  try {
    const response = await fetch(
      `http://localhost:8082/api/v1/student-tasks/${taskId}/files/${fileId}`,
      {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Delete failed');
    }

    const result = await response.json();
    return result.success === true;
  } catch (error) {
    console.error('Delete file error:', error);
    return false;
  }
}
```

---

## ⚠️ Important Notes

### **1. Upload Flow:**
- ✅ **Bước 1:** Upload files trước → Nhận File IDs
- ✅ **Bước 2:** Submit task với File IDs trong `files` array
- ❌ **KHÔNG** gửi File objects trực tiếp trong submit request

### **2. File ID là bắt buộc:**
- Sau khi upload file thành công, **PHẢI** lưu `data.id` từ response
- File ID này sẽ được dùng trong submit request: `files: [fileId1, fileId2, ...]`

### **3. Content là bắt buộc:**
- Field `content` (hoặc `submission_content`) là **BẮT BUỘC**
- Nếu thiếu sẽ nhận lỗi: `"Validation failed: Submission content is required"`

### **4. Error Handling:**
- **404** khi GET submission: Chưa có submission (không phải lỗi)
- **401**: Chưa đăng nhập hoặc token hết hạn
- **403**: Không có quyền truy cập task này
- **500**: Lỗi server

### **5. Response Structure:**
- Tất cả responses đều có format: `{ success: boolean, message: string, data: ... }`
- `files` trong submission response **luôn là array**, không bao giờ null

---

## 🧪 Testing Checklist

- [ ] Upload single file → Nhận File ID
- [ ] Upload multiple files → Nhận multiple File IDs
- [ ] Submit với content và files → Success
- [ ] Submit chỉ với content (không có files) → Success
- [ ] Submit không có content → Error
- [ ] Get submission khi chưa submit → 404
- [ ] Get submission sau khi submit → 200 với files array
- [ ] Update submission → Success
- [ ] Delete file → Success
- [ ] Error handling cho các trường hợp lỗi

---

## 📚 Related Documentation

- [API Endpoints](./API_ENDPOINTS.md)
- [Task File Upload Guide](./TASK_FILE_UPLOAD_GUIDE.md)
- [Test Submission Flow](./TEST_SUBMISSION_FLOW.md)

---

**Version**: 2.0.0  
**Last Updated**: 2025-11-01  
**Backend Version**: Laravel 12 + Task Module  
**Tested**: ✅ Verified với terminal output

