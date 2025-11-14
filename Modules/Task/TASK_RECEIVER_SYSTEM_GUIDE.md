# 🎯 Hệ Thống Người Nhận Task - Hướng Dẫn Chi Tiết

## 📋 Tổng Quan

Hệ thống Task Receiver được thiết kế rất chi tiết và linh hoạt, hỗ trợ 5 loại người nhận khác nhau với logic phức tạp. Frontend cần hiểu rõ để tận dụng tối đa tính năng này.

## 🗄️ Cấu Trúc Database

### **Bảng `task_receivers` (Pivot Table)**
```sql
CREATE TABLE task_receivers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT NOT NULL,           -- ID của task
    receiver_id BIGINT NOT NULL,       -- ID của người nhận
    receiver_type VARCHAR(50) NOT NULL, -- Loại người nhận
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_task_receivers (task_id, receiver_type),
    INDEX idx_receiver (receiver_id, receiver_type),
    FOREIGN KEY (task_id) REFERENCES task(id) ON DELETE CASCADE
);
```

### **Các Loại Receiver Được Hỗ Trợ**
```php
const ALLOWED_RECEIVER_TYPES = [
    'student',        // Sinh viên cụ thể
    'lecturer',      // Giảng viên cụ thể  
    'class',         // Cả lớp học
    'all_students',  // Tất cả sinh viên
    'all_lecturers'  // Tất cả giảng viên
];
```

## 🔧 Logic Hoạt Động Chi Tiết

### **1. `student` - Sinh Viên Cụ Thể**
```json
{
    "receiver_id": 123,
    "receiver_type": "student"
}
```

**Đặc điểm:**
- ✅ Task được giao cho **1 sinh viên cụ thể**
- ✅ Hiển thị: Tên sinh viên cụ thể
- ✅ API trả về: Thông tin chi tiết sinh viên
- ✅ Logic: Direct assignment

**Frontend hiển thị:**
```
👤 Nguyễn Văn A (nguyenvana@email.com)
```

### **2. `lecturer` - Giảng Viên Cụ Thể**
```json
{
    "receiver_id": 456,
    "receiver_type": "lecturer"
}
```

**Đặc điểm:**
- ✅ Task được giao cho **1 giảng viên cụ thể**
- ✅ Hiển thị: Tên giảng viên cụ thể
- ✅ API trả về: Thông tin chi tiết giảng viên
- ✅ Logic: Direct assignment

**Frontend hiển thị:**
```
👨‍🏫 TS. Trần Thị B (tranthib@email.com)
```

### **3. `class` - Cả Lớp Học**
```json
{
    "receiver_id": 789,
    "receiver_type": "class"
}
```

**Đặc điểm:**
- ✅ Task được giao cho **TẤT CẢ sinh viên trong lớp**
- ✅ Hiển thị: Tên lớp + số lượng sinh viên
- ✅ API trả về: Danh sách tất cả sinh viên trong lớp
- ✅ Logic: Tự động lấy students có `class_id = 789`

**Frontend hiển thị:**
```
🏫 Lớp CNTT01 (25 sinh viên)
```

### **4. `all_students` - Tất Cả Sinh Viên**
```json
{
    "receiver_id": 0,
    "receiver_type": "all_students"
}
```

**Đặc điểm:**
- ✅ Task được giao cho **TẤT CẢ sinh viên trong hệ thống**
- ✅ Hiển thị: Tổng số sinh viên
- ✅ API trả về: Danh sách tất cả sinh viên
- ✅ Logic: `receiver_id = 0` = toàn hệ thống

**Frontend hiển thị:**
```
👥 Tất cả sinh viên (1,500 sinh viên)
```

### **5. `all_lecturers` - Tất Cả Giảng Viên**
```json
{
    "receiver_id": 0,
    "receiver_type": "all_lecturers"
}
```

**Đặc điểm:**
- ✅ Task được giao cho **TẤT CẢ giảng viên trong hệ thống**
- ✅ Hiển thị: Tổng số giảng viên
- ✅ API trả về: Danh sách tất cả giảng viên
- ✅ Logic: `receiver_id = 0` = toàn hệ thống

**Frontend hiển thị:**
```
👨‍🏫 Tất cả giảng viên (50 giảng viên)
```

## 🎨 Frontend Implementation

### **1. TypeScript Interfaces**

