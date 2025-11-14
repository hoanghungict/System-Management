# 📎 Hướng dẫn tích hợp Task File Upload - Frontend Guide

## 📋 Tổng quan

Tài liệu này hướng dẫn chi tiết cách Frontend (FE) tích hợp chức năng **upload, xem, và xóa files** cho Task với Backend (BE) Laravel hiện tại.

---

## 🚀 API Endpoints

### 1. **Upload Files cho Task**

#### **Admin Tasks**
- **URL**: `POST /api/v1/admin-tasks/{taskId}/files`
- **Method**: `POST`
- **Auth**: Yêu cầu JWT Token với role `admin`

#### **Lecturer Tasks**
- **URL**: `POST /api/v1/lecturer-tasks/{taskId}/files`
- **Method**: `POST`
- **Auth**: Yêu cầu JWT Token với role `lecturer`

#### **Common Tasks** (Tất cả users đã đăng nhập)
- **URL**: `POST /api/v1/tasks/{taskId}/files`
- **Method**: `POST`
- **Auth**: Yêu cầu JWT Token

---

### 2. **Xóa File từ Task**

#### **Admin Tasks**
- **URL**: `DELETE /api/v1/admin-tasks/{taskId}/files/{fileId}`
- **Method**: `DELETE`
- **Auth**: Yêu cầu JWT Token với role `admin`

#### **Lecturer Tasks**
- **URL**: `DELETE /api/v1/lecturer-tasks/{taskId}/files/{fileId}`
- **Method**: `DELETE`
- **Auth**: Yêu cầu JWT Token với role `lecturer`

#### **Common Tasks**
- **URL**: `DELETE /api/v1/tasks/{taskId}/files/{fileId}`
- **Method**: `DELETE`
- **Auth**: Yêu cầu JWT Token

---

## 📤 Request Format

### **Upload Files**

#### **Headers:**
```javascript
{
  "Authorization": "Bearer <JWT_TOKEN>",
  // Content-Type sẽ được set tự động khi dùng FormData
}
```

#### **Body (FormData):**
```javascript
// Single file
FormData {
  "files": File
}

// Multiple files (recommended)
FormData {
  "files[]": File[]  // Array of files
}
```

#### **JavaScript Example:**
```javascript
// Single file upload
const uploadSingleFile = async (taskId, file) => {
  const formData = new FormData();
  formData.append('files', file); // Key: 'files'

  const response = await fetch(
    `${API_BASE_URL}/api/v1/admin-tasks/${taskId}/files`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
        // KHÔNG set Content-Type, browser sẽ tự động set với boundary
      },
      body: formData
    }
  );

  return await response.json();
};

// Multiple files upload (Recommended)
const uploadMultipleFiles = async (taskId, files) => {
  const formData = new FormData();
  
  // Cách 1: Append từng file với key 'files[]'
  files.forEach((file) => {
    formData.append('files[]', file);
  });

  // Cách 2: Hoặc có thể append với key 'files' và BE sẽ tự động convert
  // files.forEach((file) => {
  //   formData.append('files', file);
  // });

  const response = await fetch(
    `${API_BASE_URL}/api/v1/admin-tasks/${taskId}/files`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      },
      body: formData
    }
  );

  return await response.json();
};
```

#### **React Example với Axios:**
```typescript
import axios from 'axios';

interface UploadFileResponse {
  files: Array<{
    id: number;
    file_name: string;
    file_url: string;
    uploaded_by: number;
    created_at: string;
  }>;
}

const uploadTaskFiles = async (
  taskId: number,
  files: File[],
  userRole: 'admin' | 'lecturer' | 'common' = 'common'
): Promise<UploadFileResponse> => {
  const formData = new FormData();
  
  files.forEach((file) => {
    formData.append('files[]', file);
  });

  // Chọn endpoint dựa trên role
  const endpoint = userRole === 'admin' 
    ? `/api/v1/admin-tasks/${taskId}/files`
    : userRole === 'lecturer'
    ? `/api/v1/lecturer-tasks/${taskId}/files`
    : `/api/v1/tasks/${taskId}/files`;

  const response = await axios.post<UploadFileResponse>(
    `${API_BASE_URL}${endpoint}`,
    formData,
    {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'multipart/form-data', // Axios sẽ tự xử lý
      },
    }
  );

  return response.data;
};
```

---

### **Delete File**

#### **Headers:**
```javascript
{
  "Authorization": "Bearer <JWT_TOKEN>",
  "Content-Type": "application/json"
}
```

