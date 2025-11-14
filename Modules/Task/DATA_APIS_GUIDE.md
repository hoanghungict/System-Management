# 📊 API Lấy Dữ Liệu Lớp, Sinh Viên, Giảng Viên - Hướng Dẫn Chi Tiết

## 🎯 Tổng Quan

Hệ thống cung cấp các API để lấy dữ liệu về lớp học, sinh viên, giảng viên phục vụ cho việc tạo task và quản lý người nhận. Các API được phân chia theo module và quyền truy cập.

## 🏗️ Cấu Trúc API

### **Module Auth (Quản lý người dùng)**
- **Base URL**: `/api/v1/`
- **Authentication**: JWT Required
- **Permissions**: Admin, Lecturer, Student

### **Module Task (Tích hợp với Task)**
- **Base URL**: `/api/v1/`
- **Authentication**: JWT Required
- **Permissions**: Tất cả user đã đăng nhập

## 📡 API Endpoints Chi Tiết

### **1. 🏫 API LẤY DỮ LIỆU LỚP HỌC**

#### **A. Lấy tất cả lớp học (Admin only) - ✅ HOẠT ĐỘNG**
```http
GET /api/v1/classes
Authorization: Bearer {token}
```

**Response thực tế:**
```json
[
  {
    "id": 1,
    "class_name": "Lớp CNTT K65",
    "class_code": "CNTT65",
    "department_id": 1,
    "lecturer_id": null,
    "school_year": "2024-2025",
    "department": {
      "id": 1,
      "name": "Khoa Công nghệ Thông tin",
      "type": "faculty"
    },
    "lecturer": null,
    "students_count": 2,
    "students": [
      {
        "id": 1,
        "full_name": "Sinh Viên Mẫu",
        "student_code": "SV001"
      },
      {
        "id": 2,
        "full_name": "Trần Thị Hoa",
        "student_code": "SV002"
      }
    ],
    "created_at": null,
    "updated_at": null
  }
]
```

#### **B. Lấy lớp học theo khoa**
```http
GET /api/v1/classes/faculty/{facultyId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "CNTT01",
      "code": "CNTT01",
      "faculty_id": 1,
      "student_count": 25
    }
  ],
  "message": "Classes by faculty retrieved successfully"
}
```

#### **C. Lấy lớp học theo giảng viên**
```http
GET /api/v1/classes/lecturer/{lecturerId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "CNTT01",
      "code": "CNTT01",
      "lecturer_id": 5,
      "student_count": 25
    }
  ],
  "message": "Classes by lecturer retrieved successfully"
}
```

#### **D. Lấy chi tiết lớp học**
```http
GET /api/v1/classes/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "CNTT01",
    "code": "CNTT01",
    "faculty_id": 1,
    "faculty_name": "Công nghệ thông tin",
    "lecturer_id": 5,
    "lecturer_name": "TS. Nguyễn Văn A",
    "student_count": 25,
    "students": [
      {
        "id": 1,
        "name": "Nguyễn Văn B",
        "email": "nguyenvanb@email.com",
        "student_code": "SV001"
      }
    ],
    "created_at": "2025-01-27T10:00:00.000000Z",
    "updated_at": "2025-01-27T10:00:00.000000Z"
  },
  "message": "Class details retrieved successfully"
}
```

### **2. 👨‍🎓 API LẤY DỮ LIỆU SINH VIÊN**

#### **A. Lấy tất cả sinh viên (Admin only) - ⚠️ CÓ VẤN ĐỀ**
```http
GET /api/v1/students
Authorization: Bearer {token}
```

**⚠️ LƯU Ý:** API này hiện tại trả về **500 Internal Server Error** (HTML error page)

**Thay thế bằng:**
- Sử dụng `/api/v1/classes/{classId}` để lấy sinh viên từ lớp
- Sử dụng `/api/v1/roll-calls/all-students` (nếu là lecturer)

#### **B. Lấy sinh viên theo lớp - ✅ HOẠT ĐỘNG**
```http
GET /api/v1/classes/{classId}
Authorization: Bearer {token}
```

