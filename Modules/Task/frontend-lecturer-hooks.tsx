/**
 * React Hooks cho Lecturer Task Management
 * Copy file này vào frontend project để sử dụng
 * 
 * Dependencies: React, React Hooks
 */

import React, { useState, useCallback, useEffect } from 'react';
import {
    LecturerUploadFileResponse,
    LecturerUploadFilesResponse,
    CreateTaskRequest,
    CreateTaskResponse,
    UpdateTaskRequest,
    GetLecturerTasksResponse,
    DeleteFileResponse,
    AssignTaskRequest,
    AssignTaskResponse,
    RevokeTaskRequest,
    RevokeTaskResponse,
    UseLecturerUploadFileReturn,
    UseLecturerUploadFilesReturn,
    UseCreateTaskReturn,
    UseUpdateTaskReturn,
    UseGetLecturerTasksReturn,
    UseDeleteFileReturn,
    UseAssignTaskReturn,
    API_BASE_URL,
    LECTURER_API_ENDPOINTS
} from './frontend-lecturer-types';

// ============================================
// useUploadFile Hook (Single File)
// ============================================

/**
 * Hook để upload single file cho task (Lecturer)
 * 
 * @param taskId - Task ID
 * @param token - JWT token
 * @returns Upload functions và state
 */
