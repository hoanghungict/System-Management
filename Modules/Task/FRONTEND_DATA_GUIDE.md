# 📋 Frontend Data Guide - Tạo Task

## 🎯 **Tổng Quan**

Hướng dẫn chi tiết về dữ liệu Frontend cần gửi để tạo task thành công.

---

## 📤 **API Endpoint**

```http
POST /api/v1/lecturer-tasks
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

---

## 🔧 **Cấu Trúc Dữ Liệu Cơ Bản**

### **1. Thông Tin Task**

```json
{
  "title": "string (required)",
  "description": "string (required)", 
  "due_date": "YYYY-MM-DD (required)",
  "deadline": "YYYY-MM-DD HH:mm:ss (required)",
  "priority": "low|medium|high (optional, default: medium)",
  "status": "pending|in_progress|completed|cancelled (optional, default: pending)"
}
```

### **2. Thông Tin Người Tạo**

```json
{
  "creator_id": "number (required)",
  "creator_type": "lecturer|student (required)"
}
```

**⚠️ LƯU Ý:**
- **Admin** thực chất là **lecturer** với `is_admin: true`
- Khi admin tạo task, sử dụng `creator_type: "lecturer"`

### **3. Danh Sách Người Nhận (Receivers)**

```json
{
  "receivers": [
    {
      "receiver_id": "number (required)",
      "receiver_type": "string (required)"
    }
  ]
}
```

---

## 🎯 **Các Loại Receiver Type**

### **A. Gửi Cho Cá Nhân**

```json
{
  "receivers": [
    {
      "receiver_id": 1,
      "receiver_type": "student"
    },
    {
      "receiver_id": 2, 
      "receiver_type": "lecturer"
    }
  ]
}
```

### **B. Gửi Cho Cả Lớp**

```json
{
  "receivers": [
    {
      "receiver_id": 1,
      "receiver_type": "classes"
    }
  ]
}
```

**✅ Kết quả:** Tất cả sinh viên trong lớp ID = 1 sẽ nhận task

### **C. Gửi Cho Cả Khoa**

```json
{
  "receivers": [
    {
      "receiver_id": 1,
      "receiver_type": "department"
    }
  ]
}
```

**✅ Kết quả:** Tất cả sinh viên trong khoa ID = 1 sẽ nhận task

### **D. Gửi Cho Tất Cả Sinh Viên**

```json
{
  "receivers": [
    {
      "receiver_id": 0,
      "receiver_type": "all_students"
    }
  ]
}
```

**✅ Kết quả:** Tất cả sinh viên trong hệ thống sẽ nhận task

### **E. Gửi Cho Tất Cả Giảng Viên**

```json
{
  "receivers": [
    {
      "receiver_id": 0,
      "receiver_type": "all_lecturers"
    }
  ]
}
```

**✅ Kết quả:** Tất cả giảng viên trong hệ thống sẽ nhận task

### **F. Gửi Hỗn Hợp (Nhiều Loại Receivers)**

```json
{
  "receivers": [
    {
      "receiver_id": 1,
      "receiver_type": "student"
    },
    {
      "receiver_id": 2,
      "receiver_type": "lecturer"
    },
    {
      "receiver_id": 1,
      "receiver_type": "classes"
    },
    {
      "receiver_id": 1,
      "receiver_type": "department"
    }
  ]
}
```

**✅ Kết quả:** 
- Sinh viên ID = 1 nhận task
- Giảng viên ID = 2 nhận task  
- Tất cả sinh viên trong lớp ID = 1 nhận task
- Tất cả sinh viên trong khoa ID = 1 nhận task

---

## 📝 **Ví Dụ Hoàn Chỉnh**

### **Ví Dụ 1: Gửi Task Cho Cả Lớp**

```json
{
  "title": "Bài tập môn Lập trình Web",
  "description": "Làm bài tập về React và Node.js",
  "due_date": "2026-02-25",
  "deadline": "2026-02-25 23:59:59",
  "priority": "high",
  "status": "pending",
  "creator_id": 1,
  "creator_type": "lecturer",
  "receivers": [
    {
      "receiver_id": 1,
      "receiver_type": "classes"
    }
  ]
}
```

### **Ví Dụ 2: Gửi Task Cho Nhiều Người**

```json
{
  "title": "Họp nhóm dự án",
  "description": "Thảo luận về tiến độ dự án",
  "due_date": "2026-02-20",
  "deadline": "2026-02-20 17:00:00",
  "priority": "medium",
  "status": "pending",
  "creator_id": 1,
  "creator_type": "lecturer",
  "receivers": [
    {
      "receiver_id": 1,
      "receiver_type": "student"
    },
    {
      "receiver_id": 2,
      "receiver_type": "student"
    },
    {
      "receiver_id": 3,
      "receiver_type": "lecturer"
    }
  ]
}
```

### **Ví Dụ 3: Gửi Task Cho Tất Cả Sinh Viên**

```json
{
  "title": "Thông báo quan trọng",
  "description": "Thông báo về lịch thi cuối kỳ",
  "due_date": "2026-02-15",
  "deadline": "2026-02-15 12:00:00",
  "priority": "high",
  "status": "pending",
  "creator_id": 1,
  "creator_type": "lecturer",
  "receivers": [
    {
      "receiver_id": 0,
      "receiver_type": "all_students"
    }
  ]
}
```

### **Ví Dụ 4: Gửi Task Hỗn Hợp (Giảng Viên + Lớp + Khoa)**

```json
{
  "title": "Họp dự án nghiên cứu",
  "description": "Thảo luận về dự án nghiên cứu khoa học",
  "due_date": "2026-02-28",
  "deadline": "2026-02-28 14:00:00",
  "priority": "high",
  "status": "pending",
  "creator_id": 1,
  "creator_type": "lecturer",
  "receivers": [
    {
      "receiver_id": 5,
      "receiver_type": "lecturer"
    },
    {
      "receiver_id": 1,
      "receiver_type": "classes"
    },
    {
      "receiver_id": 2,
      "receiver_type": "department"
    }
  ]
}
```

**✅ Kết quả:**
- Giảng viên ID = 5 nhận task
- Tất cả sinh viên trong lớp ID = 1 nhận task
- Tất cả sinh viên trong khoa ID = 2 nhận task

### **Ví Dụ 5: Gửi Task Cho Cá Nhân + Lớp Cụ Thể**

```json
{
  "title": "Bài tập nhóm",
  "description": "Làm bài tập nhóm về AI và Machine Learning",
  "due_date": "2026-03-05",
  "deadline": "2026-03-05 23:59:59",
  "priority": "medium",
  "status": "pending",
  "creator_id": 1,
  "creator_type": "lecturer",
  "receivers": [
    {
      "receiver_id": 10,
      "receiver_type": "student"
    },
    {
      "receiver_id": 11,
      "receiver_type": "student"
    },
    {
      "receiver_id": 3,
      "receiver_type": "classes"
    }
  ]
}
```

**✅ Kết quả:**
- Sinh viên ID = 10 nhận task
- Sinh viên ID = 11 nhận task
- Tất cả sinh viên trong lớp ID = 3 nhận task

---

## ⚠️ **Validation Rules**

### **1. Ngày Tháng**
- `due_date`: Phải là hôm nay hoặc trong tương lai
- `deadline`: Phải là thời điểm trong tương lai

### **2. Receiver Type**
- `student`: Gửi cho sinh viên cụ thể
- `lecturer`: Gửi cho giảng viên cụ thể  
- `classes`: Gửi cho cả lớp
- `department`: Gửi cho cả khoa
- `all_students`: Gửi cho tất cả sinh viên
- `all_lecturers`: Gửi cho tất cả giảng viên

### **3. Priority**
- `low`: Ưu tiên thấp
- `medium`: Ưu tiên trung bình (mặc định)
- `high`: Ưu tiên cao

### **4. Status**
- `pending`: Chờ xử lý (mặc định)
- `in_progress`: Đang thực hiện
- `completed`: Hoàn thành
- `cancelled`: Đã hủy

---

## 🔄 **Response Format**

### **Thành Công (200)**

```json
{
  "success": true,
  "data": {
    "id": 108,
    "title": "Bài tập môn Lập trình Web",
    "description": "Làm bài tập về React và Node.js",
    "due_date": "2026-02-25T00:00:00.000000Z",
    "deadline": "2026-02-25T23:59:59.000000Z",
    "priority": "high",
    "status": "pending",
    "creator_id": 1,
    "creator_type": "lecturer",
    "created_at": "2025-01-27T10:30:00.000000Z",
    "updated_at": "2025-01-27T10:30:00.000000Z",
    "receivers": [
      {
        "id": 109,
        "task_id": 108,
        "receiver_id": 1,
        "receiver_type": "student",
        "created_at": "2025-01-27T10:30:00.000000Z",
        "updated_at": "2025-01-27T10:30:00.000000Z"
      },
      {
        "id": 110,
        "task_id": 108,
        "receiver_id": 2,
        "receiver_type": "student",
        "created_at": "2025-01-27T10:30:00.000000Z",
        "updated_at": "2025-01-27T10:30:00.000000Z"
      },
      {
        "id": 111,
        "task_id": 108,
        "receiver_id": 1,
        "receiver_type": "classes",
        "created_at": "2025-01-27T10:30:00.000000Z",
        "updated_at": "2025-01-27T10:30:00.000000Z"
      }
    ]
  },
  "message": "Task created successfully"
}
```

### **Lỗi Validation (422)**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "due_date": ["Due date must be today or in the future."],
    "deadline": ["Deadline must be in the future."]
  },
  "error_code": "VALIDATION_ERROR"
}
```