**Response thực tế:**
```json
[
  {
    "id": 1,
    "class_name": "Lớp CNTT K65",
    "class_code": "CNTT65",
    "department_id": 1,
    "lecturer_id": null,
    "school_year": "2024-2025",
    "department": {
      "id": 1,
      "name": "Khoa Công nghệ Thông tin",
      "type": "faculty"
    },
    "lecturer": null,
    "students_count": 2,
    "students": [
      {
        "id": 1,
        "full_name": "Sinh Viên Mẫu",
        "student_code": "SV001"
      },
      {
        "id": 2,
        "full_name": "Trần Thị Hoa",
        "student_code": "SV002"
      }
    ],
    "created_at": null,
    "updated_at": null
  }
]
```

#### **C. Lấy sinh viên cho điểm danh (Lecturer only)**
```http
GET /api/v1/roll-calls/students/class/{classId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Nguyễn Văn A",
      "email": "nguyenvana@email.com",
      "student_code": "SV001",
      "class_id": 1,
      "attendance_status": "present"
    }
  ],
  "message": "Students for roll call retrieved successfully"
}
```

#### **D. Lấy tất cả sinh viên cho điểm danh (Lecturer only)**
```http
GET /api/v1/roll-calls/all-students
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Nguyễn Văn A",
      "email": "nguyenvana@email.com",
      "student_code": "SV001",
      "class_id": 1,
      "class_name": "CNTT01"
    }
  ],
  "message": "All students retrieved successfully"
}
```

### **3. 👨‍🏫 API LẤY DỮ LIỆU GIẢNG VIÊN**

#### **A. Lấy tất cả giảng viên (Admin only) - ✅ HOẠT ĐỘNG**
```http
GET /api/v1/lecturers
Authorization: Bearer {token}
```

**Response thực tế:**
```json
[
  {
    "id": 1,
    "email": "admin@system.com",
    "full_name": "Admin System",
    "phone": "0123456789",
    "address": null,
    "user_type": "lecturer",
    "account": {
      "username": "admin",
      "is_admin": true
    },
    "lecturer_info": {
      "lecturer_code": "GV001",
      "gender": null,
      "unit": null
    }
  },
  {
    "id": 3,
    "email": "nguyen.van.an@university.edu.vn",
    "full_name": "Nguyễn Văn An",
    "phone": "0901234567",
    "address": "123 Đường ABC, Quận 1, TP.HCM",
    "user_type": "lecturer",
    "account": {
      "username": "gv002",
      "is_admin": false
    },
    "lecturer_info": {
      "lecturer_code": "GV002",
      "gender": "male",
      "unit": null
    }
  }
]
```

#### **B. Lấy chi tiết giảng viên**
```http
GET /api/v1/lecturers/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "TS. Nguyễn Văn A",
    "email": "nguyenvana@email.com",
    "lecturer_code": "GV001",
    "faculty_id": 1,
    "faculty_name": "Công nghệ thông tin",
    "is_admin": true,
    "phone": "0123456789",
    "address": "Hà Nội",
    "classes": [
      {
        "id": 1,
        "name": "CNTT01",
        "student_count": 25
      }
    ],
    "created_at": "2025-01-27T10:00:00.000000Z",
    "updated_at": "2025-01-27T10:00:00.000000Z"
  },
  "message": "Lecturer details retrieved successfully"
}
```

### **4. 🏢 API LẤY DỮ LIỆU KHOA/PHÒNG BAN**

#### **A. Lấy tất cả khoa/phòng ban (Admin only) - ✅ HOẠT ĐỘNG**
```http
GET /api/v1/departments
Authorization: Bearer {token}
```

**Response thực tế:**
```json
[
  {
    "id": 1,
    "name": "Khoa Công nghệ Thông tin",
    "type": "faculty",
    "parent_id": null,
    "staff_count": 2,
    "classes_count": 1,
    "parent": null,
    "created_at": null,
    "updated_at": null
  },
  {
    "id": 2,
    "name": "Khoa Công nghệ Thông tin",
    "type": "faculty",
    "parent_id": null,
    "staff_count": 0,
    "classes_count": 0,
    "parent": null,
    "created_at": null,
    "updated_at": null
  },
  {
    "id": 3,
    "name": "Khoa Công nghệ Thông tin",
    "type": "faculty",
    "parent_id": null,
    "staff_count": 0,
    "classes_count": 0,
    "parent": null,
    "created_at": null,
    "updated_at": null
  }
]
```