export function useLecturerUploadFile(
    taskId: number,
    token: string
): UseLecturerUploadFileReturn {
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const uploadFile = useCallback(async (file: File): Promise<number | null> => {
        setUploading(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch(
                `${API_BASE_URL}${LECTURER_API_ENDPOINTS.UPLOAD_FILE(taskId)}`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`
                        // KHÔNG set Content-Type, browser sẽ tự động set với boundary
                    },
                    body: formData
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Upload failed');
            }

            const result: LecturerUploadFileResponse = await response.json();

            if (result.success && result.data?.id) {
                return result.data.id;
            }

            throw new Error('Invalid response format');
        } catch (err: any) {
            const errorMessage = err.message || 'Upload failed';
            setError(errorMessage);
            console.error('Upload file error:', err);
            return null;
        } finally {
            setUploading(false);
        }
    }, [taskId, token]);

    return { uploadFile, uploading, error };
}

// ============================================
// useUploadFiles Hook (Multiple Files)
// ============================================

/**
 * Hook để upload multiple files cho task (Lecturer)
 * 
 * @param taskId - Task ID
 * @param token - JWT token
 * @returns Upload functions và state
 */
export function useLecturerUploadFiles(
    taskId: number,
    token: string
): UseLecturerUploadFilesReturn {
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const uploadFiles = useCallback(async (files: File[]): Promise<number[]> => {
        setUploading(true);
        setError(null);

        try {
            const formData = new FormData();
            files.forEach(file => {
                formData.append('files[]', file);
            });

            const response = await fetch(
                `${API_BASE_URL}${LECTURER_API_ENDPOINTS.UPLOAD_FILES(taskId)}`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    },
                    body: formData
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Upload failed');
            }

            const result: LecturerUploadFilesResponse = await response.json();

            if (result.success && Array.isArray(result.data)) {
                return result.data.map(file => file.id);
            }

            return [];
        } catch (err: any) {
            const errorMessage = err.message || 'Upload failed';
            setError(errorMessage);
            console.error('Upload files error:', err);
            return [];
        } finally {
            setUploading(false);
        }
    }, [taskId, token]);

    return { uploadFiles, uploading, error };
}

// ============================================
// useCreateTask Hook
// ============================================

/**
 * Hook để tạo task mới (Lecturer)
 * 
 * @param token - JWT token
 * @returns Create functions và state
 */
export function useCreateTask(token: string): UseCreateTaskReturn {
    const [creating, setCreating] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const createTask = useCallback(async (
        taskData: CreateTaskRequest
    ): Promise<CreateTaskResponse | null> => {
        setCreating(true);
        setError(null);

        try {
            if (!taskData.title.trim() || !taskData.description.trim()) {
                throw new Error('Title and description are required');
            }

            const response = await fetch(
                `${API_BASE_URL}${LECTURER_API_ENDPOINTS.CREATE_TASK}`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(taskData)
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Create task failed');
            }

            const result: CreateTaskResponse = await response.json();

            if (result.success) {
                return result;
            }

            throw new Error('Invalid response format');
        } catch (err: any) {
            const errorMessage = err.message || 'Create task failed';
            setError(errorMessage);
            console.error('Create task error:', err);
            return null;
        } finally {
            setCreating(false);
        }
    }, [token]);

    return { createTask, creating, error };
}

// ============================================
// useUpdateTask Hook
// ============================================

/**
 * Hook để cập nhật task (Lecturer)
 * 
 * @param token - JWT token
 * @returns Update functions và state
 */
export function useUpdateTask(token: string): UseUpdateTaskReturn {
    const [updating, setUpdating] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const updateTask = useCallback(async (
        taskId: number,
        taskData: UpdateTaskRequest
    ): Promise<CreateTaskResponse | null> => {
        setUpdating(true);
        setError(null);

        try {
            const response = await fetch(
                `${API_BASE_URL}${LECTURER_API_ENDPOINTS.UPDATE_TASK(taskId)}`,
                {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(taskData)
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Update task failed');
            }

            const result: CreateTaskResponse = await response.json();

            if (result.success) {
                return result;
            }

            throw new Error('Invalid response format');
        } catch (err: any) {
            const errorMessage = err.message || 'Update task failed';
            setError(errorMessage);
            console.error('Update task error:', err);
            return null;
        } finally {
            setUpdating(false);
        }
    }, [token]);

    return { updateTask, updating, error };
}

// ============================================
// useGetLecturerTasks Hook
// ============================================

/**
 * Hook để lấy danh sách tasks của lecturer
 * 
 * @param token - JWT token
 * @param filters - Filters (status, priority, etc.)
 * @param autoFetch - Tự động fetch khi mount (default: true)
 * @returns Tasks data và functions
 */
export function useGetLecturerTasks(
    token: string,
    filters: Record<string, any> = {},
    autoFetch: boolean = true
): UseGetLecturerTasksReturn {
    const [tasks, setTasks] = useState<any[]>([]);
    const [loading, setLoading] = useState(autoFetch);
    const [error, setError] = useState<string | null>(null);
    const [pagination, setPagination] = useState<GetLecturerTasksResponse['pagination'] | null>(null);

    const fetchTasks = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const queryParams = new URLSearchParams();
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    queryParams.append(key, String(value));
                }
            });

            const url = `${API_BASE_URL}${LECTURER_API_ENDPOINTS.BASE}${queryParams.toString() ? '?' + queryParams.toString() : ''}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Get tasks failed');
            }

            const result: GetLecturerTasksResponse = await response.json();

            if (result.success) {
                setTasks(result.data);
                setPagination(result.pagination);
            } else {
                throw new Error(result.message || 'Get tasks failed');
            }
        } catch (err: any) {
            const errorMessage = err.message || 'Get tasks failed';
            setError(errorMessage);
            console.error('Get tasks error:', err);
            setTasks([]);
            setPagination(null);
        } finally {
            setLoading(false);
        }
    }, [token, JSON.stringify(filters)]);

    // Auto fetch khi mount
    useEffect(() => {
        if (autoFetch) {
            fetchTasks();
        }
    }, [autoFetch, fetchTasks]);

    return {
        tasks,
        loading,
        error,
        pagination,
        refetch: fetchTasks
    };
}

// ============================================
// useDeleteFile Hook
// ============================================

/**
 * Hook để xóa file (Lecturer)
 * 
 * @param taskId - Task ID
 * @param token - JWT token
 * @returns Delete functions và state
 */