```typescript
interface TaskReceiver {
  id: number;
  task_id: number;
  receiver_id: number;
  receiver_type: 'student' | 'lecturer' | 'class' | 'all_students' | 'all_lecturers';
  created_at: string;
  updated_at: string;
  
  // Relationships (khi được load)
  student?: {
    id: number;
    name: string;
    email: string;
  };
  lecturer?: {
    id: number;
    name: string;
    email: string;
  };
  classroom?: {
    id: number;
    name: string;
  };
}

interface ReceiverDisplayInfo {
  receiver_id: number;
  receiver_type: string;
  display_name: string;
  count: number;
  icon: string;
  color: string;
}
```

### **2. UI Components**

#### **A. Receiver Selection Component**
```jsx
interface ReceiverSelectorProps {
  onReceiversChange: (receivers: TaskReceiver[]) => void;
  initialReceivers?: TaskReceiver[];
}

const ReceiverSelector: React.FC<ReceiverSelectorProps> = ({
  onReceiversChange,
  initialReceivers = []
}) => {
  const [activeTab, setActiveTab] = useState<'individual' | 'class' | 'all'>('individual');
  const [selectedStudents, setSelectedStudents] = useState<number[]>([]);
  const [selectedLecturers, setSelectedLecturers] = useState<number[]>([]);
  const [selectedClasses, setSelectedClasses] = useState<number[]>([]);
  const [allStudents, setAllStudents] = useState(false);
  const [allLecturers, setAllLecturers] = useState(false);

  return (
    <div className="receiver-selector">
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="individual">Cá nhân</TabsTrigger>
          <TabsTrigger value="class">Theo lớp</TabsTrigger>
          <TabsTrigger value="all">Tất cả</TabsTrigger>
        </TabsList>
        
        <TabsContent value="individual">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label>Sinh viên</Label>
              <StudentMultiSelect
                selected={selectedStudents}
                onSelectionChange={setSelectedStudents}
              />
            </div>
            <div>
              <Label>Giảng viên</Label>
              <LecturerMultiSelect
                selected={selectedLecturers}
                onSelectionChange={setSelectedLecturers}
              />
            </div>
          </div>
        </TabsContent>
        
        <TabsContent value="class">
          <Label>Chọn lớp</Label>
          <ClassMultiSelect
            selected={selectedClasses}
            onSelectionChange={setSelectedClasses}
          />
        </TabsContent>
        
        <TabsContent value="all">
          <div className="space-y-4">
            <div className="flex items-center space-x-2">
              <Checkbox
                id="all-students"
                checked={allStudents}
                onCheckedChange={setAllStudents}
              />
              <Label htmlFor="all-students">Tất cả sinh viên</Label>
            </div>
            <div className="flex items-center space-x-2">
              <Checkbox
                id="all-lecturers"
                checked={allLecturers}
                onCheckedChange={setAllLecturers}
              />
              <Label htmlFor="all-lecturers">Tất cả giảng viên</Label>
            </div>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
};
```