#### **B. Lấy cây phân cấp khoa/phòng ban**
```http
GET /api/v1/departments/tree
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Công nghệ thông tin",
      "code": "CNTT",
      "level": 1,
      "children": [
        {
          "id": 2,
          "name": "Khoa học máy tính",
          "code": "KHM",
          "level": 2,
          "children": []
        }
      ]
    }
  ],
  "message": "Department tree retrieved successfully"
}
```

### **5. 🔗 API TÍCH HỢP VỚI TASK MODULE**

#### **A. Lấy khoa/phòng ban (cho Task)**
```http
GET /api/v1/departments
Authorization: Bearer {token}
```

**Response:** Tương tự như API Auth Module

#### **B. Lấy lớp theo khoa (cho Task)**
```http
GET /api/v1/classes/by-department?department_id={departmentId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "CNTT01",
      "code": "CNTT01",
      "department_id": 1,
      "department_name": "Công nghệ thông tin",
      "student_count": 25
    }
  ],
  "message": "Classes by department retrieved successfully"
}
```

#### **C. Lấy sinh viên theo lớp (cho Task)**
```http
GET /api/v1/students/by-class?class_id={classId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Nguyễn Văn A",
      "email": "nguyenvana@email.com",
      "student_code": "SV001",
      "class_id": 1,
      "class_name": "CNTT01"
    }
  ],
  "message": "Students by class retrieved successfully"
}
```

#### **D. Lấy giảng viên (cho Task)**
```http
GET /api/v1/lecturers
Authorization: Bearer {token}
```

**Response:** Tương tự như API Auth Module

## 🔐 Phân Quyền Truy Cập

### **Admin (Quản trị viên)**
- ✅ Truy cập tất cả API
- ✅ Quản lý sinh viên, giảng viên, lớp học, khoa
- ✅ Xem thống kê tổng quan

### **Lecturer (Giảng viên)**
- ✅ Xem thông tin cá nhân
- ✅ Xem lớp học được phân công
- ✅ Xem sinh viên trong lớp
- ✅ Quản lý điểm danh
- ❌ Không thể quản lý hệ thống

### **Student (Sinh viên)**
- ✅ Xem thông tin cá nhân
- ✅ Xem lớp học của mình
- ❌ Không thể xem thông tin người khác

## 💡 Frontend Implementation

### **1. TypeScript Interfaces**

```typescript
interface Class {
  id: number;
  name: string;
  code: string;
  faculty_id: number;
  faculty_name: string;
  lecturer_id?: number;
  lecturer_name?: string;
  student_count: number;
  created_at: string;
  updated_at: string;
}

interface Student {
  id: number;
  name: string;
  email: string;
  student_code: string;
  class_id: number;
  class_name: string;
  faculty_id: number;
  faculty_name: string;
  phone?: string;
  address?: string;
  created_at: string;
  updated_at: string;
}

interface Lecturer {
  id: number;
  name: string;
  email: string;
  lecturer_code: string;
  faculty_id: number;
  faculty_name: string;
  is_admin: boolean;
  phone?: string;
  address?: string;
  classes_count: number;
  created_at: string;
  updated_at: string;
}

interface Department {
  id: number;
  name: string;
  code: string;
  parent_id?: number;
  parent_name?: string;
  level: number;
  classes_count: number;
  students_count: number;
  lecturers_count: number;
  created_at: string;
  updated_at: string;
}
```

### **2. API Service Functions**

