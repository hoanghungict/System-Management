# 📥 Frontend File Download Integration Guide

**Date:** 2024-11-03  
**Status:** ✅ READY TO USE

---

## 🎯 **Mục Đích**

Hướng dẫn Frontend integrate file download sau khi backend đã fix tất cả issues.

---

## ✅ **ĐÃ SỬA Ở BACKEND**

### **1. Thêm `download_url` vào File Response**

**Trước:**
```json
{
  "id": 13,
  "file_name": "report.docx",
  "file_url": "http://localhost:8082/storage/task-files/130/abc.docx",
  "download_url": null  ← KHÔNG CÓ!
}
```

**Sau:**
```json
{
  "id": 13,
  "task_id": 130,
  "file_name": "report.docx",
  "file_url": "http://localhost:8082/storage/task-files/130/abc.docx",
  "download_url": "http://localhost:8082/api/v1/lecturer-tasks/130/files/13/download",
  "download_urls": {
    "common": "http://localhost:8082/api/v1/tasks/130/files/13/download",
    "lecturer": "http://localhost:8082/api/v1/lecturer-tasks/130/files/13/download",
    "admin": "http://localhost:8082/api/v1/admin-tasks/130/files/13/download"
  },
  "size": 1024567,
  "created_at": "2024-11-03 12:21:00"
}
```

### **2. Thêm Download Endpoint Cho Student**
- ✅ `GET /api/v1/lecturer-tasks/{task}/files/{file}/download`

### **3. Fix Permission Check**
- ✅ Có method `canUserDownloadFile()` riêng
- ✅ Download permissions rộng hơn delete permissions

---

## 📥 **CÁCH DOWNLOAD FILE**

### **Option 1: Dùng `download_url` (RECOMMENDED)**

