# 📋 Tóm Tắt Phương Án Frontend - Student Task Submission

## 🎯 Flow Chính

```
1. Upload Files → Nhận File IDs
   ↓
2. Submit Task với File IDs + Content
   ↓
3. Get Submission để xem lại
```

---

## 📤 Bước 1: Upload File

### **API:**
```
POST /api/v1/student-tasks/{taskId}/upload-file
Body: FormData { file: File }
```

### **Response:**
```json
{
  "success": true,
  "data": {
    "id": 7  // ← Lưu ID này!
  }
}
```

### **Code:**
```typescript
const formData = new FormData();
formData.append('file', file);

const response = await fetch(`/api/v1/student-tasks/${taskId}/upload-file`, {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` },
  body: formData
});

const result = await response.json();
const fileId = result.data.id;  // ← Lưu lại
```

---

## 📝 Bước 2: Submit Task

### **API:**
```
POST /api/v1/student-tasks/{taskId}/submit
Body: JSON {
  "content": "...",     // ← BẮT BUỘC
  "files": [7, 8, 9]    // ← File IDs từ bước 1
}
```

### **Code:**
```typescript
const response = await fetch(`/api/v1/student-tasks/${taskId}/submit`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    content: "Nội dung bài nộp",
    files: [7, 8, 9]  // File IDs đã upload
  })
});
```

---

## 📥 Bước 3: Get Submission

### **API:**
```
GET /api/v1/student-tasks/{taskId}/submission
```

### **Response:**
```json
{
  "success": true,
  "data": {
    "content": "...",
    "files": [
      {
        "id": 7,
        "file_name": "test.pdf",
        "file_url": "http://..."
      }
    ]
  }
}
```

### **Code:**
```typescript
const response = await fetch(`/api/v1/student-tasks/${taskId}/submission`, {
  headers: { 'Authorization': `Bearer ${token}` }
});

const result = await response.json();
if (result.success) {
  console.log(result.data.files);  // Array of files
}
```

---

## ⚠️ Lưu Ý Quan Trọng

1. **Upload trước, Submit sau**
   - ✅ Upload files → Nhận File IDs
   - ✅ Submit với File IDs trong `files` array
   - ❌ KHÔNG gửi File objects trực tiếp trong submit

2. **Content là bắt buộc**
   - Field `content` không được để trống
   - Nếu thiếu sẽ lỗi: `"Validation failed: Submission content is required"`

3. **File ID từ upload response**
   - Response có `data.id` → Đây là File ID
   - Lưu lại để dùng trong submit: `files: [fileId1, fileId2]`

4. **Error Handling**
   - **404** khi GET submission: Chưa có submission (không phải lỗi)
   - **401**: Token hết hạn
   - **403**: Không có quyền
   - **500**: Lỗi server

---

## 📚 Files Đã Tạo

1. **`FRONTEND_UPDATE_GUIDE.md`** - Hướng dẫn chi tiết với examples
2. **`frontend-types.ts`** - TypeScript types/interfaces
3. **`frontend-hooks.tsx`** - React hooks ready-to-use

---

## 🚀 Quick Start

### **1. Copy types vào project:**
```bash
cp frontend-types.ts src/types/task.ts
```

### **2. Copy hooks vào project:**
```bash
cp frontend-hooks.tsx src/hooks/useTaskSubmission.tsx
```

### **3. Sử dụng trong component:**
```typescript
import { useTaskSubmission } from './hooks/useTaskSubmission';

function TaskSubmissionPage({ taskId, token }) {
  const {
    uploadFile,
    submitTask,
    submission,
    uploading,
    submitting
  } = useTaskSubmission(taskId, token);

  // Upload file
  const handleUpload = async (file: File) => {
    const fileId = await uploadFile(file);
    console.log('File ID:', fileId);
  };

  // Submit
  const handleSubmit = async (content: string, fileIds: number[]) => {
    await submitTask(content, fileIds);
  };

  return (
    <div>
      {/* UI here */}
    </div>
  );
}
```

---

## ✅ Checklist

- [ ] Upload file → Nhận File ID
- [ ] Lưu File IDs vào state
- [ ] Submit với content + File IDs
- [ ] Handle error cases
- [ ] Show submission sau khi submit
- [ ] Allow update submission
- [ ] Allow delete files

---

**📅 Updated: 2025-11-01**  
**✅ Tested & Verified**