```typescript
class DataApiService {
  private baseURL = '/api/v1';
  
  // Classes
  async getClasses(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    faculty_id?: number;
  }) {
    return api.get(`${this.baseURL}/classes`, { params });
  }
  
  async getClassesByFaculty(facultyId: number) {
    return api.get(`${this.baseURL}/classes/faculty/${facultyId}`);
  }
  
  async getClassesByLecturer(lecturerId: number) {
    return api.get(`${this.baseURL}/classes/lecturer/${lecturerId}`);
  }
  
  async getClassDetails(classId: number) {
    return api.get(`${this.baseURL}/classes/${classId}`);
  }
  
  // Students
  async getStudents(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    class_id?: number;
    faculty_id?: number;
  }) {
    return api.get(`${this.baseURL}/students`, { params });
  }
  
  async getStudentsByClass(classId: number) {
    return api.get(`${this.baseURL}/student/class/${classId}`);
  }
  
  async getAllStudentsForRollCall() {
    return api.get(`${this.baseURL}/roll-calls/all-students`);
  }
  
  // Lecturers
  async getLecturers(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    faculty_id?: number;
    is_admin?: boolean;
  }) {
    return api.get(`${this.baseURL}/lecturers`, { params });
  }
  
  async getLecturerDetails(lecturerId: number) {
    return api.get(`${this.baseURL}/lecturers/${lecturerId}`);
  }
  
  // Departments
  async getDepartments() {
    return api.get(`${this.baseURL}/departments`);
  }
  
  async getDepartmentTree() {
    return api.get(`${this.baseURL}/departments/tree`);
  }
  
  // Task Integration
  async getClassesByDepartment(departmentId: number) {
    return api.get(`${this.baseURL}/classes/by-department`, {
      params: { department_id: departmentId }
    });
  }
  
  async getStudentsByClassForTask(classId: number) {
    return api.get(`${this.baseURL}/students/by-class`, {
      params: { class_id: classId }
    });
  }
}
```

### **3. React Hooks**

```typescript
// Hook để lấy danh sách lớp học
const useClasses = (params?: ClassParams) => {
  return useQuery({
    queryKey: ['classes', params],
    queryFn: () => dataApiService.getClasses(params),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

// Hook để lấy sinh viên theo lớp
const useStudentsByClass = (classId: number) => {
  return useQuery({
    queryKey: ['students', 'class', classId],
    queryFn: () => dataApiService.getStudentsByClass(classId),
    enabled: !!classId,
    staleTime: 5 * 60 * 1000,
  });
};

// Hook để lấy giảng viên
const useLecturers = (params?: LecturerParams) => {
  return useQuery({
    queryKey: ['lecturers', params],
    queryFn: () => dataApiService.getLecturers(params),
    staleTime: 10 * 60 * 1000, // 10 minutes
  });
};

// Hook để lấy khoa/phòng ban
const useDepartments = () => {
  return useQuery({
    queryKey: ['departments'],
    queryFn: () => dataApiService.getDepartments(),
    staleTime: 30 * 60 * 1000, // 30 minutes
  });
};
```

### **4. UI Components**

```jsx
// Component chọn lớp học
const ClassSelector = ({ onClassSelect, selectedClassId }) => {
  const { data: classes, loading } = useClasses();
  
  return (
    <Select value={selectedClassId} onValueChange={onClassSelect}>
      <SelectTrigger>
        <SelectValue placeholder="Chọn lớp học" />
      </SelectTrigger>
      <SelectContent>
        {classes?.data?.map((classItem) => (
          <SelectItem key={classItem.id} value={classItem.id.toString()}>
            {classItem.name} ({classItem.student_count} sinh viên)
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
};

// Component chọn sinh viên
const StudentMultiSelect = ({ selectedStudents, onSelectionChange }) => {
  const [classId, setClassId] = useState(null);
  const { data: students, loading } = useStudentsByClass(classId);
  
  return (
    <div className="space-y-4">
      <ClassSelector onClassSelect={setClassId} />
      {students && (
        <MultiSelect
          options={students.data.map(student => ({
            value: student.id,
            label: `${student.name} (${student.student_code})`
          }))}
          selected={selectedStudents}
          onSelectionChange={onSelectionChange}
        />
      )}
    </div>
  );
};

// Component chọn giảng viên
const LecturerSelector = ({ onLecturerSelect, selectedLecturerId }) => {
  const { data: lecturers, loading } = useLecturers();
  
  return (
    <Select value={selectedLecturerId} onValueChange={onLecturerSelect}>
      <SelectTrigger>
        <SelectValue placeholder="Chọn giảng viên" />
      </SelectTrigger>
      <SelectContent>
        {lecturers?.data?.map((lecturer) => (
          <SelectItem key={lecturer.id} value={lecturer.id.toString()}>
            {lecturer.name} {lecturer.is_admin && '(Admin)'}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
};
```

