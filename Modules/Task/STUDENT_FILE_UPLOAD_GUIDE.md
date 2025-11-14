# 📤 Student File Upload & Download Guide

## 🎯 Mục Đích

Hướng dẫn chi tiết cho **Frontend** về cách upload file và submit bài nộp task cho Student.

---

## 📋 Flow Hoàn Chỉnh

```
1. Upload File(s)
   ↓
2. Lưu File IDs
   ↓
3. Submit Task với File IDs
   ↓
4. Download File khi cần
```

---

## 1️⃣ **UPLOAD FILE**

### **Endpoint:**
```
POST /api/v1/lecturer-tasks/{task_id}/upload-file
```

### **Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### **Body (Form Data):**
```
file: <binary_file>
```

### **Response (Success):**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 123,                    // ← LƯU FILE ID NÀY!
    "file_name": "assignment.pdf",
    "file_url": "http://localhost:8082/storage/task-files/130/abc.pdf",
    "file_size": 1024567,
    "created_at": "2024-11-03 12:21:00"
  }
}
```

### **JavaScript Example:**
```javascript
const uploadFile = async (taskId, file) => {
  const formData = new FormData();
  formData.append('file', file);

  const response = await fetch(
    `http://localhost:8082/api/v1/lecturer-tasks/${taskId}/upload-file`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      },
      body: formData
    }
  );

  const result = await response.json();
  
  if (result.success) {
    // ✅ LƯU FILE ID
    return result.data.id;
  }
  
  throw new Error(result.message);
};
```

### **React Example:**
```typescript
const [uploadedFileIds, setUploadedFileIds] = useState<number[]>([]);

