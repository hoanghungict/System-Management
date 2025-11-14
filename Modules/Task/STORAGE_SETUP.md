# 📎 Hướng dẫn Setup Storage cho Task File Upload

## 🔍 Vấn đề

Khi upload file thành công nhưng không truy cập được file (lỗi **403 FORBIDDEN**), nguyên nhân thường là:

1. ❌ **Chưa tạo storage symlink** - Laravel cần symlink để truy cập files qua URL
2. ❌ **File permissions không đúng** - Server không có quyền đọc files
3. ❌ **Storage directory chưa tồn tại** - Thư mục task-files chưa được tạo

---

## ✅ Giải pháp nhanh

### **Option 1: Chạy Script Tự động (Khuyên dùng)**

```bash
cd /path/to/laravel-project
chmod +x scripts/setup-storage.sh
./scripts/setup-storage.sh
```

Script sẽ tự động:
- ✅ Tạo storage symlink
- ✅ Set permissions (775)
- ✅ Tạo task-files directory
- ✅ Verify configuration

---

### **Option 2: Chạy Manual Commands**

#### **1. Tạo Storage Symlink**

```bash
cd /path/to/laravel-project
php artisan storage:link
```

**Kết quả:** Tạo symlink `public/storage` → `storage/app/public`

**Verify:**
```bash
ls -la public/storage
# Should see: public/storage -> ../storage/app/public
```

#### **2. Set File Permissions**

```bash
# Set permissions cho storage
chmod -R 775 storage/

# Set permissions cho public/storage (sau khi tạo symlink)
chmod -R 775 public/storage

# Nếu dùng Docker hoặc cần fix ownership
chown -R www-data:www-data storage/
chown -R www-data:www-data public/storage
```

#### **3. Tạo Task-Files Directory**

```bash
mkdir -p storage/app/public/task-files
chmod -R 775 storage/app/public/task-files
```

---

## 🧪 Kiểm tra Setup

### **1. Verify Symlink**

```bash
ls -la public/ | grep storage
# Should see: lrwxrwxrwx ... storage -> ../storage/app/public
```

### **2. Verify File Exists**

```bash
# Upload file qua API, sau đó check:
ls -la storage/app/public/task-files/{taskId}/
# Should see your uploaded file
```

### **3. Test URL trong Browser**

Mở browser và test:
```
http://localhost:8082/storage/task-files/{taskId}/{filename}
```

**Expected:** File được download/hiển thị  
**If 403:** Check permissions và symlink

---

## 🔧 Troubleshooting

### **Lỗi: Symlink không tạo được**

```bash
# Xóa symlink cũ nếu có
rm public/storage

# Tạo lại
php artisan storage:link
```

### **Lỗi: Permission Denied**

```bash
# Check current permissions
ls -la storage/app/public/

# Fix permissions
sudo chmod -R 775 storage/
sudo chown -R www-data:www-data storage/
```

### **Lỗi: File không tồn tại**

```bash
# Check file có tồn tại không
ls -la storage/app/public/task-files/{taskId}/{filename}

# Check database record
# SELECT * FROM task_file WHERE task_id = {taskId};

# Verify path trong database match với file system
```

### **Lỗi: 404 Not Found thay vì 403**

**Nguyên nhân:** Symlink chưa tạo hoặc sai path

**Fix:**
```bash
# Check symlink
ls -la public/storage

# Recreate symlink
rm public/storage
php artisan storage:link
```

---

## 📋 Configuration

### **Filesystems Config** (`config/filesystems.php`)

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),        // storage/app/public
    'url' => env('APP_URL').'/storage',         // http://domain.com/storage
    'visibility' => 'public',
],
```

### **Storage Links** (`config/filesystems.php`)

```php
'links' => [
    public_path('storage') => storage_path('app/public'),
],
```

---

## 🌐 URL Format

Sau khi setup, file URLs sẽ có format:

```
{APP_URL}/storage/task-files/{taskId}/{filename}
```

**Ví dụ:**
```
http://localhost:8082/storage/task-files/125/abc123.pdf
http://yourdomain.com/storage/task-files/125/abc123.pdf
```

---

## 📁 File Structure

```
storage/
└── app/
    └── public/              # Public disk root
        └── task-files/      # Task files directory
            ├── 124/         # Task ID 124
            │   ├── file1.pdf
            │   └── file2.jpg
            └── 125/         # Task ID 125
                └── document.docx

public/
└── storage -> ../storage/app/public  # Symlink
```

---

## 🔐 Security Notes

1. **Public Files:** Files trong `storage/app/public` là **public**, có thể truy cập qua URL
2. **Permissions:** Chỉ set 775 cho storage, không set 777
3. **Symlink:** Đảm bảo symlink luôn point đến đúng location
4. **.gitignore:** Files trong storage không được commit vào git

---

## ✅ Checklist Setup

- [ ] Run `php artisan storage:link`
- [ ] Verify symlink: `ls -la public/storage`
- [ ] Set permissions: `chmod -R 775 storage/ public/storage`
- [ ] Create directory: `mkdir -p storage/app/public/task-files`
- [ ] Test upload file qua API
- [ ] Verify file exists: `ls storage/app/public/task-files/{taskId}/`
- [ ] Test URL trong browser
- [ ] Check response có `file_url` đúng format

---

## 🚀 Production Deployment

Khi deploy lên production, đảm bảo:

1. **Chạy setup script:**
   ```bash
   php artisan storage:link
   chmod -R 775 storage/ public/storage
   ```

2. **Set proper ownership:**
   ```bash
   chown -R www-data:www-data storage/ public/storage
   ```

3. **Verify nginx/apache config** cho phép truy cập `/storage` path

4. **Check APP_URL** trong `.env` đúng với domain production

---

**Version:** 1.0.0  
**Last Updated:** 2024-01-15

