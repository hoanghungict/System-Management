/**
 * TypeScript Types & Interfaces cho Lecturer Task Management
 * Copy file này vào frontend project để sử dụng
 */

// ============================================
// API Response Types
// ============================================

/**
 * Upload File Response (Single)
 */
export interface LecturerUploadFileResponse {
    success: boolean;
    message: string;
    data: {
        id: number;                          // File ID - QUAN TRỌNG!
        task_id: number;
        lecturer_id: number;
        filename: string;                     // Tên file gốc
        path: string;                        // Path trong storage
        size: number;                         // Kích thước (bytes)
        file_url: string;                    // Full URL để truy cập
        uploaded_at: string;
    };
}

/**
 * Upload Files Response (Multiple)
 */
export interface LecturerUploadFilesResponse {
    success: boolean;
    message: string;
    data: Array<{
        id: number;
        file_name: string;
        file_url: string;
        created_at: string;
    }>;
    count: number;
}

/**
 * Create Task Request
 */
export interface CreateTaskRequest {
    title: string;                         // Bắt buộc
    description: string;                    // Bắt buộc
    deadline: string;                       // Bắt buộc (datetime)
    priority: 'low' | 'medium' | 'high' | 'urgent';
    status?: 'pending' | 'in_progress' | 'completed';
    class_id?: number;
    receivers?: Array<{
        receiver_id: number;
        receiver_type: 'student' | 'class';
    }>;
    files?: number[];                       // File IDs đã upload
}

/**
 * Create Task Response
 */
export interface CreateTaskResponse {
    success: boolean;
    message: string;
    data: {
        id: number;
        title: string;
        description: string;
        deadline: string;
        priority: string;
        status: string;
        creator_id: number;
        creator_type: string;
        created_at: string;
        files?: Array<{
            id: number;
            file_name: string;
            file_url: string;
        }>;
    };
}

/**
 * Update Task Request
 */
export interface UpdateTaskRequest {
    title?: string;
    description?: string;
    deadline?: string;
    priority?: 'low' | 'medium' | 'high' | 'urgent';
    status?: 'pending' | 'in_progress' | 'completed';
    files?: number[];                       // File IDs mới
}

/**
 * Task Object
 */
export interface LecturerTask {
    id: number;
    title: string;
    description: string;
    deadline: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    status: 'pending' | 'in_progress' | 'completed' | 'overdue';
    creator_id: number;
    creator_type: string;
    created_at: string;
    updated_at: string;
    files?: Array<{
        id: number;
        file_name: string;
        file_url: string;
        created_at: string;
    }>;
    receivers?: Array<{
        receiver_id: number;
        receiver_type: string;
    }>;
}

/**
 * Get Tasks Response
 */
export interface GetLecturerTasksResponse {
    success: boolean;
    message: string;
    data: LecturerTask[];
    pagination: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
    };
}

/**
 * Delete File Response
 */
export interface DeleteFileResponse {
    success: boolean;
    message: string;
}

/**
 * Assign Task Request
 */
export interface AssignTaskRequest {
    receiver_ids: number[];
    receiver_type: 'student' | 'class';
}

/**
 * Assign Task Response
 */
export interface AssignTaskResponse {
    success: boolean;
    message: string;
    data: {
        task_id: number;
        assigned_count: number;
        receivers: Array<{
            receiver_id: number;
            receiver_type: string;
        }>;
    };
}

/**
 * Revoke Task Request
 */
export interface RevokeTaskRequest {
    receiver_ids?: number[];                // Nếu không có thì revoke tất cả
    receiver_type?: 'student' | 'class';
}

/**
 * Revoke Task Response
 */
export interface RevokeTaskResponse {
    success: boolean;
    message: string;
    data: {
        task_id: number;
        revoked_count: number;
    };
}

/**
 * Error Response
 */
export interface ErrorResponse {
    success: false;
    message: string;
    error?: string;                        // Only in debug mode
}

// ============================================
// Hook Return Types
// ============================================

/**
 * useUploadFile Hook Return Type
 */
export interface UseLecturerUploadFileReturn {
    uploadFile: (file: File) => Promise<number | null>;
    uploading: boolean;
    error: string | null;
}

