# 🧪 Test Submission Flow - Debug Guide

## 📋 Vấn Đề Tóm Tắt

**Hiện tượng:**
- File được upload thành công (200 OK)
- Sau khi submit, vào xem lại submission → Không hiện file

**Nguyên nhân có thể:**
1. Files không được lưu vào `submission_files` khi submit
2. Files được lưu nhưng không được load đúng khi GET submission
3. File IDs không match với task_id

---

## 🔍 Debugging Steps

### **Bước 1: Test Upload File**

```bash
# Upload file
POST http://localhost:8082/api/v1/student-tasks/{task_id}/upload-file
Authorization: Bearer <token>
Content-Type: multipart/form-data
Body: file=<file>

# Response sẽ trả về file ID
{
  "success": true,
  "data": {
    "id": 1,  // ← Lưu ID này
    "file_name": "assignment.pdf",
    ...
  }
}
```

**✅ Lưu file ID:** `file_id = 1`

### **Bước 2: Test Submit với File ID**

```bash
# Submit task với file ID
POST http://localhost:8082/api/v1/student-tasks/{task_id}/submit
Authorization: Bearer <token>
Content-Type: application/json

Body:
{
  "content": "Bài nộp test",
  "files": [1]  // ← File ID từ bước 1
}
```

**Kiểm tra logs:**
```bash
tail -f storage/logs/laravel.log | grep "Submitting task"
```

**Expected log:**
```
Submitting task: {
  "task_id": 119,
  "student_id": 1,
  "submission_files": [1],  // ✅ Phải có file ID
  "submission_files_type": "array"
}
```

### **Bước 3: Kiểm tra Database**

```sql
-- Kiểm tra submission có files không
SELECT 
    id,
    task_id,
    student_id,
    submission_content,
    submission_files,  -- Phải là JSON array: [1] hoặc [1,2,3]
    submitted_at
FROM task_submissions
WHERE task_id = 119 AND student_id = 1
ORDER BY id DESC
LIMIT 1;

-- Kiểm tra file có tồn tại không
SELECT 
    id,
    task_id,
    name,
    path
FROM task_file
WHERE id = 1 AND task_id = 119;
```

**✅ Kết quả mong đợi:**
- `submission_files` phải là JSON: `[1]` hoặc `[1,2,3]`
- File phải tồn tại trong `task_file` table với đúng `task_id`

### **Bước 4: Test GET Submission**

```bash
# Get submission
GET http://localhost:8082/api/v1/student-tasks/{task_id}/submission
Authorization: Bearer <token>
```

**Kiểm tra logs:**
```bash
tail -f storage/logs/laravel.log | grep "Loading submission files"
```

**Expected log:**
```
Loading submission files: {
  "task_id": 119,
  "student_id": 1,
  "submission_files_raw": "[1]",  // Raw JSON từ DB
  "submission_files_casted": [1],  // Casted thành array
  "file_ids_count": 1
}

Files found: {
  "file_ids_requested": [1],
  "files_found_count": 1,
  "files_found_ids": [1]
}
```

**Expected response:**
```json
{
  "success": true,
  "data": {
    "files": [
      {
        "id": 1,
        "file_name": "assignment.pdf",
        "file_url": "http://localhost:8082/storage/..."
      }
    ]
  }
}
```

---

## 🐛 Common Issues & Solutions

### **Issue 1: submission_files là null hoặc []**

**Triệu chứng:**
- Log: `"submission_files": []` hoặc `null`
- Database: `submission_files` = `null` hoặc `[]`

**Nguyên nhân:**
- Frontend không gửi `files` array
- Files array rỗng
- File IDs không hợp lệ

**Giải pháp:**
1. Kiểm tra frontend có gửi `files: [1, 2, 3]` không
2. Kiểm tra file IDs có đúng không
3. Kiểm tra files có được upload trước khi submit không

### **Issue 2: Files không được load**

**Triệu chứng:**
- Database có `submission_files: [1]`
- Nhưng GET submission trả về `files: []`