#### **B. Receiver Display Component**
```jsx
interface ReceiverChipProps {
  receiver: TaskReceiver;
  onRemove: () => void;
  readonly?: boolean;
}

const ReceiverChip: React.FC<ReceiverChipProps> = ({
  receiver,
  onRemove,
  readonly = false
}) => {
  const displayInfo = getReceiverDisplayInfo(receiver);
  
  return (
    <div className={`inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm ${displayInfo.color}`}>
      <span className="text-lg">{displayInfo.icon}</span>
      <span>{displayInfo.display_name}</span>
      {!readonly && (
        <button
          onClick={onRemove}
          className="ml-1 hover:bg-black/10 rounded-full p-0.5"
        >
          <X className="h-3 w-3" />
        </button>
      )}
    </div>
  );
};

const getReceiverDisplayInfo = (receiver: TaskReceiver): ReceiverDisplayInfo => {
  switch (receiver.receiver_type) {
    case 'student':
      return {
        receiver_id: receiver.receiver_id,
        receiver_type: 'student',
        display_name: receiver.student?.name || `Sinh viên #${receiver.receiver_id}`,
        count: 1,
        icon: '👤',
        color: 'bg-blue-100 text-blue-800'
      };
      
    case 'lecturer':
      return {
        receiver_id: receiver.receiver_id,
        receiver_type: 'lecturer',
        display_name: receiver.lecturer?.name || `Giảng viên #${receiver.receiver_id}`,
        count: 1,
        icon: '👨‍🏫',
        color: 'bg-green-100 text-green-800'
      };
      
    case 'class':
      return {
        receiver_id: receiver.receiver_id,
        receiver_type: 'class',
        display_name: receiver.classroom?.name || `Lớp #${receiver.receiver_id}`,
        count: 0, // Sẽ được tính từ API
        icon: '🏫',
        color: 'bg-purple-100 text-purple-800'
      };
      
    case 'all_students':
      return {
        receiver_id: 0,
        receiver_type: 'all_students',
        display_name: 'Tất cả sinh viên',
        count: 0, // Sẽ được tính từ API
        icon: '👥',
        color: 'bg-orange-100 text-orange-800'
      };
      
    case 'all_lecturers':
      return {
        receiver_id: 0,
        receiver_type: 'all_lecturers',
        display_name: 'Tất cả giảng viên',
        count: 0, // Sẽ được tính từ API
        icon: '👨‍🏫',
        color: 'bg-red-100 text-red-800'
      };
      
    default:
      return {
        receiver_id: receiver.receiver_id,
        receiver_type: receiver.receiver_type,
        display_name: 'Unknown',
        count: 0,
        icon: '❓',
        color: 'bg-gray-100 text-gray-800'
      };
  }
};
```

#### **C. Receiver List Component**
```jsx
interface ReceiverListProps {
  receivers: TaskReceiver[];
  onRemoveReceiver: (receiverId: number, receiverType: string) => void;
  readonly?: boolean;
}

const ReceiverList: React.FC<ReceiverListProps> = ({
  receivers,
  onRemoveReceiver,
  readonly = false
}) => {
  return (
    <div className="flex flex-wrap gap-2">
      {receivers.map((receiver) => (
        <ReceiverChip
          key={`${receiver.receiver_type}-${receiver.receiver_id}`}
          receiver={receiver}
          onRemove={() => onRemoveReceiver(receiver.receiver_id, receiver.receiver_type)}
          readonly={readonly}
        />
      ))}
    </div>
  );
};
```

## 📡 API Endpoints

### **1. Lấy Danh Sách Receivers của Task**
```http
GET /api/v1/tasks/{taskId}/receivers
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "task_id": 100,
      "receiver_id": 123,
      "receiver_type": "student",
      "created_at": "2025-01-27 10:00:00",
      "updated_at": "2025-01-27 10:00:00",
      "student": {
        "id": 123,
        "name": "Nguyễn Văn A",
        "email": "nguyenvana@email.com"
      }
    },
    {
      "id": 2,
      "task_id": 100,
      "receiver_id": 789,
      "receiver_type": "class",
      "created_at": "2025-01-27 10:00:00",
      "updated_at": "2025-01-27 10:00:00",
      "classroom": {
        "id": 789,
        "name": "CNTT01"
      }
    }
  ],
  "message": "Task receivers retrieved successfully"
}
```

### **2. Thêm Receiver cho Task**
```http
POST /api/v1/tasks/{taskId}/receivers
Authorization: Bearer {token}
Content-Type: application/json

{
  "receiver_id": 123,
  "receiver_type": "student"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "task_id": 100,
    "receiver_id": 123,
    "receiver_type": "student",
    "created_at": "2025-01-27 10:00:00",
    "updated_at": "2025-01-27 10:00:00"
  },
  "message": "Receiver added successfully"
}
```

### **3. Thêm Nhiều Receivers (Bulk)**
```http
POST /api/v1/tasks/{taskId}/receivers/bulk
Authorization: Bearer {token}
Content-Type: application/json

