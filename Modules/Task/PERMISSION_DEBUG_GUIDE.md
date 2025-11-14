# 🔍 Debug Permission 403 Error Guide

**Issue:** Lecturer không thể update task của mình, bị lỗi 403 Forbidden

---

## 🐛 **Vấn Đề**

Frontend logs cho thấy:
```
403 Forbidden
Error: Bạn không có quyền thực hiện hành động này
```

---

## ✅ **Đã Sửa**

### **1. Fix Strict Comparison**

**File:** `Modules/Task/app/Services/PermissionService.php`

**Changes:**
```php
// BEFORE - Có thể fail nếu so sánh string vs int
if ($task->creator_id == $userContext->id && 
    $task->creator_type == $userContext->user_type) {

// AFTER - Strict comparison với int casting
$userId = (int) $this->getUserId($userContext);
$creatorId = (int) $task->creator_id;

if ($creatorId === $userId && 
    $task->creator_type === $userContext->user_type) {
```

### **2. Clear Cache Before Check**

**File:** `Modules/Task/app/Http/Controllers/Lecturer/LecturerTaskController.php`

**Added:**
```php
// ✅ Clear permission cache trước khi check để đảm bảo fresh check
$this->permissionService->clearPermissionCache($userContext, $id);
```

### **3. Enhanced Logging**

**Added detailed logs để debug:**
```php
\Log::info('Lecturer update task - Permission check', [
    'task_id' => $id,
    'lecturer_id' => $userId,
    'lecturer_type' => $userType,
    'task_creator_id' => $task->creator_id,
    'task_creator_type' => $task->creator_type,
    'is_creator' => $task->creator_id == $userId && $task->creator_type == ($userType ?? 'lecturer'),
]);
```

---

## 🔍 **Cách Debug**

### **1. Check Laravel Logs**

```bash
tail -f storage/logs/laravel.log | grep -E "(Lecturer update|Permission|403)"
```

**Expected logs:**
```
[INFO] Lecturer update task - Permission check: {...}
[INFO] PermissionService: Edit allowed - User is creator: {...}
[INFO] Lecturer update task - Permission allowed: {...}
```

**Nếu thấy:**
```
[WARNING] Lecturer update task - Permission denied: {...}
[WARNING] PermissionService: Edit denied - User is neither creator nor receiver: {...}
```

→ Check logs để xem:
- `user_id` vs `creator_id` có match không
- `user_type` vs `creator_type` có match không

### **2. Check Task Creator**

```sql
SELECT id, creator_id, creator_type, title 
FROM tasks 
WHERE id = <task_id>;
```

**Verify:**
- `creator_id` = lecturer ID
- `creator_type` = 'lecturer'

### **3. Check JWT Token**

Frontend cần log JWT payload:
```javascript
const payload = JSON.parse(atob(token.split('.')[1]));
console.log('JWT Payload:', payload);
// Verify: payload.id === lecturer ID
```

### **4. Clear All Permission Cache**

```bash
php artisan cache:clear
# Hoặc
php artisan tinker
Cache::flush();
```

### **5. Test Direct API Call**

```bash
curl -X PATCH http://localhost:8082/api/v1/lecturer-tasks/130 \
  -H "Authorization: Bearer <lecturer_token>" \
  -H "Content-Type: application/json" \
  -d '{"title": "Test Update"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Task updated successfully",
  "data": {...}
}
```

**If 403:**
```json
{
  "success": false,
  "message": "Access denied. You can only update tasks you created or tasks assigned to you."
}
```

---

## ✅ **Checklist**

- [ ] Verify lecturer ID từ JWT matches `task.creator_id`
- [ ] Verify `task.creator_type` = 'lecturer'
- [ ] Clear permission cache: `php artisan cache:clear`
- [ ] Check logs: `tail -f storage/logs/laravel.log`
- [ ] Test với direct API call
- [ ] Verify `canCreateTasks()` returns true for lecturer

---

## 🐛 **Common Issues**

### **Issue 1: Type Mismatch**

**Problem:** `creator_id` là string nhưng `userContext->id` là int (hoặc ngược lại)

**Fix:** ✅ Đã sửa với int casting và strict comparison