#### **JavaScript Example:**
```javascript
const deleteTaskFile = async (taskId, fileId, userRole = 'common') => {
  const endpoint = userRole === 'admin'
    ? `/api/v1/admin-tasks/${taskId}/files/${fileId}`
    : userRole === 'lecturer'
    ? `/api/v1/lecturer-tasks/${taskId}/files/${fileId}`
    : `/api/v1/tasks/${taskId}/files/${fileId}`;

  const response = await fetch(
    `${API_BASE_URL}${endpoint}`,
    {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'application/json'
      }
    }
  );

  return await response.json();
};
```

#### **React Example với Axios:**
```typescript
interface DeleteFileResponse {
  success: boolean;
  message: string;
}

const deleteTaskFile = async (
  taskId: number,
  fileId: number,
  userRole: 'admin' | 'lecturer' | 'common' = 'common'
): Promise<DeleteFileResponse> => {
  const endpoint = userRole === 'admin'
    ? `/api/v1/admin-tasks/${taskId}/files/${fileId}`
    : userRole === 'lecturer'
    ? `/api/v1/lecturer-tasks/${taskId}/files/${fileId}`
    : `/api/v1/tasks/${taskId}/files/${fileId}`;

  const response = await axios.delete<DeleteFileResponse>(
    `${API_BASE_URL}${endpoint}`,
    {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
      },
    }
  );

  return response.data;
};
```

---

## 📥 Response Format

### **Upload Files - Success (200)**

```json
{
  "files": [
    {
      "id": 55,
      "file_name": "document.pdf",
      "file_url": "http://localhost:8000/storage/task-files/123/document.pdf",
      "uploaded_by": 1,
      "created_at": "2024-01-15 10:30:00"
    },
    {
      "id": 56,
      "file_name": "image.jpg",
      "file_url": "http://localhost:8000/storage/task-files/123/image.jpg",
      "uploaded_by": 1,
      "created_at": "2024-01-15 10:30:01"
    }
  ]
}
```

#### **TypeScript Interface:**
```typescript
interface UploadedFile {
  id: number;
  file_name: string;
  file_url: string; // Full URL để truy cập file
  uploaded_by: number;
  created_at: string;
}

interface UploadFilesResponse {
  files: UploadedFile[];
}
```

---

### **Delete File - Success (200)**

```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

#### **TypeScript Interface:**
```typescript
interface DeleteFileResponse {
  success: boolean;
  message: string;
}
```

---

## ❌ Error Responses

### **400 Bad Request** - Không có files
```json
{
  "success": false,
  "message": "Không có files nào được upload"
}
```

### **401 Unauthorized** - Chưa đăng nhập
```json
{
  "success": false,
  "message": "User not authenticated"
}
```

### **403 Forbidden** - Không có quyền
```json
{
  "success": false,
  "message": "Bạn không có quyền upload files cho task này"
}
```

### **404 Not Found** - Task không tồn tại
```json
{
  "success": false,
  "message": "Task not found"
}
```

### **500 Internal Server Error** - Lỗi server
```json
{
  "success": false,
  "message": "An error occurred while uploading files: <error details>"
}
```

---

## 🎯 Complete React Hook Example

```typescript
import { useState } from 'react';
import axios from 'axios';

interface TaskFile {
  id: number;
  file_name: string;
  file_url: string;
  uploaded_by: number;
  created_at: string;
}

interface UseTaskFilesProps {
  taskId: number;
  userRole?: 'admin' | 'lecturer' | 'common';
}

export const useTaskFiles = ({ taskId, userRole = 'common' }: UseTaskFilesProps) => {
  const [uploading, setUploading] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const getEndpoint = (suffix: string) => {
    const base = userRole === 'admin'
      ? `/api/v1/admin-tasks/${taskId}`
      : userRole === 'lecturer'
      ? `/api/v1/lecturer-tasks/${taskId}`
      : `/api/v1/tasks/${taskId}`;
    return `${base}${suffix}`;
  };

  const uploadFiles = async (files: File[]): Promise<TaskFile[] | null> => {
    setUploading(true);
    setError(null);

    try {
      const formData = new FormData();
      files.forEach((file) => {
        formData.append('files[]', file);
      });

      const response = await axios.post<{ files: TaskFile[] }>(
        `${API_BASE_URL}${getEndpoint('/files')}`,
        formData,
        {
          headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
          },
        }
      );

      return response.data.files;
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Upload failed';
      setError(errorMessage);
      console.error('Upload error:', err);
      return null;
    } finally {
      setUploading(false);
    }
  };

  const deleteFile = async (fileId: number): Promise<boolean> => {
    setDeleting(true);
    setError(null);

    try {
      await axios.delete(
        `${API_BASE_URL}${getEndpoint(`/files/${fileId}`)}`,
        {
          headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
          },
        }
      );

      return true;
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Delete failed';
      setError(errorMessage);
      console.error('Delete error:', err);
      return false;
    } finally {
      setDeleting(false);
    }
  };

  return {
    uploadFiles,
    deleteFile,
    uploading,
    deleting,
    error,
  };
};
```

---

## 📱 React Component Example

```typescript
import React, { useState } from 'react';
import { useTaskFiles } from './hooks/useTaskFiles';