const handleFileUpload = async (file: File) => {
  const formData = new FormData();
  formData.append('file', file);

  try {
    const response = await api.post(
      `/lecturer-tasks/${taskId}/upload-file`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    );

    if (response.data.success) {
      const fileId = response.data.data.id;
      
      // ✅ LƯU FILE ID VÀO STATE
      setUploadedFileIds(prev => [...prev, fileId]);
      
      toast.success('File uploaded successfully');
    }
  } catch (error) {
    toast.error('Failed to upload file');
  }
};
```

---

## 2️⃣ **SUBMIT TASK VỚI FILE IDS**

### **Endpoint:**
```
POST /api/v1/lecturer-tasks/{task_id}/submit
```

### **Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

### **Body (JSON):**
```json
{
  "content": "Đây là bài nộp của em",
  "files": [123, 456, 789],       // ← ARRAY OF FILE IDs
  "notes": "Em đã hoàn thành đầy đủ"
}
```

**⚠️ QUAN TRỌNG:**
- `files` PHẢI là **array of integers** (file IDs)
- Không được là `[]` (empty array) nếu có files
- Không được là `null` hoặc `undefined`

### **JavaScript Example:**
```javascript
const submitTask = async (taskId, content, fileIds, notes) => {
  const response = await fetch(
    `http://localhost:8082/api/v1/lecturer-tasks/${taskId}/submit`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        content: content,
        files: fileIds,           // ← Array of IDs [123, 456]
        notes: notes || null
      })
    }
  );

  const result = await response.json();
  return result;
};
```

### **React Example:**
```typescript
const handleSubmitTask = async () => {
  try {
    const payload = {
      content: submissionContent,
      files: uploadedFileIds,     // ← State đã lưu từ bước upload
      notes: submissionNotes
    };

    const response = await api.post(
      `/lecturer-tasks/${taskId}/submit`,
      payload
    );

    if (response.data.success) {
      toast.success('Task submitted successfully');
      navigate('/tasks');
    }
  } catch (error) {
    toast.error('Failed to submit task');
  }
};
```

---

## 3️⃣ **DOWNLOAD FILE**

### **Endpoint:**
```
GET /api/v1/lecturer-tasks/{task_id}/files/{file_id}/download
```

### **Headers:**
```
Authorization: Bearer {token}
```

### **Response:**
- Binary file stream với tên file gốc
- Content-Disposition: `attachment; filename="assignment.pdf"`

### **JavaScript Example:**
```javascript
const downloadFile = async (taskId, fileId, fileName) => {
  const response = await fetch(
    `http://localhost:8082/api/v1/lecturer-tasks/${taskId}/files/${fileId}/download`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      }
    }
  );

  if (response.ok) {
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  }
};
```

### **React Example:**
```typescript
const handleDownloadFile = async (file: TaskFile) => {
  try {
    const response = await api.get(
      `/lecturer-tasks/${taskId}/files/${file.id}/download`,
      {
        responseType: 'blob'
      }
    );

    const blob = new Blob([response.data]);
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', file.file_name);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    toast.success('File downloaded successfully');
  } catch (error) {
    toast.error('Failed to download file');
  }
};
```

---

## 4️⃣ **GET SUBMISSION (Xem Bài Nộp)**

### **Endpoint:**
```
GET /api/v1/lecturer-tasks/{task_id}/submission
```

### **Response (Success):**
```json
{
  "success": true,
  "message": "Task submission retrieved successfully",
  "data": {
    "id": 45,
    "task_id": 130,
    "student_id": 1,
    "submission_content": "Đây là bài nộp của em",
    "submission_notes": "Em đã hoàn thành",
    "submitted_at": "2024-11-03 12:30:00",
    "files": [
      {
        "id": 123,
        "file_name": "assignment.pdf",
        "file_url": "http://localhost:8082/storage/task-files/130/abc.pdf",
        "file_size": 1024567,
        "created_at": "2024-11-03 12:21:00"
      }
    ],
    "grade": null
  }
}
```

### **Response (No Submission Yet):**
```json
{
  "success": false,
  "message": "Chưa có bài nộp cho task này",
  "data": null
}
```
**Status Code:** `404 Not Found`

---

## 🔴 **COMMON MISTAKES TO AVOID**

### ❌ **Mistake 1: Gửi files rỗng**
```javascript
// BAD
{
  "content": "Bài nộp",
  "files": []           // ← Không có files!
}
```

### ✅ **Correct:**
```javascript
// GOOD
{
  "content": "Bài nộp",
  "files": [123, 456]   // ← Array of file IDs
}
```

---

### ❌ **Mistake 2: Gửi file objects thay vì IDs**
```javascript
// BAD
{
  "content": "Bài nộp",
  "files": [
    { id: 123, name: "file.pdf" }    // ← WRONG!
  ]
}
```

### ✅ **Correct:**
```javascript
// GOOD
{
  "content": "Bài nộp",
  "files": [123, 456]   // ← Array of integers only
}
```

---

### ❌ **Mistake 3: Submit trước khi upload xong**
```javascript
// BAD
const handleSubmit = async () => {
  uploadFile(file1);        // Async, chưa xong
  uploadFile(file2);        // Async, chưa xong
  submitTask(taskId, ...);  // ← Submit ngay! FILES CHƯA CÓ!
};
```

### ✅ **Correct:**
```javascript
// GOOD
const handleSubmit = async () => {
  const fileId1 = await uploadFile(file1);  // Wait xong
  const fileId2 = await uploadFile(file2);  // Wait xong
  
  await submitTask(taskId, content, [fileId1, fileId2], notes);
};
```

---

### ❌ **Mistake 4: Dùng file_url để download**
```javascript
// BAD - File sẽ mở trên browser, không download
window.open(file.file_url);
```

### ✅ **Correct:**
```javascript
// GOOD - Dùng download endpoint
const response = await fetch(
  `/lecturer-tasks/${taskId}/files/${fileId}/download`,
  { headers: { 'Authorization': `Bearer ${token}` } }
);
const blob = await response.blob();
// ... trigger download
```

---

## 📊 **COMPLETE REACT COMPONENT EXAMPLE**

```typescript
import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import api from '@/services/api';

interface UploadedFile {
  id: number;
  file_name: string;
  file_size: number;
}

