# 🧪 Hướng Dẫn Test Submission Flow

## 📋 Quick Start

### **Cách 1: Test với File Upload (Khuyến nghị)**

```bash
# 1. Chuẩn bị file test
touch test.pdf  # Hoặc dùng file thật

# 2. Chạy script với file
cd /home/anhduong/projects/System-Management/System-Management
./Modules/Task/test_submission.sh 119 "your_jwt_token_here" test.pdf
```

**Script sẽ tự động:**
1. ✅ Upload file → Lấy file ID
2. ✅ Submit task với file ID
3. ✅ Get submission → Kiểm tra files

### **Cách 2: Test với File ID có sẵn**

```bash
# Nếu đã có file ID trong database
./Modules/Task/test_submission.sh 119 "your_jwt_token_here"

# Script sẽ hỏi file ID, nhập: 1 (hoặc ID khác)
```

---

## 🔑 Lấy JWT Token

### **Option 1: Từ Postman**
1. Login và copy token từ Authorization header
2. Hoặc copy từ response của login API

### **Option 2: Từ Browser DevTools**
1. Login vào frontend
2. Open DevTools → Application → Local Storage
3. Tìm và copy `token` hoặc `jwt_token`

### **Option 3: Test Login API**
```bash
curl -X POST http://localhost:8082/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "student@example.com",
    "password": "password"
  }'
# Copy token từ response
```

---

## 📝 Ví Dụ Test Đầy Đủ

### **Test Case 1: Upload File Mới và Submit**

```bash
# Tạo file test
echo "Test content" > test.pdf

# Chạy test
./Modules/Task/test_submission.sh 119 "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." test.pdf
```

**Expected Output:**
```
🧪 Testing Submission Flow
==========================================
Task ID: 119
Base URL: http://localhost:8082

📤 Step 1: Upload File
----------------------
Uploading: test.pdf
HTTP Status: 200
✅ File uploaded successfully. File ID: 5

📝 Step 2: Submit Task with File ID: 5
---------------------------------------------
HTTP Status: 200
✅ Task submitted successfully

📥 Step 3: Get Submission
-------------------------
HTTP Status: 200
✅ Files found in submission: 1 file(s)

  - File ID: 5

✅ Test completed!
```

### **Test Case 2: Test với File ID có sẵn**

```bash
./Modules/Task/test_submission.sh 119 "your_token"
# Nhập file ID khi được hỏi: 1
```

---

## 🔍 Kiểm Tra Kết Quả

### **1. Xem Logs**

```bash
# Xem logs submit
tail -20 storage/logs/laravel.log | grep "Submitting task"

# Xem logs load files
tail -20 storage/logs/laravel.log | grep "Loading submission files"

# Xem tất cả logs liên quan
tail -50 storage/logs/laravel.log | grep -E "(submission|file)"
```

### **2. Kiểm tra Database**

```bash
# Vào SQLite console
sqlite3 database/database.sqlite

# Kiểm tra submission
SELECT id, task_id, student_id, submission_files, submitted_at
FROM task_submissions
WHERE task_id = 119
ORDER BY id DESC
LIMIT 1;

# Kiểm tra files
SELECT id, task_id, name, path
FROM task_file
WHERE task_id = 119;
```

### **3. Test với Postman**

Sau khi chạy script, test lại với Postman:
```
GET http://localhost:8082/api/v1/student-tasks/119/submission
Authorization: Bearer <token>
```

---

## 🐛 Troubleshooting

### **Lỗi: "Failed to upload file"**

**Nguyên nhân:**
- File không tồn tại
- Token không hợp lệ
- Task không tồn tại

**Giải pháp:**
```bash
# Kiểm tra file
ls -la test.pdf

# Kiểm tra token
echo "your_token" | wc -c  # Phải > 50 characters

# Test upload thủ công
curl -X POST http://localhost:8082/api/v1/student-tasks/119/upload-file \
  -H "Authorization: Bearer your_token" \
  -F "file=@test.pdf"
```

### **Lỗi: "No files found in submission"**

**Kiểm tra:**
1. Xem logs: `tail -f storage/logs/laravel.log | grep "Submitting task"`
   - Phải có: `"submission_files": [1]`

2. Kiểm tra database:
   ```sql
   SELECT submission_files FROM task_submissions 
   WHERE task_id = 119 ORDER BY id DESC LIMIT 1;
   ```
   - Phải có: `[1]` hoặc `[1,2,3]`

3. Kiểm tra file có tồn tại:
   ```sql
   SELECT * FROM task_file WHERE id = 1 AND task_id = 119;
   ```

### **Lỗi: "Failed to submit task"**

**Kiểm tra:**
- Content có được gửi không (required field)
- Token có hợp lệ không
- Task có tồn tại không

---

## 📊 Expected Logs

### **Khi Submit thành công:**
```
[2025-01-27 10:30:00] local.INFO: Submitting task 
{
  "task_id": 119,
  "student_id": 1,
  "submission_files": [1, 2],
  "submission_files_type": "array"
}

[2025-01-27 10:30:01] local.INFO: Task submitted 
{
  "submission_id": 5,
  "submission_files": [1, 2],
  "submission_files_type": "array"
}
```

### **Khi Load Submission:**
```
[2025-01-27 10:30:05] local.INFO: Loading submission files 
{
  "task_id": 119,
  "student_id": 1,
  "submission_files_raw": "[1,2]",
  "submission_files_casted": [1, 2],
  "file_ids_count": 2
}

[2025-01-27 10:30:05] local.INFO: Files found 
{
  "file_ids_requested": [1, 2],
  "files_found_count": 2,
  "files_found_ids": [1, 2]
}
```

---

## ✅ Checklist Test

- [ ] Script chạy không lỗi
- [ ] File upload thành công (HTTP 200)
- [ ] File ID được extract đúng
- [ ] Submit thành công (HTTP 200)
- [ ] Logs có `submission_files: [file_id]`
- [ ] Database có `submission_files = [file_id]`
- [ ] GET submission trả về files array
- [ ] Files array có đúng file data

---

**📅 Created: 2025-01-27**  
**🎯 Use this guide to test submission flow and debug file issues**