{
  "receivers": [
    {
      "receiver_id": 123,
      "receiver_type": "student"
    },
    {
      "receiver_id": 456,
      "receiver_type": "lecturer"
    },
    {
      "receiver_id": 789,
      "receiver_type": "class"
    }
  ]
}
```

### **4. Xóa Receiver khỏi Task**
```http
DELETE /api/v1/tasks/{taskId}/receivers/{receiverId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Receiver removed successfully"
}
```

### **5. Lấy Số Lượng Receivers Thực Tế**
```http
GET /api/v1/tasks/{taskId}/receivers/count
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_students": 25,
    "total_lecturers": 2,
    "breakdown": [
      {
        "receiver_id": 123,
        "receiver_type": "student",
        "count": 1
      },
      {
        "receiver_id": 789,
        "receiver_type": "class",
        "count": 25
      },
      {
        "receiver_id": 0,
        "receiver_type": "all_students",
        "count": 1500
      }
    ]
  }
}
```

## 🔍 Logic Kiểm Tra Quyền

### **Kiểm Tra User Có Nhận Task Không**
```typescript
interface UserTaskPermission {
  canView: boolean;
  canEdit: boolean;
  canSubmit: boolean;
  canDelete: boolean;
  reason?: string;
}

const checkUserTaskPermission = async (
  taskId: number,
  userId: number,
  userType: 'student' | 'lecturer'
): Promise<UserTaskPermission> => {
  const response = await api.get(`/tasks/${taskId}/permissions`, {
    params: { user_id: userId, user_type: userType }
  });
  
  return response.data;
};
```

**Backend Logic:**
```php
// Trong TaskBusinessLogicService
public function isUserTaskReceiver(Task $task, int $userId, string $userType): bool
{
    // 1. Kiểm tra direct receiver
    $isDirectReceiver = $task->receivers()
        ->where('receiver_id', $userId)
        ->where('receiver_type', $userType)
        ->exists();

    if ($isDirectReceiver) {
        return true;
    }
    
    // 2. Nếu là student, kiểm tra class và all_students
    if ($userType === 'student') {
        $student = Student::find($userId);
        if ($student) {
            // Kiểm tra class receiver
            $isClassReceiver = $task->receivers()
                ->where('receiver_type', 'class')
                ->where('receiver_id', $student->class_id)
                ->exists();
                
            // Kiểm tra all_students receiver
            $isAllStudentsReceiver = $task->receivers()
                ->where('receiver_type', 'all_students')
                ->exists();
                
            return $isClassReceiver || $isAllStudentsReceiver;
        }
    }
    
    return false;
}
```

## 💡 Tận Dụng Tối Đa Cho Frontend

### **1. Bulk Operations**
```typescript
// Giao task cho nhiều lớp cùng lúc
const bulkAssignToClasses = async (taskId: number, classIds: number[]) => {
  const receivers = classIds.map(classId => ({
    receiver_id: classId,
    receiver_type: 'class'
  }));
  
  await api.post(`/tasks/${taskId}/receivers/bulk`, { receivers });
};

// Giao task cho tất cả sinh viên trong khoa
const assignToAllStudentsInFaculty = async (taskId: number, facultyId: number) => {
  await api.post(`/tasks/${taskId}/receivers`, {
    receiver_id: facultyId,
    receiver_type: 'all_students'
  });
};
```

### **2. Smart Filtering**
```typescript
// Lọc tasks theo receiver type
const getTasksByReceiverType = (receiverType: string) => {
  return api.get(`/tasks?receiver_type=${receiverType}`);
};

// Lọc tasks theo class
const getTasksByClass = (classId: number) => {
  return api.get(`/tasks?class_id=${classId}`);
};

// Lọc tasks theo student
const getTasksByStudent = (studentId: number) => {
  return api.get(`/tasks?student_id=${studentId}`);
};
```

### **3. Real-time Updates**
```typescript
// Khi có sinh viên mới vào lớp, tự động nhận tasks của lớp
const handleNewStudentInClass = (studentId: number, classId: number) => {
  // Tự động assign tasks có receiver_type = 'class' và receiver_id = classId
  socket.emit('student_joined_class', { studentId, classId });
};