### **Lỗi Server (500)**

```json
{
  "success": false,
  "message": "Failed to create task",
  "error": "Error message details"
}
```

---

## 🎯 **Lưu Ý Quan Trọng**

### **1. Khi Gửi Cho Lớp/Khoa**
- Backend sẽ tự động lấy danh sách sinh viên
- Tạo individual receivers cho từng sinh viên
- Vẫn giữ receiver cho lớp/khoa để tracking

### **2. JWT Token**
- Luôn cần gửi JWT token trong header
- Token chứa thông tin user hiện tại
- Backend sẽ tự động lấy `creator_id` từ token

### **3. Date Format**
- `due_date`: `YYYY-MM-DD`
- `deadline`: `YYYY-MM-DD HH:mm:ss`
- Sử dụng timezone UTC

### **4. Receiver ID**
- Với `classes`: ID của lớp
- Với `department`: ID của khoa
- Với `all_students`/`all_lecturers`: Sử dụng `0`

---

## 🚀 **Quick Start**

1. **Lấy JWT token** từ login API
2. **Chuẩn bị dữ liệu** theo format trên
3. **Gửi POST request** đến `/api/v1/lecturer-tasks`
4. **Kiểm tra response** để xác nhận thành công

**✅ Với logic mới, Frontend chỉ cần chọn lớp → Backend tự động phân phối cho tất cả sinh viên!**

