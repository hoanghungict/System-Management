/**
 * TypeScript Types & Interfaces cho Student Task Submission
 * Copy file này vào frontend project để sử dụng
 */

// ============================================
// API Response Types
// ============================================

/**
 * Upload File Response
 */
export interface UploadFileResponse {
    success: boolean;
    message: string;
    data: {
        id: number;                          // File ID - QUAN TRỌNG!
        task_id: string | number;
        student_id: number;
        filename: string;                     // Tên file gốc
        path: string;                        // Path trong storage
        size: number;                         // Kích thước (bytes)
        file_url: string;                    // Full URL để truy cập
        uploaded_at: string;
    };
}

/**
 * Submit Task Request
 */
export interface SubmitTaskRequest {
    content: string;                       // Bắt buộc
    files?: number[];                      // Array of file IDs (optional)
    notes?: string;                        // Optional

    // Hoặc format đầy đủ (cũng được hỗ trợ):
    submission_content?: string;
    submission_files?: number[];
    submission_notes?: string;
}

/**
 * Submit Task Response
 */
export interface SubmitTaskResponse {
    success: boolean;
    message: string;
    data: {
        id: number;
        task_id: number;
        student_id: number;
        submission_content: string;
        submission_files: number[];           // Array of file IDs
        submitted_at: string;
        status: 'pending' | 'graded' | 'returned';
        grade: number | null;
        feedback: string | null;
        graded_at: string | null;
        graded_by: number | null;
        created_at: string;
        updated_at: string;
        deleted_at: string | null;
    };
}

/**
 * Submission File Object
 */
export interface SubmissionFile {
    id: number;
    file_name: string;
    name: string;
    file_path: string;
    file_url: string;
    file_size: number;
    size: number;
    mime_type: string | null;
    created_at: string;
}

/**
 * Get Submission Response
 */
export interface GetSubmissionResponse {
    success: boolean;
    message: string;
    data: {
        id: number;
        task_id: number;
        student_id: number;
        content: string;
        submission_content: string;
        submitted_at: string;
        updated_at: string;
        status: 'pending' | 'graded' | 'returned';
        files: SubmissionFile[];             // Luôn là array, không phải null
        grade: number | null;
        feedback: string | null;
    } | null;                               // null nếu chưa có submission (404)
}

/**
 * Delete File Response
 */
export interface DeleteFileResponse {
    success: boolean;
    message: string;
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
// Task Types
// ============================================

/**
 * Task Status
 */
export type TaskStatus = 'pending' | 'in_progress' | 'completed' | 'overdue';

/**
 * Submission Status
 */
export type SubmissionStatus = 'pending' | 'graded' | 'returned';

/**
 * Task Priority
 */
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

/**
 * Task Object (Basic)
 */
export interface Task {
    id: number;
    title: string;
    description: string;
    deadline: string;
    status: TaskStatus;
    priority: TaskPriority;
    created_at: string;
    updated_at: string;
}

// ============================================
// Hook Return Types
// ============================================

/**
 * useUploadFile Hook Return Type
 */
export interface UseUploadFileReturn {
    uploadFile: (file: File) => Promise<number | null>;
    uploading: boolean;
    error: string | null;
}

/**
 * useSubmitTask Hook Return Type
 */
export interface UseSubmitTaskReturn {
    submitTask: (
        content: string,
        fileIds?: number[],
        notes?: string
    ) => Promise<SubmitTaskResponse | null>;
    submitting: boolean;
    error: string | null;
}

/**
 * useGetSubmission Hook Return Type
 */
export interface UseGetSubmissionReturn {
    submission: GetSubmissionResponse['data'] | null;
    loading: boolean;
    error: string | null;
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

// ============================================
// Utility Types
// ============================================

/**
 * API Response Wrapper
 */
export type ApiResponse<T> = {
    success: boolean;
    message: string;
    data: T;
};

/**
 * Pagination Metadata
 */
export interface PaginationMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
}

/**
 * Paginated Response
 */
export interface PaginatedResponse<T> {
    data: T[];
    pagination: PaginationMeta;
}

// ============================================
// Form Types
// ============================================

/**
 * Submission Form Data
 */
export interface SubmissionFormData {
    content: string;
    files: File[];                         // Files để upload
    uploadedFileIds: number[];             // File IDs đã upload
    notes?: string;
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
export const API_ENDPOINTS = {
    // Student Task Endpoints
    STUDENT_TASKS: '/api/v1/student-tasks',
    UPLOAD_FILE: (taskId: number) => `/api/v1/student-tasks/${taskId}/upload-file`,
    SUBMIT_TASK: (taskId: number) => `/api/v1/student-tasks/${taskId}/submit`,
    GET_SUBMISSION: (taskId: number) => `/api/v1/student-tasks/${taskId}/submission`,
    UPDATE_SUBMISSION: (taskId: number) => `/api/v1/student-tasks/${taskId}/submission`,
    DELETE_FILE: (taskId: number, fileId: number) => `/api/v1/student-tasks/${taskId}/files/${fileId}`,
    GET_FILES: (taskId: number) => `/api/v1/student-tasks/${taskId}/files`,
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