**TypeScript/React:**
```typescript
interface TaskFile {
  id: number;
  task_id: number;
  file_name: string;
  file_url: string;
  download_url: string;  // ← Dùng field này!
  size: number;
}

const downloadFile = async (file: TaskFile) => {
  try {
    const response = await fetch(file.download_url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      }
    });

    if (!response.ok) {
      throw new Error('Download failed');
    }

    const blob = await response.blob();
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

### **Option 2: Dùng `download_urls` theo role**

```typescript
const downloadFileByRole = async (
  file: TaskFile,
  userRole: 'student' | 'lecturer' | 'admin'
) => {
  // Backend trả về download_urls cho cả 3 roles
  const downloadUrl = userRole === 'admin'
    ? file.download_urls.admin
    : userRole === 'lecturer'
    ? file.download_urls.lecturer
    : file.download_urls.common;

  const response = await fetch(downloadUrl, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${getAuthToken()}`
    }
  });

  // ... same download logic
};
```

### **Option 3: Dùng axios/api client**

```typescript
import api from '@/lib/api';

const downloadFile = async (file: TaskFile) => {
  try {
    const response = await api.get(file.download_url, {
      responseType: 'blob'
    });

    const blob = new Blob([response.data]);
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', file.file_name);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Download failed:', error);
    throw error;
  }
};
```

---

## 🔧 **UPDATE EXISTING FRONTEND CODE**

### **File: TaskFileList Component**

**Cần update:**
```typescript
// TaskFileList.tsx line ~278-313

// TRƯỚC (có thể build URL manually)
const downloadFile = (file: TaskFile) => {
  const userRole = getCurrentUserRole();
  const downloadUrl = `${API_BASE_URL}/api/v1/${userRole}-tasks/${file.task_id}/files/${file.id}/download`;
  // ...
};

// SAU (dùng download_url từ backend)
const downloadFile = async (file: TaskFile) => {
  if (!file.download_url) {
    toast.error('Download URL not available');
    return;
  }

  try {
    const response = await fetch(file.download_url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      }
    });

    if (!response.ok) {
      throw new Error('Download failed');
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', file.file_name);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    toast.success(`Downloaded: ${file.file_name}`);
  } catch (error) {
    console.error('Download error:', error);
    toast.error('Failed to download file');
  }
};
```

---

## 🚀 **QUICK INTEGRATION STEPS**

### **Step 1: Update TypeScript Interface**

```typescript
// types/task.ts
export interface TaskFile {
  id: number;
  task_id: number;
  file_name: string;
  file_url: string;
  download_url: string;      // ← ADD THIS
  download_urls?: {          // ← ADD THIS (optional)
    common: string;
    lecturer: string;
    admin: string;
  };
  size: number;
  path?: string;
  created_at: string;
}
```

### **Step 2: Update Download Function**

```typescript
// utils/fileDownload.ts
export const downloadTaskFile = async (file: TaskFile): Promise<void> => {
  if (!file.download_url) {
    throw new Error('Download URL not available');
  }

  const response = await fetch(file.download_url, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
    }
  });

  if (!response.ok) {
    throw new Error(`Download failed: ${response.statusText}`);
  }

  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = file.file_name;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
};
```

### **Step 3: Use in Component**

```typescript
// components/TaskFileList.tsx
import { downloadTaskFile } from '@/utils/fileDownload';

const TaskFileList = ({ files }: { files: TaskFile[] }) => {
  const handleDownload = async (file: TaskFile) => {
    try {
      await downloadTaskFile(file);
      toast.success('File downloaded');
    } catch (error) {
      toast.error('Download failed');
    }
  };

  return (
    <div>
      {files.map(file => (
        <div key={file.id}>
          <span>{file.file_name}</span>
          <button onClick={() => handleDownload(file)}>
            Download
          </button>
        </div>
      ))}
    </div>
  );
};
```

---

## 🔍 **TESTING**

### **Check Response Format:**

```javascript
// In TaskFileList component
console.log('🔍 File data:', file);
console.log('📥 Download URL:', file.download_url);
console.log('📁 File name:', file.file_name);

// Expected:
// download_url: "http://localhost:8082/api/v1/lecturer-tasks/130/files/13/download"
// file_name: "BaoCao_CauTrucBang_Task.docx"
```

### **Test Download:**

```javascript
const testDownload = async () => {
  const file = {
    id: 13,
    task_id: 130,
    file_name: 'test.docx',
    download_url: 'http://localhost:8082/api/v1/lecturer-tasks/130/files/13/download'
  };

  await downloadTaskFile(file);
  // Should download file with name "test.docx"
};
```

---

## ❌ **KHÔNG NÊN LÀM**

### **❌ Đừng dùng `file_url` để download**

```javascript
// BAD - File sẽ mở trong browser, không download
window.open(file.file_url);
```

**Tại sao?**
- `file_url` trỏ đến `/storage/task-files/...` (static file)
- Browser sẽ mở file thay vì download
- Không có tên file gốc

### **✅ Dùng `download_url`**

```javascript
// GOOD - File sẽ download với tên gốc
await fetch(file.download_url, { ... });
```

**Lý do:**
- Endpoint `/download` dùng `Content-Disposition: attachment`
- File download với tên gốc
- Có authentication check
- Có permission check

---

## 🐛 **TROUBLESHOOTING**

### **Issue: `download_url` vẫn là `null`**

**Kiểm tra:**
1. Backend có dùng `TaskFileResource` không?
2. Backend đã deploy changes chưa?

**Fix:**
```bash
# Backend
docker exec hpc_app php artisan config:clear
docker exec hpc_app php artisan cache:clear
docker-compose restart
```

---

### **Issue: Download trả về 403 Forbidden**

**Nguyên nhân:**
- User không phải receiver của task
- Token không hợp lệ

**Kiểm tra:**
```javascript
console.log('User ID:', getUserId());
console.log('Task receivers:', task.receivers);
```

---

### **Issue: Download trả về 404 Not Found**

**Nguyên nhân:**
- File không tồn tại trong storage
- File ID hoặc Task ID sai

**Kiểm tra:**
```javascript
console.log('File ID:', file.id);
console.log('Task ID:', file.task_id);
console.log('Download URL:', file.download_url);
```

---

## 📋 **CHECKLIST**

- [ ] Update TypeScript interfaces với `download_url`
- [ ] Update download function để dùng `download_url`
- [ ] Test download với file thực
- [ ] Verify file download với tên gốc
- [ ] Handle errors properly
- [ ] Show loading state during download
- [ ] Show success/error toasts

---

## 💡 **BEST PRACTICES**

### **1. Always check `download_url` exists**
```typescript
if (!file.download_url) {
  console.error('Download URL missing for file:', file.id);
  return;
}
```

### **2. Handle errors gracefully**
```typescript
try {
  await downloadFile(file);
} catch (error) {
  if (error.status === 403) {
    toast.error('You don\'t have permission to download this file');
  } else if (error.status === 404) {
    toast.error('File not found');
  } else {
    toast.error('Download failed. Please try again');
  }
}
```

### **3. Show download progress (optional)**
```typescript
const downloadWithProgress = async (file: TaskFile) => {
  const response = await fetch(file.download_url, {
    headers: { 'Authorization': `Bearer ${token}` }
  });

  const reader = response.body?.getReader();
  const contentLength = +response.headers.get('Content-Length')!;
  
  let receivedLength = 0;
  const chunks = [];
  
  while (true) {
    const { done, value } = await reader!.read();
    if (done) break;
    
    chunks.push(value);
    receivedLength += value.length;
    
    const progress = (receivedLength / contentLength) * 100;
    console.log(`Download progress: ${progress.toFixed(2)}%`);
  }
  
  const blob = new Blob(chunks);
  // ... trigger download
};
```

---

## 🎯 **CÁCH FIX NHANH**

### **Current Frontend Code:**
Từ logs tôi thấy frontend đang có:
```javascript
// TaskFileList.tsx:278
file_url: 'http://localhost:8082/storage/task-files/130/7U97oSHTShZPrl8M2C6ojfMYfzVJUiZyIsz6w90w.docx'
download_url: null  // ← Sau khi backend deploy, sẽ có giá trị!
```

### **Sau khi Backend Deploy:**
```javascript
// Giờ có download_url!
file_url: 'http://localhost:8082/storage/task-files/130/7U97oSHTShZPrl8M2C6ojfMYfzVJUiZyIsz6w90w.docx'
download_url: 'http://localhost:8082/api/v1/lecturer-tasks/130/files/13/download'
```

### **Frontend Code Update:**
```typescript
// Tìm function download file trong TaskFileList.tsx
// Thay vì build URL manually:
const downloadUrl = `${API_BASE_URL}/api/v1/${userRole}-tasks/${taskId}/files/${fileId}/download`;

// Dùng download_url từ backend:
const downloadUrl = file.download_url;

// Hoặc fallback nếu cần:
const downloadUrl = file.download_url || 
  `${API_BASE_URL}/api/v1/lecturer-tasks/${file.task_id}/files/${file.id}/download`;
```

---

## 🚀 **DEPLOYMENT CHECKLIST**

### **Backend:**
- [x] Sửa `TaskFileResource` để trả về `download_url`
- [x] Thêm `downloadFile()` method cho Student
- [x] Sửa permission check
- [x] Fix APP_URL config
- [ ] Deploy changes
- [ ] Clear cache: `php artisan config:clear && php artisan cache:clear`

### **Frontend:**
- [ ] Update TypeScript interfaces
- [ ] Update download function để dùng `download_url`
- [ ] Remove manual URL building code
- [ ] Test với backend mới
- [ ] Verify file downloads with original filename

---

## ✅ **EXPECTED BEHAVIOR**

1. **GET Task Detail** → Files có `download_url` field
2. **Click Download Button** → Gọi `file.download_url`
3. **Backend Response** → Binary file stream với `Content-Disposition: attachment; filename="..."` 
4. **Browser** → Download file với tên gốc

---

## 📞 **NẾU CÓ VẤN ĐỀ**

### **Backend không trả về `download_url`:**
```bash
# Check TaskResource có dùng TaskFileResource không
grep -r "TaskFileResource" Modules/Task/app/Transformers/

# Check backend logs
tail -f storage/logs/laravel.log
```

### **Download trả về error:**
```bash
# Check backend logs với file_id cụ thể
tail -f storage/logs/laravel.log | grep "download"
```

### **File download sai tên:**
```bash
# Check TaskFile model có tên file gốc không
# Column 'name' trong task_file table phải có giá trị
```

---

**🎯 Chúc mừng! Hệ thống file download giờ đã hoàn chỉnh!**

---

**Note:** Sau khi backend deploy, frontend chỉ cần reload trang là sẽ nhận được `download_url` trong response. Không cần thay đổi nhiều code!