---

## 🎨 **Frontend UI Suggestions**

### **1. Giao Diện Chọn Receivers**

```html
<!-- Multi-select cho Receivers -->
<div class="receivers-section">
  <h3>Chọn người nhận:</h3>
  
  <!-- Tab Navigation -->
  <div class="receiver-tabs">
    <button class="tab-btn active" data-tab="individual">Cá nhân</button>
    <button class="tab-btn" data-tab="group">Nhóm/Lớp</button>
    <button class="tab-btn" data-tab="all">Tất cả</button>
  </div>
  
  <!-- Tab Content -->
  <div class="tab-content">
    <!-- Cá nhân -->
    <div id="individual" class="tab-pane active">
      <div class="form-group">
        <label>Sinh viên:</label>
        <select multiple class="form-control" id="students-select">
          <option value="1">Nguyễn Văn A (SV001)</option>
          <option value="2">Trần Thị B (SV002)</option>
        </select>
      </div>
      <div class="form-group">
        <label>Giảng viên:</label>
        <select multiple class="form-control" id="lecturers-select">
          <option value="1">Thầy Nguyễn Văn C (GV001)</option>
          <option value="2">Cô Trần Thị D (GV002)</option>
        </select>
      </div>
    </div>
    
    <!-- Nhóm/Lớp -->
    <div id="group" class="tab-pane">
      <div class="form-group">
        <label>Lớp học:</label>
        <select multiple class="form-control" id="classes-select">
          <option value="1">Lớp CNTT01</option>
          <option value="2">Lớp CNTT02</option>
        </select>
      </div>
      <div class="form-group">
        <label>Khoa:</label>
        <select multiple class="form-control" id="departments-select">
          <option value="1">Khoa Công nghệ thông tin</option>
          <option value="2">Khoa Kỹ thuật</option>
        </select>
      </div>
    </div>
    
    <!-- Tất cả -->
    <div id="all" class="tab-pane">
      <div class="form-group">
        <label>
          <input type="checkbox" id="all-students"> Tất cả sinh viên
        </label>
      </div>
      <div class="form-group">
        <label>
          <input type="checkbox" id="all-lecturers"> Tất cả giảng viên
        </label>
      </div>
    </div>
  </div>
</div>
```