interface TaskFileUploadProps {
  taskId: number;
  userRole?: 'admin' | 'lecturer' | 'common';
  onUploadSuccess?: (files: TaskFile[]) => void;
}

export const TaskFileUpload: React.FC<TaskFileUploadProps> = ({
  taskId,
  userRole = 'common',
  onUploadSuccess,
}) => {
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const { uploadFiles, deleteFile, uploading, deleting, error } = useTaskFiles({
    taskId,
    userRole,
  });

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    if (event.target.files) {
      setSelectedFiles(Array.from(event.target.files));
    }
  };

  const handleUpload = async () => {
    if (selectedFiles.length === 0) return;

    const uploaded = await uploadFiles(selectedFiles);
    if (uploaded && onUploadSuccess) {
      onUploadSuccess(uploaded);
      setSelectedFiles([]);
    }
  };

  const handleDelete = async (fileId: number) => {
    const confirmed = window.confirm('Bạn có chắc muốn xóa file này?');
    if (!confirmed) return;

    const success = await deleteFile(fileId);
    if (success) {
      // Refresh file list hoặc remove từ UI
      console.log('File deleted successfully');
    }
  };

  return (
    <div className="task-file-upload">
      <input
        type="file"
        multiple
        onChange={handleFileSelect}
        disabled={uploading}
      />

      {selectedFiles.length > 0 && (
        <button onClick={handleUpload} disabled={uploading}>
          {uploading ? 'Uploading...' : `Upload ${selectedFiles.length} file(s)`}
        </button>
      )}

      {error && <div className="error">{error}</div>}
    </div>
  );
};
```

---

## 🔍 Xem/Download Files

### **Preview Files (Xem trước)**

File URLs được trả về trong response có format:
```
http://your-domain.com/storage/task-files/{taskId}/{filename}
```

#### **Cách sử dụng để preview:**
```typescript
// Display image
<img src={file.file_url} alt={file.file_name} />

// Open in new tab (preview)
window.open(file.file_url, '_blank');

