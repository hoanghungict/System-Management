# Cấu trúc RollCall Module trong Auth

## Tổ chức thư mục

```
Modules/Auth/app/
├── Http/
│   ├── Controllers/
│   │   └── RollCallController/
│   │       └── RollCallController.php
│   └── Requests/
│       └── RollCallRequest/
│           ├── CreateRollCallRequest.php
│           ├── UpdateRollCallStatusRequest.php
│           └── BulkUpdateRollCallStatusRequest.php
├── Models/
│   ├── RollCall.php
│   └── RollCallDetail.php
├── Repositories/
│   └── RollCallRepository/
│       ├── RollCallRepository.php
│       ├── RollCallRepositoryInterface.php
│       ├── RollCallDetailRepository.php
│       └── RollCallDetailRepositoryInterface.php
├── Services/
│   └── RollCallService/
│       └── RollCallService.php
└── routes/
    └── roll-call.php
```

## Namespace Structure

### Controllers

-   `Modules\Auth\app\Http\Controllers\RollCallController\RollCallController`

### Requests

-   `Modules\Auth\app\Http\Requests\RollCallRequest\CreateRollCallRequest`
-   `Modules\Auth\app\Http\Requests\RollCallRequest\UpdateRollCallStatusRequest`
-   `Modules\Auth\app\Http\Requests\RollCallRequest\BulkUpdateRollCallStatusRequest`

### Services

-   `Modules\Auth\app\Services\RollCallService\RollCallService`

### Repositories

-   `Modules\Auth\app\Repositories\RollCallRepository\RollCallRepository`
-   `Modules\Auth\app\Repositories\RollCallRepository\RollCallRepositoryInterface`
-   `Modules\Auth\app\Repositories\RollCallRepository\RollCallDetailRepository`
-   `Modules\Auth\app\Repositories\RollCallRepository\RollCallDetailRepositoryInterface`

### Models

-   `Modules\Auth\app\Models\RollCall`
-   `Modules\Auth\app\Models\RollCallDetail`

## API Routes

### Web Routes (có middleware auth)

-   `GET /roll-call` - Trang chọn lớp
-   `GET /roll-call/create` - Form tạo điểm danh
-   `POST /roll-call` - Tạo buổi điểm danh
-   `GET /roll-call/class/{classId}` - Danh sách buổi điểm danh theo lớp
-   `GET /roll-call/{id}` - Chi tiết buổi điểm danh
-   `PUT /roll-call/{rollCallId}/status` - Cập nhật trạng thái 1 sinh viên
-   `PUT /roll-call/{rollCallId}/bulk-status` - Cập nhật trạng thái hàng loạt
-   `PATCH /roll-call/{id}/complete` - Hoàn thành buổi điểm danh
-   `PATCH /roll-call/{id}/cancel` - Hủy buổi điểm danh
-   `GET /roll-call/statistics/class/{classId}` - Thống kê điểm danh

### API Routes (cho mobile/frontend)

-   `POST /api/roll-call` - Tạo buổi điểm danh
-   `GET /api/roll-call/class/{classId}` - Danh sách buổi điểm danh theo lớp
-   `GET /api/roll-call/{id}` - Chi tiết buổi điểm danh
-   `PUT /api/roll-call/{rollCallId}/status` - Cập nhật trạng thái
-   `PUT /api/roll-call/{rollCallId}/bulk-status` - Cập nhật hàng loạt
-   `PATCH /api/roll-call/{id}/complete` - Hoàn thành
-   `PATCH /api/roll-call/{id}/cancel` - Hủy
-   `GET /api/roll-call/statistics/class/{classId}` - Thống kê
-   `GET /api/roll-call/students/class/{classId}` - Danh sách sinh viên

## Database Tables

### roll_calls

-   `id` - Primary key
-   `class_id` - Foreign key to classrooms
-   `title` - Tiêu đề buổi điểm danh
-   `description` - Mô tả
-   `date` - Ngày giờ điểm danh
-   `status` - Trạng thái (active/completed/cancelled)
-   `created_by` - Foreign key to lecturers
-   `created_at`, `updated_at`

### roll_call_details

-   `id` - Primary key
-   `roll_call_id` - Foreign key to roll_calls
-   `student_id` - Foreign key to students
-   `status` - Trạng thái (present/absent/late/excused)
-   `note` - Ghi chú
-   `checked_at` - Thời gian điểm danh
-   `created_at`, `updated_at`

## Features

### ✅ Đã hoàn thành:

1. **Models** với relationships đầy đủ
2. **Migrations** cho 2 bảng
3. **Repository Pattern** với interfaces
4. **Service Layer** với business logic
5. **Controller** với đầy đủ API endpoints
6. **Request Validation** cho tất cả inputs
7. **Routes** cho web và API
8. **Cache Strategy** cho performance
9. **Error Handling** và logging
10. **API Documentation** chi tiết

### 🔧 Cấu trúc theo pattern:

-   **Controller** → **Service** → **Repository** → **Model**
-   **Request Validation** cho mỗi endpoint
-   **Repository Interface** cho dependency injection
-   **Cache** cho performance optimization
-   **Logging** cho debugging và monitoring
