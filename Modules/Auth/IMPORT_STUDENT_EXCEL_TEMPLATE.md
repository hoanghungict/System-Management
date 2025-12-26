# 📋 Hướng dẫn Import Sinh viên từ Excel

## 📊 Cấu trúc File Excel

### Format yêu cầu:
- **Định dạng**: `.xlsx` hoặc `.xls`
- **Kích thước tối đa**: 10MB
- **Encoding**: UTF-8

### Cấu trúc cột (dạng table Excel):

| Tên cột | Bắt buộc | Kiểu dữ liệu | Mô tả | Ví dụ | Tên cột thay thế |
|---------|----------|--------------|-------|-------|------------------|
| **full_name** | ✅ | Text | Họ và tên đầy đủ | Nguyễn Văn A | fullname, họ và tên, ho ten, tên, name |
| **email** | ✅ | Email | Email sinh viên (unique) | sv001@example.com | e-mail, mail |
| **student_code** | ✅ | Text | Mã sinh viên (unique) | SV001 | studentcode, mã sinh viên, ma sinh vien, mssv, code |
| **birth_date** | ❌ | Date | Ngày sinh | 2000-01-15 hoặc 15/01/2000 | birthdate, ngày sinh, ngay sinh, dob, date_of_birth |
| **gender** | ❌ | Enum | Giới tính | male / female / other | giới tính, gioi tinh, sex |
| **address** | ❌ | Text | Địa chỉ | 123 Đường ABC, Quận XYZ | địa chỉ, dia chi, addr |
| **phone** | ❌ | Text | Số điện thoại | 0123456789 | số điện thoại, so dien thoai, tel, mobile, sdt |
| **class_id** | ❌ | Number | ID lớp học | 1, 2, 3... | classid, lớp, lop, class, class_code, classcode |

### Lưu ý:
- **Dòng đầu tiên**: Phải là header (tên các cột) - có thể dùng tên tiếng Việt hoặc tiếng Anh
- **Các dòng tiếp theo**: Dữ liệu sinh viên
- **Cột bắt buộc**: full_name, email, student_code
- **Cột tùy chọn**: Có thể để trống
- **Thứ tự cột**: Không quan trọng, hệ thống tự động nhận diện theo tên cột
- **Tên cột**: Hỗ trợ cả tiếng Việt và tiếng Anh (không phân biệt hoa thường)

## 📝 Ví dụ File Excel (Dạng Table)

### Cách 1: Tên cột tiếng Anh
| full_name | email | student_code | birth_date | gender | address | phone | class_id |
|-----------|-------|--------------|------------|--------|---------|-------|----------|
| Nguyễn Văn A | sv001@example.com | SV001 | 2000-01-15 | male | 123 Đường ABC | 0123456789 | 1 |
| Trần Thị B | sv002@example.com | SV002 | 2001-05-20 | female | 456 Đường XYZ | 0987654321 | 1 |
| Lê Văn C | sv003@example.com | SV003 | 1999-12-10 | male | | | 2 |

### Cách 2: Tên cột tiếng Việt
| Họ và tên | Email | Mã sinh viên | Ngày sinh | Giới tính | Địa chỉ | Số điện thoại | Lớp |
|-----------|-------|--------------|-----------|-----------|---------|---------------|-----|
| Nguyễn Văn A | sv001@example.com | SV001 | 2000-01-15 | male | 123 Đường ABC | 0123456789 | 1 |
| Trần Thị B | sv002@example.com | SV002 | 2001-05-20 | female | 456 Đường XYZ | 0987654321 | 1 |

### Cách 3: Thứ tự cột khác nhau (vẫn được)
| student_code | full_name | email | class_id | birth_date | gender | phone | address |
|--------------|-----------|-------|----------|------------|--------|-------|---------|
| SV001 | Nguyễn Văn A | sv001@example.com | 1 | 2000-01-15 | male | 0123456789 | 123 Đường ABC |
| SV002 | Trần Thị B | sv002@example.com | 1 | 2001-05-20 | female | 0987654321 | 456 Đường XYZ |

## ⚠️ Validation Rules

### 1. full_name
- **Bắt buộc**: Có
- **Độ dài**: Tối đa 255 ký tự
- **Lỗi thường gặp**: Để trống

### 2. email
- **Bắt buộc**: Có
- **Format**: Phải là email hợp lệ
- **Unique**: Không được trùng với email đã có trong hệ thống
- **Lỗi thường gặp**: 
  - Email không đúng format
  - Email đã tồn tại

### 3. student_code
- **Bắt buộc**: Có
- **Độ dài**: Tối đa 50 ký tự
- **Unique**: Không được trùng với mã sinh viên đã có
- **Lỗi thường gặp**:
  - Để trống
  - Mã sinh viên đã tồn tại

### 4. birth_date
- **Bắt buộc**: Không
- **Format**: 
  - `YYYY-MM-DD` (ví dụ: 2000-01-15)
  - `DD/MM/YYYY` (ví dụ: 15/01/2000)
- **Lỗi thường gặp**: Format không đúng

### 5. gender
- **Bắt buộc**: Không
- **Giá trị cho phép**: `male`, `female`, `other`
- **Lỗi thường gặp**: Giá trị không hợp lệ

### 6. address
- **Bắt buộc**: Không
- **Độ dài**: Tối đa 255 ký tự

### 7. phone
- **Bắt buộc**: Không
- **Độ dài**: Tối đa 20 ký tự

### 8. class_id
- **Bắt buộc**: Không
- **Kiểu**: Số nguyên
- **Validation**: Phải tồn tại trong bảng `class`
- **Lỗi thường gặp**: ID lớp không tồn tại

## 🚫 Các lỗi thường gặp

1. **Email trùng**: Email đã tồn tại trong hệ thống
2. **Mã sinh viên trùng**: Mã sinh viên đã tồn tại
3. **Lớp không tồn tại**: class_id không có trong database
4. **Format ngày sai**: birth_date không đúng format
5. **Thiếu cột bắt buộc**: full_name, email, student_code bị thiếu

## ✅ Checklist trước khi import

- [ ] File Excel có header row (dòng đầu tiên)
- [ ] Các cột bắt buộc đã điền đầy đủ
- [ ] Email không trùng với dữ liệu hiện có
- [ ] Mã sinh viên không trùng
- [ ] Format ngày sinh đúng (nếu có)
- [ ] class_id tồn tại trong hệ thống (nếu có)
- [ ] File không quá 10MB

## 📥 Download Template

Bạn có thể tạo file Excel với cấu trúc trên hoặc liên hệ admin để lấy template mẫu.

