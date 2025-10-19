# 📧 Email System Test Guide

## 🚀 **Cách test hệ thống email đã refactor**

### **1. Test từ Laravel Tinker (Khuyến nghị)**

```bash
# Mở Laravel tinker
php artisan tinker

# Load test file
include 'test_email_quick.php';

# Chạy test đầy đủ
EmailTest::quickTest();

# Hoặc test từng phần
EmailTest::testConnection();
EmailTest::sendTestEmail();
```

### **2. Test từ Artisan Command**

```bash
# Test tất cả
php artisan email:test

# Test chỉ Notifications EmailService
php artisan email:test --type=notifications

# Test chỉ Task EmailService
php artisan email:test --type=task

# Test chỉ SendEmailJob
php artisan email:test --type=job
```

### **3. Test từ PHP Script**

```bash
# Test đơn giản
php test_email_simple.php

# Test đầy đủ (cần Laravel environment)
php test_email_system.php
```

## 🔍 **Các test case được thực hiện**

### **1. Notifications EmailService Tests**
- ✅ Kiểm tra kết nối email
- ✅ Gửi email notification đơn giản
- ✅ Gửi email với template
- ✅ Gửi email hàng loạt
- ✅ Lấy email của user theo type

### **2. Task EmailService Tests**
- ✅ Gửi email báo cáo Task
- ✅ Test các method delegate
- ✅ Kiểm tra Facade pattern hoạt động

### **3. SendEmailJob Tests**
- ✅ Dispatch job thành công
- ✅ Job sử dụng Notifications EmailService
- ✅ Dependency injection hoạt động

### **4. Integration Tests**
- ✅ Service Providers bind đúng
- ✅ Dependency Inversion hoạt động
- ✅ Clean Architecture được áp dụng

## 📋 **Kết quả mong đợi**

```
🚀 QUICK EMAIL TEST - CLEAN ARCHITECTURE
========================================

1. Testing Notifications EmailService...
   Connection: ✅ OK
   Simple email: ✅ Queued
   Template email: ✅ Sent
   ✅ Notifications EmailService working!

2. Testing Task EmailService...
   Report email: ✅ Sent
   Delegate method: ✅ Working
   ✅ Task EmailService working!

3. Testing SendEmailJob...
   ✅ SendEmailJob dispatched!

4. Testing User Email Retrieval...
   Student email: student@example.com
   Lecturer email: lecturer@example.com
   Admin email: admin@example.com
   ✅ User email retrieval working!

🎉 ALL TESTS PASSED!
===================
✅ Clean Architecture implemented
✅ No code duplication
✅ Dependency Inversion applied
✅ Task module uses Notifications EmailService
✅ Email system refactored successfully!
```

## ⚠️ **Lưu ý quan trọng**

1. **Cấu hình Email**: Đảm bảo file `.env` có cấu hình email đúng:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your-email@gmail.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

2. **Queue Worker**: Để test job queue, cần chạy queue worker:
   ```bash
   php artisan queue:work --queue=emails
   ```

3. **Database**: Đảm bảo database có dữ liệu test cho các bảng `student`, `lecturer`, `users`

4. **Email Templates**: Tạo template test nếu cần:
   ```php
   // resources/views/emails/test.blade.php
   <h1>{{ $subject }}</h1>
   <p>Hello {{ $name }}!</p>
   <p>{{ $message }}</p>
   <p>Email được gửi đến: anhduong185203@gmail.com</p>
   ```

## 🐛 **Troubleshooting**

### **Lỗi Connection Failed**
- Kiểm tra cấu hình SMTP trong `.env`
- Kiểm tra firewall và network
- Test với email provider khác

### **Lỗi Template Not Found**
- Tạo template email trong `resources/views/emails/`
- Kiểm tra đường dẫn template

### **Lỗi User Email Not Found**
- Kiểm tra dữ liệu trong database
- Đảm bảo có user với ID = 1 trong các bảng

### **Lỗi Job Not Processing**
- Chạy queue worker: `php artisan queue:work`
- Kiểm tra queue configuration
- Xem logs trong `storage/logs/laravel.log`

## 🎯 **Kết luận**

Sau khi chạy test thành công, bạn có thể yên tâm rằng:
- ✅ Hệ thống email đã được refactor theo Clean Architecture
- ✅ Không còn trùng lặp code
- ✅ Task module sử dụng Notifications EmailService
- ✅ Dependency Inversion Principle được áp dụng đúng
- ✅ Email system hoạt động ổn định và có thể mở rộng