## 🚀 Best Practices

### **1. Caching Strategy**
```typescript
// Sử dụng React Query với stale time phù hợp
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      cacheTime: 10 * 60 * 1000, // 10 minutes
    },
  },
});
```

### **2. Error Handling**
```typescript
const useClassesWithErrorHandling = (params?: ClassParams) => {
  return useQuery({
    queryKey: ['classes', params],
    queryFn: () => dataApiService.getClasses(params),
    onError: (error) => {
      console.error('Error fetching classes:', error);
      toast.error('Không thể tải danh sách lớp học');
    },
  });
};
```

### **3. Loading States**
```jsx
const ClassList = () => {
  const { data: classes, loading, error } = useClasses();
  
  if (loading) return <LoadingSpinner />;
  if (error) return <ErrorMessage error={error} />;
  
  return (
    <div className="grid gap-4">
      {classes?.data?.map((classItem) => (
        <ClassCard key={classItem.id} classItem={classItem} />
      ))}
    </div>
  );
};
```

### **4. Search và Filter**
```typescript
const useClassesWithSearch = () => {
  const [search, setSearch] = useState('');
  const [facultyFilter, setFacultyFilter] = useState<number | null>(null);
  
  const { data: classes, loading } = useClasses({
    search: search || undefined,
    faculty_id: facultyFilter || undefined,
  });
  
  return {
    classes,
    loading,
    search,
    setSearch,
    facultyFilter,
    setFacultyFilter,
  };
};
```

## 🎯 Kết Luận

Hệ thống cung cấp đầy đủ API để lấy dữ liệu lớp học, sinh viên, giảng viên với:

1. **Phân quyền rõ ràng** - Admin, Lecturer, Student
2. **API đa dạng** - CRUD operations, filtering, searching
3. **Tích hợp tốt** - Hỗ trợ Task Module
4. **Performance cao** - Pagination, caching, lazy loading
5. **Dễ sử dụng** - TypeScript interfaces, React hooks

Frontend có thể tận dụng tối đa để tạo ra giao diện quản lý task mạnh mẽ và linh hoạt!

## 📊 TÓM TẮT TRẠNG THÁI API (Cập nhật 2025-01-27)

### ✅ **API HOẠT ĐỘNG:**
- **`GET /api/v1/lecturers`** ✅ - 2 giảng viên
- **`GET /api/v1/classes`** ✅ - 1 lớp học với 2 sinh viên
- **`GET /api/v1/departments`** ✅ - 3 khoa/phòng ban
- **`GET /api/v1/classes/{classId}`** ✅ - Chi tiết lớp với sinh viên

### ❌ **API CÓ VẤN ĐỀ:**
- **`GET /api/v1/students`** ❌ - 500 Internal Server Error

### 🔧 **GIẢI PHÁP CHO FRONTEND:**
1. **Lấy sinh viên:** Sử dụng `/api/v1/classes/{classId}` → `students` array
2. **Lấy giảng viên:** Sử dụng `/api/v1/lecturers` ✅
3. **Lấy lớp học:** Sử dụng `/api/v1/classes` ✅
4. **Lấy khoa:** Sử dụng `/api/v1/departments` ✅

### 🚀 **FRONTEND CÓ THỂ KẾT NỐI THÀNH CÔNG!**

---

**📝 Tài liệu được tạo ngày: 2025-01-27**  
**🔄 Cập nhật lần cuối: 2025-01-27**  
**👨‍💻 Tác giả: System Management Team**
