# 🎨 Task Management System - UI Specification cho Next.js Frontend

## 📚 Tổng quan Hệ thống

Dựa trên phân tích backend Laravel, hệ thống Task Management có **127+ API endpoints** với các chức năng chính:

- **📋 Task Management**: CRUD operations, file uploads, submissions
- **📅 Calendar Integration**: Events, deadlines, reminders  
- **🔔 Notification System**: Multi-channel (Email, Push, SMS, In-app)
- **📊 Analytics & Reports**: Statistics, charts, exports
- **👥 Role-based Access**: Admin, Lecturer, Student permissions
- **⏰ Reminder System**: Automatic & manual reminders
- **🔗 Task Dependencies**: Task relationships & workflows

---

## 🎭 Phân quyền UI Components

### **🔧 ADMIN Dashboard**
```typescript
interface AdminDashboard {
  // System Overview
  systemStats: {
    totalTasks: number;
    totalUsers: number;
    activeUsers: number;
    systemHealth: 'healthy' | 'warning' | 'critical';
  };
  
  // Task Management
  allTasks: Task[];
  taskStatistics: TaskStats;
  userManagement: User[];
  
  // System Monitoring
  systemMetrics: SystemMetrics;
  errorLogs: ErrorLog[];
  performanceMetrics: PerformanceData;
}
```

### **👨‍🏫 LECTURER Dashboard**
```typescript
interface LecturerDashboard {
  // Personal Stats
  personalStats: {
    createdTasks: number;
    assignedTasks: number;
    completedTasks: number;
    pendingGrading: number;
  };
  
  // Task Management
  myTasks: Task[];
  studentSubmissions: Submission[];
  gradingQueue: Task[];
  
  // Calendar & Events
  calendarEvents: CalendarEvent[];
  upcomingDeadlines: Task[];
  classSchedule: Schedule[];
}
```

### **👨‍🎓 STUDENT Dashboard**
```typescript
interface StudentDashboard {
  // Personal Stats
  personalStats: {
    assignedTasks: number;
    completedTasks: number;
    pendingTasks: number;
    overdueTasks: number;
  };
  
  // Task Management
  myTasks: Task[];
  submissions: Submission[];
  grades: Grade[];
  
  // Calendar & Reminders
  calendarEvents: CalendarEvent[];
  upcomingDeadlines: Task[];
  reminders: Reminder[];
}
```

---

## 🏗️ Core UI Components

### **1. 📋 Task Management Components**

#### **TaskList Component**
```typescript
interface TaskListProps {
  tasks: Task[];
  filters: TaskFilters;
  onFilterChange: (filters: TaskFilters) => void;
  onTaskUpdate: (taskId: number) => void;
  userRole: 'admin' | 'lecturer' | 'student';
}

const TaskList: React.FC<TaskListProps> = ({
  tasks,
  filters,
  onFilterChange,
  onTaskUpdate,
  userRole
}) => {
  return (
    <div className="task-list">
      <TaskFilters 
        filters={filters} 
        onFilterChange={onFilterChange}
        userRole={userRole}
      />
      <div className="task-grid">
        {tasks.map(task => (
          <TaskCard 
            key={task.id} 
            task={task} 
            onUpdate={onTaskUpdate}
            userRole={userRole}
          />
        ))}
      </div>
      <TaskPagination />
    </div>
  );
};
```

