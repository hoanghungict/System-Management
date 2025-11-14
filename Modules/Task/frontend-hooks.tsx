/**
 * React Hooks cho Student Task Submission
 * Copy file này vào frontend project để sử dụng
 * 
 * Dependencies: React, React Hooks
 */

import React, { useState, useCallback, useEffect } from 'react';
import {
    UploadFileResponse,
    SubmitTaskResponse,
    GetSubmissionResponse,
    DeleteFileResponse,
    UseUploadFileReturn,
    UseSubmitTaskReturn,
    UseGetSubmissionReturn,
    UseDeleteFileReturn,
    API_BASE_URL,
    API_ENDPOINTS
} from './frontend-types';

// ============================================
// useUploadFile Hook
// ============================================

/**
 * Hook để upload file cho task
 * 
 * @param taskId - Task ID
 * @param token - JWT token
 * @returns Upload functions và state
 */
export function useUploadFile(
    taskId: number,
    token: string
): UseUploadFileReturn {
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const uploadFile = useCallback(async (file: File): Promise<number | null> => {
        setUploading(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch(
                `${API_BASE_URL}${API_ENDPOINTS.UPLOAD_FILE(taskId)}`,
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

            const result: UploadFileResponse = await response.json();

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
// useSubmitTask Hook
// ============================================

/**
 * Hook để submit task
 * 
 * @param taskId - Task ID
 * @param token - JWT token
 * @returns Submit functions và state
 */
export function useSubmitTask(
    taskId: number,
    token: string
): UseSubmitTaskReturn {
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const submitTask = useCallback(async (
        content: string,
        fileIds: number[] = [],
        notes?: string
    ): Promise<SubmitTaskResponse | null> => {
        setSubmitting(true);
        setError(null);

        try {
            if (!content.trim()) {
                throw new Error('Nội dung bài nộp là bắt buộc');
            }

            const response = await fetch(
                `${API_BASE_URL}${API_ENDPOINTS.SUBMIT_TASK(taskId)}`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        content,
                        files: fileIds,
                        notes
                    })
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Submit failed');
            }

            const result: SubmitTaskResponse = await response.json();

            if (result.success) {
                return result;
            }

            throw new Error('Invalid response format');
        } catch (err: any) {
            const errorMessage = err.message || 'Submit failed';
            setError(errorMessage);
            console.error('Submit task error:', err);
            return null;
        } finally {
            setSubmitting(false);
        }
    }, [taskId, token]);

    return { submitTask, submitting, error };
}

// ============================================
// useGetSubmission Hook
// ============================================

/**
 * Hook để lấy submission của task
 * 
 * @param taskId - Task ID
 * @param token - JWT token
 * @param autoFetch - Tự động fetch khi mount (default: true)
 * @returns Submission data và functions
 */
export function useGetSubmission(
    taskId: number,
    token: string,
    autoFetch: boolean = true
): UseGetSubmissionReturn {
    const [submission, setSubmission] = useState<GetSubmissionResponse['data'] | null>(null);
    const [loading, setLoading] = useState(autoFetch);
    const [error, setError] = useState<string | null>(null);

    const fetchSubmission = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(
                `${API_BASE_URL}${API_ENDPOINTS.GET_SUBMISSION(taskId)}`,
                {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                }
            );

            if (response.status === 404) {
                // Chưa có submission - không phải lỗi
                setSubmission(null);
                return;
            }

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Get submission failed');
            }

            const result: GetSubmissionResponse = await response.json();

            if (result.success) {
                setSubmission(result.data);
            } else {
                throw new Error(result.message || 'Get submission failed');
            }
        } catch (err: any) {
            const errorMessage = err.message || 'Get submission failed';
            setError(errorMessage);
            console.error('Get submission error:', err);
            setSubmission(null);
        } finally {
            setLoading(false);
        }
    }, [taskId, token]);

    // Auto fetch khi mount
    useEffect(() => {
        if (autoFetch) {
            fetchSubmission();
        }
    }, [autoFetch, fetchSubmission]);

    return {
        submission,
        loading,
        error,
        refetch: fetchSubmission
    };
}

// ============================================
// useDeleteFile Hook
// ============================================

/**
 * Hook để xóa file
 * 
 * @param taskId - Task ID
 * @param token - JWT token
 * @returns Delete functions và state
 */
export function useDeleteFile(
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
                `${API_BASE_URL}${API_ENDPOINTS.DELETE_FILE(taskId, fileId)}`,
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
// useTaskSubmission Hook (Combined)
// ============================================

/**
 * Combined hook để quản lý toàn bộ submission flow
 * 
 * @param taskId - Task ID
 * @param token - JWT token
 * @returns Tất cả functions và states cần thiết
 */
export function useTaskSubmission(taskId: number, token: string) {
    const uploadFileHook = useUploadFile(taskId, token);
    const submitTaskHook = useSubmitTask(taskId, token);
    const getSubmissionHook = useGetSubmission(taskId, token);
    const deleteFileHook = useDeleteFile(taskId, token);

    return {
        // Upload
        uploadFile: uploadFileHook.uploadFile,
        uploading: uploadFileHook.uploading,
        uploadError: uploadFileHook.error,

        // Submit
        submitTask: submitTaskHook.submitTask,
        submitting: submitTaskHook.submitting,
        submitError: submitTaskHook.error,

        // Get Submission
        submission: getSubmissionHook.submission,
        loadingSubmission: getSubmissionHook.loading,
        submissionError: getSubmissionHook.error,
        refetchSubmission: getSubmissionHook.refetch,

        // Delete File
        deleteFile: deleteFileHook.deleteFile,
        deleting: deleteFileHook.deleting,
        deleteError: deleteFileHook.error
    };
}