/**
 * useUploadFiles Hook Return Type
 */
export interface UseLecturerUploadFilesReturn {
    uploadFiles: (files: File[]) => Promise<number[]>;
    uploading: boolean;
    error: string | null;
}

/**
 * useCreateTask Hook Return Type
 */
export interface UseCreateTaskReturn {
    createTask: (taskData: CreateTaskRequest) => Promise<CreateTaskResponse | null>;
    creating: boolean;
    error: string | null;
}

/**
 * useUpdateTask Hook Return Type
 */
export interface UseUpdateTaskReturn {
    updateTask: (taskId: number, taskData: UpdateTaskRequest) => Promise<CreateTaskResponse | null>;
    updating: boolean;
    error: string | null;
}

/**
 * useGetTasks Hook Return Type
 */
export interface UseGetLecturerTasksReturn {
    tasks: LecturerTask[];
    loading: boolean;
    error: string | null;
    pagination: GetLecturerTasksResponse['pagination'] | null;
    refetch: () => Promise<void>;
}

/**
 * useDeleteFile Hook Return Type
 */
export interface UseDeleteFileReturn {
    deleteFile: (fileId: number) => Promise<boolean>;
    deleting: boolean;
    error: string | null;
}

/**
 * useAssignTask Hook Return Type
 */
export interface UseAssignTaskReturn {
    assignTask: (taskId: number, data: AssignTaskRequest) => Promise<AssignTaskResponse | null>;
    assigning: boolean;
    error: string | null;
}

// ============================================
// Constants
// ============================================

/**
 * API Base URL
 */
export const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8082';

/**
 * API Endpoints
 */
export const LECTURER_API_ENDPOINTS = {
    BASE: '/api/v1/lecturer-tasks',
    UPLOAD_FILE: (taskId: number) => `/api/v1/lecturer-tasks/${taskId}/upload-file`,
    UPLOAD_FILES: (taskId: number) => `/api/v1/lecturer-tasks/${taskId}/files`,
    CREATE_TASK: '/api/v1/lecturer-tasks',
    GET_TASK: (taskId: number) => `/api/v1/lecturer-tasks/${taskId}`,
    UPDATE_TASK: (taskId: number) => `/api/v1/lecturer-tasks/${taskId}`,
    DELETE_TASK: (taskId: number) => `/api/v1/lecturer-tasks/${taskId}`,
    DELETE_FILE: (taskId: number, fileId: number) => `/api/v1/lecturer-tasks/${taskId}/files/${fileId}`,
    DOWNLOAD_FILE: (taskId: number, fileId: number) => `/api/v1/lecturer-tasks/${taskId}/files/${fileId}/download`,
    ASSIGN_TASK: (taskId: number) => `/api/v1/lecturer-tasks/${taskId}/assign`,
    REVOKE_TASK: (taskId: number) => `/api/v1/lecturer-tasks/${taskId}/revoke`,
    GET_CREATED_TASKS: '/api/v1/lecturer-tasks/created',
    GET_ASSIGNED_TASKS: '/api/v1/lecturer-tasks/assigned',
    GET_STATISTICS: '/api/v1/lecturer-tasks/statistics',
} as const;

/**
 * File Upload Constants
 */
export const FILE_UPLOAD = {
    MAX_SIZE: 10 * 1024 * 1024,           // 10MB
    ALLOWED_TYPES: [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/zip',
        'application/x-rar-compressed',
        'text/plain'
    ],
    ALLOWED_EXTENSIONS: ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png', '.zip', '.rar', '.txt']
} as const;

// ============================================
// Helper Functions Types
// ============================================

/**
 * Format file size helper
 */
export function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Validate file type
 */
export function isValidFileType(file: File): boolean {
    return FILE_UPLOAD.ALLOWED_TYPES.includes(file.type) ||
        FILE_UPLOAD.ALLOWED_EXTENSIONS.some(ext => file.name.toLowerCase().endsWith(ext));
}

/**
 * Validate file size
 */
export function isValidFileSize(file: File): boolean {
    return file.size <= FILE_UPLOAD.MAX_SIZE;
}

/**
 * Get file extension
 */
export function getFileExtension(filename: string): string {
    return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2);
}