export function useLecturerDeleteFile(
    taskId: number,
    token: string
): UseDeleteFileReturn {
    const [deleting, setDeleting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const deleteFile = useCallback(async (fileId: number): Promise<boolean> => {
        setDeleting(true);
        setError(null);

        try {
            const response = await fetch(
                `${API_BASE_URL}${LECTURER_API_ENDPOINTS.DELETE_FILE(taskId, fileId)}`,
                {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Delete failed');
            }

            const result: DeleteFileResponse = await response.json();
            return result.success === true;
        } catch (err: any) {
            const errorMessage = err.message || 'Delete failed';
            setError(errorMessage);
            console.error('Delete file error:', err);
            return false;
        } finally {
            setDeleting(false);
        }
    }, [taskId, token]);

    return { deleteFile, deleting, error };
}

// ============================================
// useAssignTask Hook
// ============================================

/**
 * Hook để giao task cho sinh viên (Lecturer)
 * 
 * @param token - JWT token
 * @returns Assign functions và state
 */
export function useAssignTask(token: string): UseAssignTaskReturn {
    const [assigning, setAssigning] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const assignTask = useCallback(async (
        taskId: number,
        data: AssignTaskRequest
    ): Promise<AssignTaskResponse | null> => {
        setAssigning(true);
        setError(null);

        try {
            const response = await fetch(
                `${API_BASE_URL}${LECTURER_API_ENDPOINTS.ASSIGN_TASK(taskId)}`,
                {
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Assign task failed');
            }

            const result: AssignTaskResponse = await response.json();

            if (result.success) {
                return result;
            }

            throw new Error('Invalid response format');
        } catch (err: any) {
            const errorMessage = err.message || 'Assign task failed';
            setError(errorMessage);
            console.error('Assign task error:', err);
            return null;
        } finally {
            setAssigning(false);
        }
    }, [token]);

    return { assignTask, assigning, error };
}

// ============================================
// useLecturerTaskManagement Hook (Combined)
// ============================================

/**
 * Combined hook để quản lý toàn bộ task management flow cho lecturer
 * 
 * @param token - JWT token
 * @param taskId - Task ID (optional, for file operations)
 * @returns Tất cả functions và states cần thiết
 */
export function useLecturerTaskManagement(token: string, taskId?: number) {
    const uploadFileHook = taskId ? useLecturerUploadFile(taskId, token) : null;
    const uploadFilesHook = taskId ? useLecturerUploadFiles(taskId, token) : null;
    const createTaskHook = useCreateTask(token);
    const updateTaskHook = useUpdateTask(token);
    const getTasksHook = useGetLecturerTasks(token);
    const deleteFileHook = taskId ? useLecturerDeleteFile(taskId, token) : null;
    const assignTaskHook = useAssignTask(token);

    return {
        // Upload Single File
        uploadFile: uploadFileHook?.uploadFile,
        uploadingFile: uploadFileHook?.uploading,
        uploadFileError: uploadFileHook?.error,

        // Upload Multiple Files
        uploadFiles: uploadFilesHook?.uploadFiles,
        uploadingFiles: uploadFilesHook?.uploading,
        uploadFilesError: uploadFilesHook?.error,

        // Create Task
        createTask: createTaskHook.createTask,
        creating: createTaskHook.creating,
        createError: createTaskHook.error,

        // Update Task
        updateTask: updateTaskHook.updateTask,
        updating: updateTaskHook.updating,
        updateError: updateTaskHook.error,

        // Get Tasks
        tasks: getTasksHook.tasks,
        loadingTasks: getTasksHook.loading,
        tasksError: getTasksHook.error,
        pagination: getTasksHook.pagination,
        refetchTasks: getTasksHook.refetch,

        // Delete File
        deleteFile: deleteFileHook?.deleteFile,
        deleting: deleteFileHook?.deleting,
        deleteError: deleteFileHook?.error,

        // Assign Task
        assignTask: assignTaskHook.assignTask,
        assigning: assignTaskHook.assigning,
        assignError: assignTaskHook.error
    };
}