const TaskSubmissionForm: React.FC = () => {
  const { taskId } = useParams<{ taskId: string }>();
  const navigate = useNavigate();

  const [content, setContent] = useState('');
  const [notes, setNotes] = useState('');
  const [uploadedFiles, setUploadedFiles] = useState<UploadedFile[]>([]);
  const [isUploading, setIsUploading] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Upload single file
  const handleFileUpload = async (file: File) => {
    const formData = new FormData();
    formData.append('file', file);

    setIsUploading(true);
    try {
      const response = await api.post(
        `/lecturer-tasks/${taskId}/upload-file`,
        formData,
        {
          headers: { 'Content-Type': 'multipart/form-data' }
        }
      );

      if (response.data.success) {
        const uploadedFile = response.data.data;
        setUploadedFiles(prev => [...prev, uploadedFile]);
        toast.success(`File "${uploadedFile.file_name}" uploaded`);
      }
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Upload failed');
    } finally {
      setIsUploading(false);
    }
  };

  // Handle file input change
  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (!files) return;

    for (let i = 0; i < files.length; i++) {
      await handleFileUpload(files[i]);
    }

    // Reset input
    e.target.value = '';
  };

  // Submit task
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!content.trim()) {
      toast.error('Please enter submission content');
      return;
    }

    if (uploadedFiles.length === 0) {
      toast.warning('No files uploaded');
    }

    setIsSubmitting(true);
    try {
      const payload = {
        content: content,
        files: uploadedFiles.map(f => f.id),  // ← Array of IDs
        notes: notes || null
      };

      const response = await api.post(
        `/lecturer-tasks/${taskId}/submit`,
        payload
      );

      if (response.data.success) {
        toast.success('Task submitted successfully');
        navigate('/tasks');
      }
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Submit failed');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Remove uploaded file
  const handleRemoveFile = (fileId: number) => {
    setUploadedFiles(prev => prev.filter(f => f.id !== fileId));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {/* Content */}
      <div>
        <label>Submission Content *</label>
        <textarea
          value={content}
          onChange={(e) => setContent(e.target.value)}
          placeholder="Enter your submission"
          rows={6}
          required
        />
      </div>

      {/* File Upload */}
      <div>
        <label>Attach Files</label>
        <input
          type="file"
          onChange={handleFileChange}
          multiple
          disabled={isUploading}
        />
      </div>

      {/* Uploaded Files List */}
      {uploadedFiles.length > 0 && (
        <div>
          <h4>Uploaded Files ({uploadedFiles.length}):</h4>
          <ul>
            {uploadedFiles.map(file => (
              <li key={file.id}>
                {file.file_name} ({(file.file_size / 1024).toFixed(2)} KB)
                <button
                  type="button"
                  onClick={() => handleRemoveFile(file.id)}
                >
                  Remove
                </button>
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Notes */}
      <div>
        <label>Notes</label>
        <textarea
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          placeholder="Additional notes (optional)"
          rows={3}
        />
      </div>

      {/* Submit Button */}
      <button
        type="submit"
        disabled={isSubmitting || isUploading}
      >
        {isSubmitting ? 'Submitting...' : 'Submit Task'}
      </button>
    </form>
  );
};

export default TaskSubmissionForm;
```

---

## 🐛 **DEBUGGING**

### **Check Upload Success:**
```javascript
console.log('Uploaded File ID:', fileId);
// Expected: 123 (number)
```

### **Check Submit Payload:**
```javascript
console.log('Submit Payload:', {
  content: content,
  files: fileIds,
  notes: notes
});
// Expected: { content: "...", files: [123, 456], notes: "..." }
```

### **Check Backend Logs:**
```bash
tail -f storage/logs/laravel.log | grep "Submitting task"
```

**Expected Log:**
```
Submitting task: {
  "task_id": 130,
  "student_id": 1,
  "submission_files": [123, 456],
  "submission_files_type": "array"
}
```

---

## ✅ **CHECKLIST**

- [ ] Upload file trả về file ID
- [ ] Lưu file IDs vào state/variable
- [ ] Submit với `files: [123, 456]` (array of integers)
- [ ] GET submission trả về files array
- [ ] Download file dùng download endpoint (không phải file_url)
- [ ] Handle errors properly
- [ ] Show loading states during upload/submit

---

**📅 Created: 2024-11-03**  
**🎯 Follow this guide để tránh lỗi không tải được file!**

