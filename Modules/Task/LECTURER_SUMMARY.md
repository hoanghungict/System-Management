# 📋 Tóm Tắt Phương Án Frontend - Lecturer Task Management

## 🎯 Flow Chính

```
1. Upload Files → Nhận File IDs
   ↓
2. Create Task với File IDs + Task Data
   ↓
3. Assign Task cho sinh viên
   ↓
4. Update/Delete Task khi cần
```

---

## 📤 Bước 1: Upload File

### **API:**
```
POST /api/v1/lecturer-tasks/{taskId}/upload-file
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

const response = await fetch(`/api/v1/lecturer-tasks/${taskId}/upload-file`, {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` },
  body: formData
});

const result = await response.json();
const fileId = result.data.id;  // ← Lưu lại
```

---

## 📝 Bước 2: Create Task

### **API:**
```
POST /api/v1/lecturer-tasks
Body: JSON {
  "title": "...",           // ← BẮT BUỘC
  "description": "...",      // ← BẮT BUỘC
  "deadline": "...",        // ← BẮT BUỘC
  "priority": "high",
  "files": [7, 8, 9]        // ← File IDs từ bước 1
}
```

### **Code:**
```typescript
const response = await fetch('/api/v1/lecturer-tasks', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: "Bài tập tuần 1",
    description: "Mô tả chi tiết",
    deadline: "2025-12-01 23:59:59",
    priority: "high",
    files: [7, 8, 9]  // File IDs đã upload
  })
});
```

---

## 🎯 Bước 3: Assign Task

### **API:**
```
PATCH /api/v1/lecturer-tasks/{taskId}/assign
Body: JSON {
  "receiver_ids": [1, 2, 3],
  "receiver_type": "student"
}
```

### **Code:**
```typescript
const response = await fetch(`/api/v1/lecturer-tasks/${taskId}/assign`, {
  method: 'PATCH',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    receiver_ids: [1, 2, 3],
    receiver_type: "student"
  })
});
```

---

## ⚠️ Lưu Ý Quan Trọng

1. **Upload trước, Create sau**
   - ✅ Upload files → Nhận File IDs
   - ✅ Create task với File IDs trong `files` array
   - ❌ KHÔNG gửi File objects trực tiếp trong create task

2. **Required Fields**
   - `title`: Bắt buộc
   - `description`: Bắt buộc
   - `deadline`: Bắt buộc (datetime format)
   - `files`: Optional (array of file IDs)

3. **File ID từ upload response**
   - Response có `data.id` → Đây là File ID
   - Lưu lại để dùng trong create task: `files: [fileId1, fileId2]`

4. **Error Handling**
   - **401**: Token hết hạn
   - **403**: Không có quyền
   - **404**: Task không tồn tại
   - **500**: Lỗi server

---

## 📚 Files Đã Tạo

1. **`FRONTEND_LECTURER_GUIDE.md`** - Hướng dẫn chi tiết với examples
2. **`frontend-lecturer-types.ts`** - TypeScript types/interfaces
3. **`frontend-lecturer-hooks.tsx`** - React hooks ready-to-use
4. **`LECTURER_SUMMARY.md`** - Tóm tắt nhanh (file này)

---

## 🚀 Quick Start

### **1. Copy types vào project:**
```bash
cp frontend-lecturer-types.ts src/types/lecturer-task.ts
```

### **2. Copy hooks vào project:**
```bash
cp frontend-lecturer-hooks.tsx src/hooks/useLecturerTaskManagement.tsx
```

### **3. Sử dụng trong component:**
```typescript
import { useLecturerTaskManagement } from './hooks/useLecturerTaskManagement';

function CreateTaskPage({ token }) {
  const {
    uploadFile,
    createTask,
    creating,
    uploadingFile
  } = useLecturerTaskManagement(token);

  // Upload file
  const handleUpload = async (file: File) => {
    const fileId = await uploadFile(file);
    console.log('File ID:', fileId);
  };

  // Create task
  const handleCreate = async (taskData, fileIds) => {
    await createTask({
      ...taskData,
      files: fileIds
    });
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
- [ ] Create task với task data + File IDs
- [ ] Assign task cho sinh viên
- [ ] Update task khi cần
- [ ] Delete file khi cần
- [ ] Handle error cases
- [ ] Show task list

---

## 🔄 So Sánh với Student

| Feature | Student | Lecturer |
|---------|---------|----------|
| Upload File | ✅ Single | ✅ Single + Multiple |
| Submit Task | ✅ | ❌ (Không có submit) |
| Create Task | ❌ | ✅ |
| Update Task | ❌ | ✅ |
| Assign Task | ❌ | ✅ |
| View Submissions | ✅ | ✅ (Xem submissions của students) |

---

**📅 Updated: 2025-11-01**  
**✅ Tested & Verified**  
**Role**: Lecturer (Giảng viên)