**Nguyên nhân:**
- File không tồn tại trong `task_file` table
- File ID không match với `task_id`
- Query `whereIn` không tìm thấy file

**Giải pháp:**
1. Kiểm tra file có tồn tại không:
   ```sql
   SELECT * FROM task_file WHERE id = 1;
   ```
2. Kiểm tra file có đúng `task_id` không:
   ```sql
   SELECT * FROM task_file WHERE id = 1 AND task_id = 119;
   ```
3. Kiểm tra logs: `Files found` section

### **Issue 3: File IDs không match**

**Triệu chứng:**
- `file_ids_requested: [1, 2]`
- `files_found_count: 0`

**Nguyên nhân:**
- Files thuộc task khác
- Files đã bị xóa
- File IDs sai

**Giải pháp:**
1. Kiểm tra file có đúng `task_id` không
2. Kiểm tra file có bị soft delete không
3. Upload lại files và submit lại

---

## 📝 Test Checklist

- [ ] **Upload file thành công**
  - [ ] Response 200 OK
  - [ ] Có file ID trong response
  - [ ] File được lưu vào `task_file` table

- [ ] **Submit với file ID**
  - [ ] Request body có `files: [file_id]`
  - [ ] Response 200 OK
  - [ ] Log "Submitting task" có `submission_files: [file_id]`
  - [ ] Database có `submission_files` = `[file_id]`

- [ ] **GET submission**
  - [ ] Response 200 OK
  - [ ] Log "Loading submission files" có file IDs
  - [ ] Log "Files found" có files
  - [ ] Response có `files` array với file data

---

## 🔧 Quick Test Script

### **Test với Postman Collection:**

1. **Upload File:**
   ```
   POST /api/v1/student-tasks/119/upload-file
   Body: form-data, file=<file>
   → Lưu file_id từ response
   ```

2. **Submit Task:**
   ```
   POST /api/v1/student-tasks/119/submit
   Body: {
     "content": "Test submission",
     "files": [file_id]  // ID từ bước 1
   }
   → Kiểm tra response có success: true
   ```

3. **Get Submission:**
   ```
   GET /api/v1/student-tasks/119/submission
   → Kiểm tra files array có file không
   ```

### **Test với cURL:**

```bash
# 1. Upload file
FILE_ID=$(curl -X POST http://localhost:8082/api/v1/student-tasks/119/upload-file \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@test.pdf" \
  | jq -r '.data.id')

echo "File ID: $FILE_ID"

# 2. Submit với file ID
curl -X POST http://localhost:8082/api/v1/student-tasks/119/submit \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"content\": \"Test submission\",
    \"files\": [$FILE_ID]
  }"

# 3. Get submission
curl -X GET http://localhost:8082/api/v1/student-tasks/119/submission \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.data.files'
```

---

## 📊 Expected Flow

```
1. Upload File
   → File saved to task_file table
   → Returns: { id: 1, ... }

2. Submit Task
   → Frontend sends: { content: "...", files: [1] }
   → Backend saves: submission_files = [1]
   → Database: submission_files = "[1]" (JSON)

3. Get Submission
   → Backend reads: submission_files = [1]
   → Backend queries: WHERE id IN (1) AND task_id = 119
   → Backend returns: files: [{ id: 1, ... }]
```

---

## 🚨 Debug Commands

### **Check Logs:**
```bash
# Xem logs submit
tail -f storage/logs/laravel.log | grep -E "(Submitting task|Task submitted)"

# Xem logs load files
tail -f storage/logs/laravel.log | grep -E "(Loading submission files|Files found)"

# Xem tất cả logs liên quan
tail -f storage/logs/laravel.log | grep -E "(submission|file)"
```

### **Check Database:**
```sql
-- Check latest submission
SELECT * FROM task_submissions 
WHERE task_id = 119 
ORDER BY id DESC 
LIMIT 1;

-- Check files
SELECT * FROM task_file 
WHERE task_id = 119;

-- Check specific file
SELECT * FROM task_file WHERE id = 1;
```

---

**📅 Created: 2025-01-27**  
**🎯 Use this guide to debug submission files issue**