// Preview PDF
<iframe src={file.file_url} width="100%" height="600px" />
```

### **Download Files với Tên Gốc**

**⚠️ Quan trọng:** Để file download về có **tên gốc** (không phải tên hash), bạn **KHÔNG** dùng `file_url` trực tiếp. Thay vào đó, sử dụng **download endpoint**.

#### **Download Endpoints:**

**Admin Tasks:**
- `GET /api/v1/admin-tasks/{taskId}/files/{fileId}/download`

**Lecturer Tasks:**
- `GET /api/v1/lecturer-tasks/{taskId}/files/{fileId}/download`

**Common Tasks:**
- `GET /api/v1/tasks/{taskId}/files/{fileId}/download`

#### **JavaScript Example:**
```javascript
const downloadTaskFile = async (taskId, fileId, fileFileName, userRole = 'common') => {
  const endpoint = userRole === 'admin'
    ? `/api/v1/admin-tasks/${taskId}/files/${fileId}/download`
    : userRole === 'lecturer'
    ? `/api/v1/lecturer-tasks/${taskId}/files/${fileId}/download`
    : `/api/v1/tasks/${taskId}/files/${fileId}/download`;

  // Tạo link ẩn để download
  const link = document.createElement('a');
  link.href = `${API_BASE_URL}${endpoint}`;
  link.setAttribute('download', fileFileName); // Tên file gốc
  link.setAttribute('Authorization', `Bearer ${getAuthToken()}`); // Không work, cần dùng fetch
  
  // Hoặc dùng fetch với credentials
  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${getAuthToken()}`
    }
  });

  if (response.ok) {
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileFileName; // Tên file gốc
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  }
};
```

#### **React Example:**
```typescript
const downloadFile = async (
  taskId: number,
  fileId: number,
  fileName: string,
  userRole: 'admin' | 'lecturer' | 'common' = 'common'
): Promise<void> => {
  const endpoint = userRole === 'admin'
    ? `/api/v1/admin-tasks/${taskId}/files/${fileId}/download`
    : userRole === 'lecturer'
    ? `/api/v1/lecturer-tasks/${taskId}/files/${fileId}/download`
    : `/api/v1/tasks/${taskId}/files/${fileId}/download`;

  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
      },
    });

    if (!response.ok) {
      throw new Error('Download failed');
    }

    // Get blob from response
    const blob = await response.blob();
    
    // Create object URL
    const url = window.URL.createObjectURL(blob);
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName; // ✅ Tên file gốc sẽ được dùng
    document.body.appendChild(link);
    link.click();
    
    // Cleanup
    window.URL.revokeObjectURL(url);
    document.body.removeChild(link);
  } catch (error) {
    console.error('Download error:', error);
    alert('Failed to download file');
  }
};

// Usage in component
<button onClick={() => downloadFile(task.id, file.id, file.file_name, userRole)}>
  Download {file.file_name}
</button>
```

#### **Axios Example:**
```typescript
const downloadFile = async (
  taskId: number,
  fileId: number,
  fileName: string,
  userRole: 'admin' | 'lecturer' | 'common' = 'common'
): Promise<void> => {
  const endpoint = userRole === 'admin'
    ? `/api/v1/admin-tasks/${taskId}/files/${fileId}/download`
    : userRole === 'lecturer'
    ? `/api/v1/lecturer-tasks/${taskId}/files/${fileId}/download`
    : `/api/v1/tasks/${taskId}/files/${fileId}/download`;

  try {
    const response = await axios.get(`${API_BASE_URL}${endpoint}`, {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
      },
      responseType: 'blob', // ✅ Quan trọng: phải set responseType là 'blob'
    });

    // Create blob URL and download
    const blob = new Blob([response.data]);
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName; // ✅ Tên file gốc
    document.body.appendChild(link);
    link.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(link);
  } catch (error) {
    console.error('Download error:', error);
    alert('Failed to download file');
  }
};
```

### **Lưu ý quan trọng:**

1. **file_url vs download endpoint:**
   - `file_url`: Dùng để **preview/xem trước** (browser sẽ mở file với tên hash)
   - `download endpoint`: Dùng để **download** (file sẽ có tên gốc)

2. **Tên file hash trong storage:**
   - Files trong storage có tên hash là **bình thường và an toàn**
   - Backend sẽ trả về file với tên gốc qua `Content-Disposition` header

3. **Download với tên gốc:**
   - Luôn dùng download endpoint khi user click "Download"
   - Browser sẽ tự động dùng tên từ `Content-Disposition` header

---

## ⚠️ Lưu ý quan trọng

### 1. **File Upload Format**
- ✅ **Supported**: `FormData` với key `files[]` hoặc `files`
- ✅ **Multiple files**: Append nhiều files vào FormData
- ❌ **NOT supported**: JSON base64 encoded files

### 2. **Authentication**
- **Bắt buộc**: Header `Authorization: Bearer <JWT_TOKEN>`
- Token phải hợp lệ và có quyền phù hợp (admin/lecturer)

### 3. **Permissions**
- **Admin**: Có thể upload/delete files cho mọi task
- **Lecturer**: Có thể upload/delete files cho task họ tạo hoặc được assign
- **Student**: Có thể upload files cho task họ được assign (qua common route)

### 4. **File URL**
- URL trả về là **public URL** - có thể truy cập trực tiếp
- Đảm bảo Laravel storage link đã được tạo: `php artisan storage:link`
- URL format: `{APP_URL}/storage/task-files/{taskId}/{filename}`

### 5. **Error Handling**
- Luôn check `response.status` trước khi parse JSON
- Xử lý các error codes: 400, 401, 403, 404, 500
- Hiển thị message lỗi từ BE cho user

### 6. **File Size & Types**
- Hiện tại BE chưa có validation strict về file size/types
- Nên validate ở FE trước khi upload:
  - Max file size (ví dụ: 10MB)
  - Allowed file types (pdf, jpg, png, doc, docx, etc.)

---

## 🧪 Testing với CURL

```bash
# Upload files
curl -X POST http://localhost:8000/api/v1/admin-tasks/121/files \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "files[]=@/path/to/file1.pdf" \
  -F "files[]=@/path/to/file2.jpg"

# Delete file
curl -X DELETE http://localhost:8000/api/v1/admin-tasks/121/files/55 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

---

## 📝 Checklist Integration

- [ ] Tạo service/hook để handle file upload
- [ ] Implement file selection UI (input type="file" multiple)
- [ ] Implement upload progress indicator
- [ ] Display uploaded files list với download/preview
- [ ] Implement delete file functionality
- [ ] Handle error cases (401, 403, 404, 500)
- [ ] Validate file types & size ở FE
- [ ] Test với single file upload
- [ ] Test với multiple files upload
- [ ] Test với các roles khác nhau (admin, lecturer)
- [ ] Test delete file functionality
- [ ] Test error handling

---

## 🔗 Related Documentation

- [API Endpoints](./API_ENDPOINTS.md)
- [Frontend Integration Guide](./FRONTEND_INTEGRATION_GUIDE.md)
- [Task Data Guide](./DATA_APIS_GUIDE.md)

---

**Version**: 1.0.0  
**Last Updated**: 2024-01-15  
**Backend Version**: Laravel 12 + Task Module