// Khi có giảng viên mới, tự động nhận tasks của all_lecturers
const handleNewLecturer = (lecturerId: number) => {
  socket.emit('lecturer_joined', { lecturerId });
};
```

### **4. Performance Optimization**
```typescript
// Lazy loading cho danh sách receivers lớn
const useTaskReceivers = (taskId: number) => {
  const [receivers, setReceivers] = useState<TaskReceiver[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  const loadReceivers = useCallback(async () => {
    if (!hasMore) return;
    
    setLoading(true);
    try {
      const response = await api.get(`/tasks/${taskId}/receivers`, {
        params: { page, per_page: 20 }
      });
      
      const newReceivers = response.data.data;
      setReceivers(prev => [...prev, ...newReceivers]);
      setHasMore(newReceivers.length === 20);
      setPage(prev => prev + 1);
    } catch (error) {
      console.error('Error loading receivers:', error);
    } finally {
      setLoading(false);
    }
  }, [taskId, page, hasMore]);

  return { receivers, loading, loadReceivers, hasMore };
};
```

### **5. Advanced UI Features**
```typescript
// Drag & Drop để sắp xếp receivers
const DraggableReceiverList = ({ receivers, onReorder }) => {
  return (
    <DragDropContext onDragEnd={onReorder}>
      <Droppable droppableId="receivers">
        {(provided) => (
          <div {...provided.droppableProps} ref={provided.innerRef}>
            {receivers.map((receiver, index) => (
              <Draggable key={receiver.id} draggableId={receiver.id.toString()} index={index}>
                {(provided) => (
                  <div
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                    {...provided.dragHandleProps}
                  >
                    <ReceiverChip receiver={receiver} />
                  </div>
                )}
              </Draggable>
            ))}
            {provided.placeholder}
          </div>
        )}
      </Droppable>
    </DragDropContext>
  );
};

// Search và filter receivers
const ReceiverSearch = ({ onSearch }) => {
  const [query, setQuery] = useState('');
  const [filters, setFilters] = useState({
    type: 'all',
    status: 'all'
  });

  return (
    <div className="space-y-4">
      <Input
        placeholder="Tìm kiếm receivers..."
        value={query}
        onChange={(e) => setQuery(e.target.value)}
      />
      <div className="flex gap-2">
        <Select value={filters.type} onValueChange={(value) => setFilters(prev => ({ ...prev, type: value }))}>
          <SelectTrigger>
            <SelectValue placeholder="Loại receiver" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Tất cả</SelectItem>
            <SelectItem value="student">Sinh viên</SelectItem>
            <SelectItem value="lecturer">Giảng viên</SelectItem>
            <SelectItem value="class">Lớp</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>
  );
};
```

## 🎯 Best Practices

### **1. Error Handling**
```typescript
const handleReceiverError = (error: any) => {
  if (error.response?.status === 403) {
    toast.error('Bạn không có quyền thêm receiver này');
  } else if (error.response?.status === 422) {
    toast.error('Dữ liệu receiver không hợp lệ');
  } else {
    toast.error('Có lỗi xảy ra khi thêm receiver');
  }
};
```

### **2. Validation**
```typescript
const validateReceiver = (receiver: Partial<TaskReceiver>): string[] => {
  const errors: string[] = [];
  
  if (!receiver.receiver_id && receiver.receiver_type !== 'all_students' && receiver.receiver_type !== 'all_lecturers') {
    errors.push('Receiver ID là bắt buộc');
  }
  
  if (!receiver.receiver_type || !ALLOWED_RECEIVER_TYPES.includes(receiver.receiver_type)) {
    errors.push('Loại receiver không hợp lệ');
  }
  
  return errors;
};
```

### **3. Caching**
```typescript
const useCachedReceivers = (taskId: number) => {
  const queryKey = ['task-receivers', taskId];
  
  return useQuery({
    queryKey,
    queryFn: () => api.get(`/tasks/${taskId}/receivers`).then(res => res.data),
    staleTime: 5 * 60 * 1000, // 5 minutes
    cacheTime: 10 * 60 * 1000, // 10 minutes
  });
};
```

## 🚀 Kết Luận

Hệ thống Task Receiver của bạn rất mạnh mẽ và linh hoạt! Frontend cần:

1. **Hiểu rõ 5 loại receiver** và cách hiển thị
2. **Tạo UI components** để quản lý receivers hiệu quả
3. **Implement bulk operations** cho hiệu suất cao
4. **Xử lý real-time updates** khi có thay đổi
5. **Tối ưu performance** khi hiển thị số lượng lớn receivers
6. **Implement proper error handling** và validation
7. **Sử dụng caching** để tối ưu trải nghiệm người dùng

Với hệ thống này, bạn có thể tạo ra một giao diện quản lý task rất mạnh mẽ và linh hoạt!

---

**📝 Tài liệu được tạo ngày: 2025-01-27**  
**🔄 Cập nhật lần cuối: 2025-01-27**  
**👨‍💻 Tác giả: System Management Team**