### **Issue 2: Cached Permission**

**Problem:** Permission cache đang cached với giá trị cũ (denied)

**Fix:** ✅ Đã thêm `clearPermissionCache()` trước khi check

### **Issue 3: canCreateTasks() Returns False**

**Problem:** Lecturer không pass `canCreateTasks()` check

**Check:**
```php
// PermissionService.php
public function canCreateTasks(object $userContext): bool
{
    // Admin và lecturer đều có thể create tasks
    return $this->isAdmin($userContext) || $this->isLecturer($userContext);
}
```

**Verify lecturer is detected:**
```php
public function isLecturer(object $userContext): bool
{
    return isset($userContext->user_type) && 
           $userContext->user_type === 'lecturer';
}
```

### **Issue 4: Task Not Loaded with Receivers**

**Problem:** Permission check receiver nhưng task chưa load receivers

**Fix:** ✅ Đã thêm `Task::with('receivers')->find($taskId)` trong checkTaskEditPermission()

---

## 📊 **Permission Flow**

```
1. Lecturer calls PATCH /api/v1/lecturer-tasks/{id}
   ↓
2. LecturerTaskController::update()
   ↓
3. Clear permission cache
   ↓
4. Check permission: permissionService->canEditTask()
   ↓
5. PermissionService::checkTaskEditPermission()
   ↓
6. Check 1: Is creator?
   - creator_id === user_id?
   - creator_type === user_type?
   - canCreateTasks()?
   ↓
7. Check 2: Is receiver? (if lecturer)
   - Is lecturer in receivers?
   ↓
8. If permission allowed → TaskService::updateTask()
   ↓
9. TaskService::validateEditTaskPermission() (double check)
   ↓
10. Update task
```

---

## 🧪 **Test Cases**

### **Test 1: Lecturer Updates Own Task**

```php
// Setup
$lecturer = Lecturer::factory()->create();
$task = Task::factory()->create([
    'creator_id' => $lecturer->id,
    'creator_type' => 'lecturer',
]);

// Test
$userContext = (object) [
    'id' => $lecturer->id,
    'user_type' => 'lecturer',
];

$canEdit = $permissionService->canEditTask($userContext, $task->id);
// ✅ Expected: true
```

### **Test 2: Lecturer Updates Assigned Task**

```php
// Setup
$lecturer = Lecturer::factory()->create();
$task = Task::factory()->create([
    'creator_id' => 999, // Other lecturer
    'creator_type' => 'lecturer',
]);

// Add lecturer as receiver
$task->receivers()->create([
    'receiver_id' => $lecturer->id,
    'receiver_type' => 'lecturer',
]);

// Test
$userContext = (object) [
    'id' => $lecturer->id,
    'user_type' => 'lecturer',
];

$canEdit = $permissionService->canEditTask($userContext, $task->id);
// ✅ Expected: true (vì là receiver)
```

### **Test 3: Lecturer Updates Other Lecturer's Task**

```php
// Setup
$lecturer1 = Lecturer::factory()->create();
$lecturer2 = Lecturer::factory()->create();
$task = Task::factory()->create([
    'creator_id' => $lecturer1->id,
    'creator_type' => 'lecturer',
]);

// Test với lecturer2
$userContext = (object) [
    'id' => $lecturer2->id,
    'user_type' => 'lecturer',
];

$canEdit = $permissionService->canEditTask($userContext, $task->id);
// ❌ Expected: false (không phải creator và không phải receiver)
```

---

## 🔧 **Quick Fix Commands**

```bash
# Clear all cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Restart services
docker-compose restart

# Check routes
php artisan route:list | grep lecturer-tasks

# Test API
curl -X PATCH http://localhost:8082/api/v1/lecturer-tasks/130 \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"title": "Test"}'
```

---

## 📝 **Next Steps**

1. ✅ Clear permission cache trước khi check
2. ✅ Fix strict comparison với int casting
3. ✅ Add detailed logging
4. ⏳ Test với real lecturer token
5. ⏳ Check logs để verify permission check
6. ⏳ Verify task creator matches lecturer ID

---

**🎯 Vấn đề sẽ được resolve sau khi clear cache và verify logs!**