#### **TaskCard Component**
```typescript
interface TaskCardProps {
  task: Task;
  onUpdate: (taskId: number) => void;
  userRole: 'admin' | 'lecturer' | 'student';
}

const TaskCard: React.FC<TaskCardProps> = ({ task, onUpdate, userRole }) => {
  const getPriorityColor = (priority: string) => {
    const colors = {
      low: 'bg-green-100 text-green-800',
      medium: 'bg-yellow-100 text-yellow-800', 
      high: 'bg-orange-100 text-orange-800',
      urgent: 'bg-red-100 text-red-800'
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
  };

  const getStatusBadge = (status: string) => {
    const badges = {
      pending: { text: 'Pending', class: 'bg-yellow-100 text-yellow-800' },
      in_progress: { text: 'In Progress', class: 'bg-blue-100 text-blue-800' },
      completed: { text: 'Completed', class: 'bg-green-100 text-green-800' },
      overdue: { text: 'Overdue', class: 'bg-red-100 text-red-800' }
    };
    return badges[status] || { text: status, class: 'bg-gray-100 text-gray-800' };
  };

  return (
    <div className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      {/* Header */}
      <div className="flex justify-between items-start mb-4">
        <h3 className="text-lg font-semibold text-gray-900">{task.title}</h3>
        <div className="flex gap-2">
          <span className={`px-2 py-1 rounded-full text-xs font-medium ${getPriorityColor(task.priority)}`}>
            {task.priority.toUpperCase()}
          </span>
          <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusBadge(task.status).class}`}>
            {getStatusBadge(task.status).text}
          </span>
        </div>
      </div>

      {/* Description */}
      <p className="text-gray-600 mb-4 line-clamp-3">{task.description}</p>

      {/* Metadata */}
      <div className="flex justify-between items-center text-sm text-gray-500 mb-4">
        <span>Deadline: {formatDate(task.deadline)}</span>
        <span>Created: {formatDate(task.created_at)}</span>
      </div>

      {/* Files */}
      {task.files && task.files.length > 0 && (
        <div className="mb-4">
          <div className="flex flex-wrap gap-2">
            {task.files.map(file => (
              <FileBadge key={file.id} file={file} />
            ))}
          </div>
        </div>
      )}

      {/* Actions */}
      <div className="flex gap-2">
        <button 
          onClick={() => viewTask(task.id)}
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          View Details
        </button>
        
        {userRole === 'student' && task.status === 'pending' && (
          <button 
            onClick={() => submitTask(task.id)}
            className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
          >
            Submit
          </button>
        )}
        
        {userRole === 'lecturer' && task.status === 'submitted' && (
          <button 
            onClick={() => gradeTask(task.id)}
            className="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors"
          >
            Grade
          </button>
        )}
      </div>
    </div>
  );
};
```

#### **TaskForm Component**
```typescript
interface TaskFormProps {
  task?: Task;
  onSubmit: (taskData: TaskFormData) => void;
  onCancel: () => void;
  userRole: 'admin' | 'lecturer';
}

