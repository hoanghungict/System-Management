# Task Module Routes

## Tổng quan

Module Task sử dụng hệ thống routes được tổ chức và quản lý một cách chuyên nghiệp với các file sau:

- `api.php` - File chính chứa routes API
- `RouteHelper.php` - Helper class để đăng ký routes
- `RouteConfig.php` - Cấu hình routes
- `README.md` - Tài liệu hướng dẫn

## Cấu trúc Routes

### 1. Task Routes

```php
// CRUD cơ bản (tự động tạo bởi apiResource)
GET    /tasks              - index()
POST   /tasks              - store()
GET    /tasks/{task}       - show()
PUT    /tasks/{task}       - update()
DELETE /tasks/{task}       - destroy()

// Routes bổ sung
GET    /tasks/by-receiver  - getTasksByReceiver()
GET    /tasks/by-creator   - getTasksByCreator()
GET    /tasks/statistics   - statistics()
```

### 2. Calendar Routes

```php
// CRUD cơ bản
GET    /calendar                    - index()
POST   /calendar                    - store()
GET    /calendar/{calendar}         - show()
PUT    /calendar/{calendar}         - update()
DELETE /calendar/{calendar}         - destroy()

// View và thống kê
GET    /calendar/view               - view()
GET    /calendar/statistics         - statistics()
GET    /calendar/conflicts          - conflicts()

// Events
GET    /calendar/events/by-date     - eventsByDate()
GET    /calendar/events/by-range    - eventsByRange()
GET    /calendar/events/recurring   - recurringEvents()
GET    /calendar/events/upcoming    - upcomingEvents()
GET    /calendar/events/overdue     - overdueEvents()
GET    /calendar/events/by-type     - eventsByType()
GET    /calendar/events/count-by-status - eventsCountByStatus()

// Reminders
GET    /calendar/reminders          - reminders()
POST   /calendar/reminders          - setReminder()

// Recurring Events
POST   /calendar/recurring          - createRecurring()
PUT    /calendar/recurring/{calendar} - updateRecurring()

// Import/Export
POST   /calendar/data/export        - export()
POST   /calendar/data/import        - import()

// Sync
POST   /calendar/sync               - sync()
```

## Cách sử dụng

### 1. Thêm route mới

Để thêm route mới cho Task:

1. Cập nhật `RouteConfig::getTaskRoutes()` trong `RouteConfig.php`:

```php
'additional_routes' => [
    // ... routes hiện tại
    [
        'methods' => ['GET'],
        'uri' => 'new-route',
        'action' => 'newMethod',
        'name' => 'new-route'
    ],
]
```

2. Thêm method tương ứng trong `TaskController`

### 2. Thêm route mới cho Calendar

1. Cập nhật `RouteConfig::getCalendarRoutes()` trong `RouteConfig.php`
2. Thêm method tương ứng trong `CalendarController`

### 3. Tạo controller mới

1. Tạo controller trong thư mục `app/Http/Controllers/`
2. Thêm cấu hình routes trong `RouteConfig.php`
3. Đăng ký routes trong `api.php`

## Lợi ích

1. **Gọn gàng**: File `api.php` chỉ còn 20 dòng thay vì 97 dòng
2. **Dễ bảo trì**: Tất cả cấu hình routes tập trung trong `RouteConfig.php`
3. **Tái sử dụng**: `RouteHelper` có thể sử dụng cho các module khác
4. **Tự động**: Sử dụng `apiResource` để tự động tạo CRUD routes
5. **Có tổ chức**: Routes được nhóm theo chức năng rõ ràng

## Lưu ý

- Tất cả routes đều có prefix và name để tránh xung đột
- Sử dụng `apiResource` thay vì `resource` để chỉ tạo routes API (không có create/edit forms)
- Routes được sắp xếp theo thứ tự ưu tiên để tránh xung đột