### **2. JavaScript Logic**

```javascript
// Function để build receivers array
function buildReceiversArray() {
  const receivers = [];
  
  // Cá nhân
  const selectedStudents = Array.from(document.getElementById('students-select').selectedOptions);
  selectedStudents.forEach(option => {
    receivers.push({
      receiver_id: parseInt(option.value),
      receiver_type: 'student'
    });
  });
  
  const selectedLecturers = Array.from(document.getElementById('lecturers-select').selectedOptions);
  selectedLecturers.forEach(option => {
    receivers.push({
      receiver_id: parseInt(option.value),
      receiver_type: 'lecturer'
    });
  });
  
  // Nhóm/Lớp
  const selectedClasses = Array.from(document.getElementById('classes-select').selectedOptions);
  selectedClasses.forEach(option => {
    receivers.push({
      receiver_id: parseInt(option.value),
      receiver_type: 'classes'
    });
  });
  
  const selectedDepartments = Array.from(document.getElementById('departments-select').selectedOptions);
  selectedDepartments.forEach(option => {
    receivers.push({
      receiver_id: parseInt(option.value),
      receiver_type: 'department'
    });
  });
  
  // Tất cả
  if (document.getElementById('all-students').checked) {
    receivers.push({
      receiver_id: 0,
      receiver_type: 'all_students'
    });
  }
  
  if (document.getElementById('all-lecturers').checked) {
    receivers.push({
      receiver_id: 0,
      receiver_type: 'all_lecturers'
    });
  }
  
  return receivers;
}

// Function để tạo task
async function createTask() {
  const taskData = {
    title: document.getElementById('title').value,
    description: document.getElementById('description').value,
    due_date: document.getElementById('due_date').value,
    deadline: document.getElementById('deadline').value,
    priority: document.getElementById('priority').value,
    status: 'pending',
    creator_id: getCurrentUserId(), // Từ JWT token
    creator_type: 'lecturer',
    receivers: buildReceiversArray()
  };
  
  try {
    const response = await fetch('/api/v1/lecturer-tasks', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getJWTToken()}`
      },
      body: JSON.stringify(taskData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Tạo task thành công!');
      console.log('Task created:', result.data);
    } else {
      alert('Lỗi: ' + result.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Có lỗi xảy ra khi tạo task');
  }
}
```

### **3. Preview Receivers**

```javascript
// Function để preview danh sách người nhận
function previewReceivers() {
  const receivers = buildReceiversArray();
  const preview = document.getElementById('receivers-preview');
  
  let html = '<h4>Danh sách người nhận:</h4><ul>';
  
  receivers.forEach(receiver => {
    let label = '';
    switch(receiver.receiver_type) {
      case 'student':
        label = `Sinh viên ID: ${receiver.receiver_id}`;
        break;
      case 'lecturer':
        label = `Giảng viên ID: ${receiver.receiver_id}`;
        break;
      case 'classes':
        label = `Lớp ID: ${receiver.receiver_id} (tất cả sinh viên trong lớp)`;
        break;
      case 'department':
        label = `Khoa ID: ${receiver.receiver_id} (tất cả sinh viên trong khoa)`;
        break;
      case 'all_students':
        label = 'Tất cả sinh viên';
        break;
      case 'all_lecturers':
        label = 'Tất cả giảng viên';
        break;
    }
    html += `<li>${label}</li>`;
  });
  
  html += '</ul>';
  preview.innerHTML = html;
}
```

---

## 🎯 **Tóm Tắt**

### **✅ Backend Hỗ Trợ:**
- **Hỗn hợp receivers** trong cùng 1 task
- **Tự động phân phối** cho lớp/khoa
- **Validation đầy đủ** cho tất cả loại receivers
- **Response chi tiết** với danh sách receivers

### **✅ Frontend Cần:**
- **Multi-select UI** cho receivers
- **Tab navigation** cho các loại receivers
- **Preview function** để xem trước
- **Validation** trước khi gửi

**🎉 Với logic mới, Frontend có thể tạo task linh hoạt cho bất kỳ combination nào!**