const TaskForm: React.FC<TaskFormProps> = ({ task, onSubmit, onCancel, userRole }) => {
  const [formData, setFormData] = useState<TaskFormData>({
    title: task?.title || '',
    description: task?.description || '',
    deadline: task?.deadline || '',
    priority: task?.priority || 'medium',
    receivers: task?.receivers || [],
    files: []
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  const validateForm = (data: TaskFormData) => {
    const newErrors: Record<string, string> = {};
    
    if (!data.title || data.title.trim().length < 3) {
      newErrors.title = 'Title must be at least 3 characters';
    }
    
    if (!data.deadline) {
      newErrors.deadline = 'Deadline is required';
    } else if (new Date(data.deadline) <= new Date()) {
      newErrors.deadline = 'Deadline must be in the future';
    }
    
    if (!data.receivers || data.receivers.length === 0) {
      newErrors.receivers = 'At least one receiver is required';
    }
    
    return newErrors;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const newErrors = validateForm(formData);
    
    if (Object.keys(newErrors).length === 0) {
      onSubmit(formData);
    } else {
      setErrors(newErrors);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Title */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Task Title *
        </label>
        <input
          type="text"
          value={formData.title}
          onChange={(e) => setFormData(prev => ({ ...prev, title: e.target.value }))}
          className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.title ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="Enter task title"
        />
        {errors.title && <p className="text-red-500 text-sm mt-1">{errors.title}</p>}
      </div>

      {/* Description */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Description *
        </label>
        <textarea
          value={formData.description}
          onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
          rows={4}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter task description"
        />
      </div>

      {/* Deadline & Priority */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Deadline *
          </label>
          <input
            type="datetime-local"
            value={formData.deadline}
            onChange={(e) => setFormData(prev => ({ ...prev, deadline: e.target.value }))}
            className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.deadline ? 'border-red-500' : 'border-gray-300'
            }`}
          />
          {errors.deadline && <p className="text-red-500 text-sm mt-1">{errors.deadline}</p>}
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Priority
          </label>
          <select
            value={formData.priority}
            onChange={(e) => setFormData(prev => ({ ...prev, priority: e.target.value }))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>

      {/* Receivers */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Assign To *
        </label>
        <ReceiverSelector
          selected={formData.receivers}
          onChange={(receivers) => setFormData(prev => ({ ...prev, receivers }))}
          userRole={userRole}
        />
        {errors.receivers && <p className="text-red-500 text-sm mt-1">{errors.receivers}</p>}
      </div>

      {/* File Upload */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Attachments
        </label>
        <FileUpload
          files={formData.files}
          onChange={(files) => setFormData(prev => ({ ...prev, files }))}
          maxFiles={5}
          maxSize={10 * 1024 * 1024} // 10MB
        />
      </div>

      {/* Actions */}
      <div className="flex justify-end gap-4">
        <button
          type="button"
          onClick={onCancel}
          className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
        >
          Cancel
        </button>
        <button
          type="submit"
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          {task ? 'Update Task' : 'Create Task'}
        </button>
      </div>
    </form>
  );
};
```

### **2. 📅 Calendar Components**

#### **CalendarView Component**
```typescript
interface CalendarViewProps {
  events: CalendarEvent[];
  onEventClick: (event: CalendarEvent) => void;
  onDateClick: (date: Date) => void;
  view: 'month' | 'week' | 'day';
}

const CalendarView: React.FC<CalendarViewProps> = ({
  events,
  onEventClick,
  onDateClick,
  view
}) => {
  return (
    <div className="calendar-container">
      <div className="calendar-header">
        <CalendarNavigation />
        <CalendarViewToggle />
      </div>
      
      <div className="calendar-body">
        {view === 'month' && <MonthView events={events} onEventClick={onEventClick} />}
        {view === 'week' && <WeekView events={events} onEventClick={onEventClick} />}
        {view === 'day' && <DayView events={events} onEventClick={onEventClick} />}
      </div>
    </div>
  );
};
```

#### **EventCard Component**
```typescript
interface EventCardProps {
  event: CalendarEvent;
  onClick: (event: CalendarEvent) => void;
}

const EventCard: React.FC<EventCardProps> = ({ event, onClick }) => {
  const getEventColor = (type: string) => {
    const colors = {
      task: 'bg-blue-100 border-blue-300 text-blue-800',
      deadline: 'bg-red-100 border-red-300 text-red-800',
      reminder: 'bg-yellow-100 border-yellow-300 text-yellow-800',
      class: 'bg-green-100 border-green-300 text-green-800'
    };
    return colors[type] || 'bg-gray-100 border-gray-300 text-gray-800';
  };

  return (
    <div 
      className={`p-2 rounded-md border cursor-pointer hover:shadow-md transition-shadow ${getEventColor(event.type)}`}
      onClick={() => onClick(event)}
    >
      <div className="font-medium text-sm">{event.title}</div>
      <div className="text-xs opacity-75">{formatTime(event.start)}</div>
      {event.priority && (
        <div className="text-xs font-medium mt-1">
          {event.priority.toUpperCase()}
        </div>
      )}
    </div>
  );
};
```

### **3. 📊 Dashboard Components**

#### **StatisticsDashboard Component**
```typescript
interface StatisticsDashboardProps {
  userRole: 'admin' | 'lecturer' | 'student';
  stats: DashboardStats;
}

const StatisticsDashboard: React.FC<StatisticsDashboardProps> = ({ userRole, stats }) => {
  return (
    <div className="dashboard-container">
      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatCard
          title="Total Tasks"
          value={stats.totalTasks}
          icon="📋"
          color="blue"
        />
        <StatCard
          title="Completed"
          value={stats.completedTasks}
          icon="✅"
          color="green"
        />
        <StatCard
          title="Pending"
          value={stats.pendingTasks}
          icon="⏳"
          color="yellow"
        />
        <StatCard
          title="Overdue"
          value={stats.overdueTasks}
          icon="⚠️"
          color="red"
        />
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <CompletionRateChart data={stats.completionRate} />
        <PriorityDistributionChart data={stats.priorityDistribution} />
      </div>

      {/* Recent Activities */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <RecentActivities activities={stats.recentActivities} />
        <UpcomingDeadlines deadlines={stats.upcomingDeadlines} />
      </div>
    </div>
  );
};
```

#### **StatCard Component**
```typescript
interface StatCardProps {
  title: string;
  value: number;
  icon: string;
  color: 'blue' | 'green' | 'yellow' | 'red' | 'purple';
  trend?: {
    value: number;
    direction: 'up' | 'down' | 'neutral';
  };
}

const StatCard: React.FC<StatCardProps> = ({ title, value, icon, color, trend }) => {
  const getColorClasses = (color: string) => {
    const colors = {
      blue: 'bg-blue-50 border-blue-200 text-blue-800',
      green: 'bg-green-50 border-green-200 text-green-800',
      yellow: 'bg-yellow-50 border-yellow-200 text-yellow-800',
      red: 'bg-red-50 border-red-200 text-red-800',
      purple: 'bg-purple-50 border-purple-200 text-purple-800'
    };
    return colors[color] || colors.blue;
  };

  return (
    <div className={`p-6 rounded-lg border ${getColorClasses(color)}`}>
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium opacity-75">{title}</p>
          <p className="text-2xl font-bold">{value.toLocaleString()}</p>
        </div>
        <div className="text-3xl">{icon}</div>
      </div>
      
      {trend && (
        <div className="mt-2 flex items-center">
          <span className={`text-sm ${
            trend.direction === 'up' ? 'text-green-600' : 
            trend.direction === 'down' ? 'text-red-600' : 'text-gray-600'
          }`}>
            {trend.direction === 'up' ? '↗' : trend.direction === 'down' ? '↘' : '→'} 
            {Math.abs(trend.value)}%
          </span>
          <span className="text-sm opacity-75 ml-1">vs last period</span>
        </div>
      )}
    </div>
  );
};
```

### **4. 📤 Task Submission Components**

#### **TaskSubmissionForm Component**
```typescript
interface TaskSubmissionFormProps {
  task: Task;
  onSubmit: (submission: SubmissionData) => void;
  onCancel: () => void;
}

const TaskSubmissionForm: React.FC<TaskSubmissionFormProps> = ({
  task,
  onSubmit,
  onCancel
}) => {
  const [submission, setSubmission] = useState<SubmissionData>({
    content: '',
    files: []
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleFileUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(event.target.files || []);
    setSubmission(prev => ({
      ...prev,
      files: [...prev.files, ...files]
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    
    try {
      await onSubmit(submission);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto p-6">
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{task.title}</h2>
        <p className="text-gray-600">{task.description}</p>
        <div className="mt-4 flex items-center gap-4 text-sm text-gray-500">
          <span>Deadline: {formatDate(task.deadline)}</span>
          <span className={`px-2 py-1 rounded-full text-xs font-medium ${
            task.priority === 'urgent' ? 'bg-red-100 text-red-800' :
            task.priority === 'high' ? 'bg-orange-100 text-orange-800' :
            task.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
            'bg-green-100 text-green-800'
          }`}>
            {task.priority.toUpperCase()}
          </span>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Submission Content */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Your Submission *
          </label>
          <textarea
            value={submission.content}
            onChange={(e) => setSubmission(prev => ({ ...prev, content: e.target.value }))}
            rows={10}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Describe your work, findings, or any additional information..."
            required
          />
        </div>

        {/* File Upload */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Attach Files
          </label>
          <div className="border-2 border-dashed border-gray-300 rounded-lg p-6">
            <input
              type="file"
              multiple
              onChange={handleFileUpload}
              className="w-full"
              accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip,.rar"
            />
            <p className="text-sm text-gray-500 mt-2">
              Supported formats: PDF, DOC, DOCX, TXT, JPG, PNG, ZIP, RAR (Max 10MB each)
            </p>
          </div>

          {/* File List */}
          {submission.files.length > 0 && (
            <div className="mt-4">
              <h4 className="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
              <div className="space-y-2">
                {submission.files.map((file, index) => (
                  <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <span className="text-sm">{file.name}</span>
                    <span className="text-xs text-gray-500">
                      {formatFileSize(file.size)}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Actions */}
        <div className="flex justify-end gap-4">
          <button
            type="button"
            onClick={onCancel}
            className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={isSubmitting || !submission.content.trim()}
            className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSubmitting ? 'Submitting...' : 'Submit Task'}
          </button>
        </div>
      </form>
    </div>
  );
};
```

### **5. 🔔 Notification Components**

#### **NotificationCenter Component**
```typescript
interface NotificationCenterProps {
  notifications: Notification[];
  onMarkAsRead: (id: number) => void;
  onMarkAllAsRead: () => void;
  onDelete: (id: number) => void;
}

const NotificationCenter: React.FC<NotificationCenterProps> = ({
  notifications,
  onMarkAsRead,
  onMarkAllAsRead,
  onDelete
}) => {
  const unreadCount = notifications.filter(n => !n.read).length;

  return (
    <div className="notification-center">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold">Notifications</h3>
        <div className="flex items-center gap-2">
          {unreadCount > 0 && (
            <button
              onClick={onMarkAllAsRead}
              className="text-sm text-blue-600 hover:text-blue-800"
            >
              Mark all as read
            </button>
          )}
          <span className="text-sm text-gray-500">
            {unreadCount} unread
          </span>
        </div>
      </div>

      <div className="space-y-2 max-h-96 overflow-y-auto">
        {notifications.map(notification => (
          <NotificationItem
            key={notification.id}
            notification={notification}
            onMarkAsRead={onMarkAsRead}
            onDelete={onDelete}
          />
        ))}
      </div>
    </div>
  );
};
```

#### **NotificationItem Component**
```typescript
interface NotificationItemProps {
  notification: Notification;
  onMarkAsRead: (id: number) => void;
  onDelete: (id: number) => void;
}

const NotificationItem: React.FC<NotificationItemProps> = ({
  notification,
  onMarkAsRead,
  onDelete
}) => {
  const getNotificationIcon = (type: string) => {
    const icons = {
      task_created: '📋',
      task_updated: '✏️',
      task_assigned: '👤',
      task_submitted: '📤',
      task_graded: '📊',
      reminder: '⏰',
      deadline: '⚠️'
    };
    return icons[type] || '🔔';
  };

  const getNotificationColor = (type: string) => {
    const colors = {
      task_created: 'bg-blue-50 border-blue-200',
      task_updated: 'bg-yellow-50 border-yellow-200',
      task_assigned: 'bg-green-50 border-green-200',
      task_submitted: 'bg-purple-50 border-purple-200',
      task_graded: 'bg-indigo-50 border-indigo-200',
      reminder: 'bg-orange-50 border-orange-200',
      deadline: 'bg-red-50 border-red-200'
    };
    return colors[type] || 'bg-gray-50 border-gray-200';
  };

  return (
    <div className={`p-4 rounded-lg border ${getNotificationColor(notification.type)} ${
      !notification.read ? 'ring-2 ring-blue-200' : ''
    }`}>
      <div className="flex items-start gap-3">
        <div className="text-2xl">{getNotificationIcon(notification.type)}</div>
        
        <div className="flex-1">
          <div className="flex items-center justify-between">
            <h4 className="font-medium text-gray-900">{notification.title}</h4>
            <div className="flex items-center gap-2">
              <span className="text-xs text-gray-500">
                {formatTimeAgo(notification.created_at)}
              </span>
              {!notification.read && (
                <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
              )}
            </div>
          </div>
          
          <p className="text-sm text-gray-600 mt-1">{notification.message}</p>
          
          <div className="flex items-center gap-2 mt-2">
            <button
              onClick={() => onMarkAsRead(notification.id)}
              className="text-xs text-blue-600 hover:text-blue-800"
            >
              {notification.read ? 'Mark as unread' : 'Mark as read'}
            </button>
            <button
              onClick={() => onDelete(notification.id)}
              className="text-xs text-red-600 hover:text-red-800"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
```

---

## 📱 Responsive Design

### **Mobile-First Approach**
```css
/* Base styles for mobile */
.task-card {
  padding: 1rem;
  margin-bottom: 1rem;
}

.task-actions {
  flex-direction: column;
  gap: 0.5rem;
}

.task-actions button {
  width: 100%;
}

/* Tablet styles */
@media (min-width: 768px) {
  .task-card {
    padding: 1.5rem;
  }
  
  .task-actions {
    flex-direction: row;
  }
  
  .task-actions button {
    width: auto;
  }
}

/* Desktop styles */
@media (min-width: 1024px) {
  .task-card {
    padding: 2rem;
  }
  
  .dashboard-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
```

### **Touch-Friendly Interface**
```typescript
// Mobile-specific components
const MobileTaskList = () => {
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  
  return (
    <div className="mobile-task-list">
      <div className="mobile-header sticky top-0 bg-white z-10 p-4 border-b">
        <div className="flex items-center justify-between">
          <h2 className="text-xl font-semibold">Tasks</h2>
          <button 
            className="p-2 rounded-md bg-gray-100"
            onClick={() => setIsFilterOpen(!isFilterOpen)}
          >
            <FilterIcon className="w-5 h-5" />
          </button>
        </div>
      </div>
      
      {isFilterOpen && (
        <div className="mobile-filters p-4 bg-gray-50">
          <TaskFilters onClose={() => setIsFilterOpen(false)} />
        </div>
      )}
      
      <div className="task-list p-4">
        {/* Task cards */}
      </div>
    </div>
  );
};
```

---

## 🎨 Design System

### **Color Palette**
```typescript
const colors = {
  primary: {
    50: '#eff6ff',
    100: '#dbeafe',
    500: '#3b82f6',
    600: '#2563eb',
    700: '#1d4ed8'
  },
  success: {
    50: '#f0fdf4',
    100: '#dcfce7',
    500: '#22c55e',
    600: '#16a34a'
  },
  warning: {
    50: '#fffbeb',
    100: '#fef3c7',
    500: '#f59e0b',
    600: '#d97706'
  },
  danger: {
    50: '#fef2f2',
    100: '#fee2e2',
    500: '#ef4444',
    600: '#dc2626'
  }
};
```

### **Typography Scale**
```typescript
const typography = {
  h1: 'text-3xl font-bold',
  h2: 'text-2xl font-semibold',
  h3: 'text-xl font-semibold',
  h4: 'text-lg font-medium',
  body: 'text-base',
  small: 'text-sm',
  caption: 'text-xs'
};
```

### **Spacing System**
```typescript
const spacing = {
  xs: '0.25rem',    // 4px
  sm: '0.5rem',     // 8px
  md: '1rem',       // 16px
  lg: '1.5rem',     // 24px
  xl: '2rem',       // 32px
  '2xl': '3rem',    // 48px
  '3xl': '4rem'     // 64px
};
```

---

## 🚀 Performance Optimization

### **Code Splitting**
```typescript
// Lazy load components
const TaskForm = lazy(() => import('./components/TaskForm'));
const CalendarView = lazy(() => import('./components/CalendarView'));
const StatisticsDashboard = lazy(() => import('./components/StatisticsDashboard'));

// Route-based code splitting
const routes = [
  {
    path: '/tasks',
    component: lazy(() => import('./pages/TasksPage'))
  },
  {
    path: '/calendar',
    component: lazy(() => import('./pages/CalendarPage'))
  },
  {
    path: '/statistics',
    component: lazy(() => import('./pages/StatisticsPage'))
  }
];
```

### **Data Fetching Optimization**
```typescript
// React Query for caching and synchronization
const useTasks = (filters: TaskFilters) => {
  return useQuery({
    queryKey: ['tasks', filters],
    queryFn: () => fetchTasks(filters),
    staleTime: 5 * 60 * 1000, // 5 minutes
    cacheTime: 10 * 60 * 1000, // 10 minutes
  });
};

// Optimistic updates
const useTaskMutation = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: updateTask,
    onMutate: async (newTask) => {
      await queryClient.cancelQueries(['tasks']);
      const previousTasks = queryClient.getQueryData(['tasks']);
      queryClient.setQueryData(['tasks'], old => [...old, newTask]);
      return { previousTasks };
    },
    onError: (err, newTask, context) => {
      queryClient.setQueryData(['tasks'], context?.previousTasks);
    },
    onSettled: () => {
      queryClient.invalidateQueries(['tasks']);
    }
  });
};
```

---

## 🧪 Testing Strategy

### **Component Testing**
```typescript
// TaskCard.test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import TaskCard from './TaskCard';

describe('TaskCard', () => {
  const mockTask = {
    id: 1,
    title: 'Test Task',
    description: 'Test Description',
    deadline: '2025-02-15',
    priority: 'high',
    status: 'pending'
  };

  test('renders task information correctly', () => {
    render(<TaskCard task={mockTask} onUpdate={jest.fn()} userRole="student" />);
    
    expect(screen.getByText('Test Task')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
    expect(screen.getByText('HIGH')).toBeInTheDocument();
  });

  test('calls onUpdate when submit button is clicked', () => {
    const mockOnUpdate = jest.fn();
    render(<TaskCard task={mockTask} onUpdate={mockOnUpdate} userRole="student" />);
    
    fireEvent.click(screen.getByText('Submit'));
    expect(mockOnUpdate).toHaveBeenCalledWith(1);
  });
});
```

### **Integration Testing**
```typescript
// TaskList.integration.test.tsx
import { render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import TaskList from './TaskList';

describe('TaskList Integration', () => {
  test('loads and displays tasks', async () => {
    const queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false }
      }
    });

    render(
      <QueryClientProvider client={queryClient}>
        <TaskList userRole="student" />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Loading...')).toBeInTheDocument();
    });

    await waitFor(() => {
      expect(screen.getByText('Test Task')).toBeInTheDocument();
    });
  });
});
```

---

## 📦 Project Structure

```
src/
├── components/
│   ├── common/
│   │   ├── Button.tsx
│   │   ├── Modal.tsx
│   │   ├── LoadingSpinner.tsx
│   │   └── ErrorBoundary.tsx
│   ├── task/
│   │   ├── TaskCard.tsx
│   │   ├── TaskList.tsx
│   │   ├── TaskForm.tsx
│   │   ├── TaskSubmissionForm.tsx
│   │   └── TaskFilters.tsx
│   ├── calendar/
│   │   ├── CalendarView.tsx
│   │   ├── EventCard.tsx
│   │   └── CalendarNavigation.tsx
│   ├── dashboard/
│   │   ├── StatisticsDashboard.tsx
│   │   ├── StatCard.tsx
│   │   ├── RecentActivities.tsx
│   │   └── UpcomingDeadlines.tsx
│   └── notifications/
│       ├── NotificationCenter.tsx
│       ├── NotificationItem.tsx
│       └── NotificationBadge.tsx
├── pages/
│   ├── dashboard/
│   │   ├── index.tsx
│   │   └── [role].tsx
│   ├── tasks/
│   │   ├── index.tsx
│   │   ├── [id].tsx
│   │   └── create.tsx
│   ├── calendar/
│   │   └── index.tsx
│   └── statistics/
│       └── index.tsx
├── hooks/
│   ├── useTasks.ts
│   ├── useTaskMutation.ts
│   ├── useCalendar.ts
│   └── useNotifications.ts
├── services/
│   ├── api.ts
│   ├── taskService.ts
│   ├── calendarService.ts
│   └── notificationService.ts
├── types/
│   ├── task.ts
│   ├── calendar.ts
│   ├── notification.ts
│   └── user.ts
├── utils/
│   ├── formatters.ts
│   ├── validators.ts
│   └── constants.ts
└── styles/
    ├── globals.css
    ├── components.css
    └── utilities.css
```

---

## 🚀 Deployment Checklist

### **Pre-deployment**
- [ ] All components tested
- [ ] API integration verified
- [ ] Error handling implemented
- [ ] Loading states added
- [ ] Mobile responsiveness verified
- [ ] Performance optimized
- [ ] Security measures in place

### **Environment Configuration**
```typescript
// .env.local
NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_WS_URL=ws://localhost:6001
NEXT_PUBLIC_VAPID_PUBLIC_KEY=your_vapid_public_key
```

### **Build Configuration**
```typescript
// next.config.js
module.exports = {
  env: {
    CUSTOM_KEY: process.env.CUSTOM_KEY,
  },
  images: {
    domains: ['localhost'],
  },
  experimental: {
    appDir: true,
  },
};
```

---

## 📚 Tài liệu tham khảo

### **UI Libraries**
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Headless UI](https://headlessui.com/)
- [Radix UI](https://www.radix-ui.com/)

### **Charts & Visualization**
- [Chart.js](https://www.chartjs.org/docs)
- [Recharts](https://recharts.org/)
- [D3.js](https://d3js.org/)

### **Calendar**
- [FullCalendar](https://fullcalendar.io/docs)
- [React Big Calendar](https://github.com/jquense/react-big-calendar)

### **State Management**
- [TanStack Query](https://tanstack.com/query/latest)
- [Zustand](https://github.com/pmndrs/zustand)
- [Redux Toolkit](https://redux-toolkit.js.org/)

---

**🎯 Với specification này, frontend team có thể xây dựng UI hoàn chỉnh cho Task Management System!**

**📞 Liên hệ support nếu cần hỗ trợ thêm!**
