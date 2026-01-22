<?php

declare(strict_types=1);

namespace Modules\Auth\app\Http\Controllers\ImportExcelController;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Modules\Auth\app\Models\ImportJob;
use Modules\Auth\app\Http\Requests\ImportStudentRequest;
use Modules\Auth\app\Http\Requests\ImportLecturerRequest;
use Modules\Auth\app\Jobs\AddListStudent;
use Modules\Auth\app\Jobs\AddListLecturer;

class ImportDataExcelController extends Controller
{
    /**
     * Upload file và tạo import job cho sinh viên
     */
    public function ImportStudent(ImportStudentRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $userId = auth()->id();
            
            // Store file
            try{
                $filePath = $file->store('imports', 'local');
                // Resolve actual full path using Storage to avoid mismatches (local disk root may be storage/app/private)
                $fullPath = Storage::disk('local')->path($filePath);
            
            } catch (\Exception $e) {
                Log::channel('daily')->error('File upload failed', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Tải lên file thất bại',
                    'error' => $e->getMessage()
                ], 500);
            }
            // Create import job
            $importJob = ImportJob::create([
                'user_id' => $userId,
                'entity_type' => 'student',
                'file_path' => $fullPath,
                'status' => 'pending',
                'total' => 0,
                'processed_rows' => 0,
                'success' => 0,
                'failed' => 0,
            ]);
            
            // Dispatch job
            AddListStudent::dispatch($importJob->id);
            
            Log::channel('daily')->info('Student import job created', [
                'import_job_id' => $importJob->id,
                'user_id' => $userId,
                'file_path' => $filePath
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'File đã được nhận và đang được xử lý',
                'data' => [
                    'import_job_id' => $importJob->id,
                    'status' => $importJob->status,
                    'file_path' => $filePath
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error creating student import job', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo import job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file và tạo import job cho giảng viên
     */
    public function ImportLecturer(ImportLecturerRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $userId = auth()->id();
            
            // Store file
            try {
                $filePath = $file->store('imports', 'local');
                $fullPath = Storage::disk('local')->path($filePath);
            
            } catch (\Exception $e) {
                Log::channel('daily')->error('Lecturer file upload failed', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Tải lên file thất bại',
                    'error' => $e->getMessage()
                ], 500);
            }

            // Create import job
            $importJob = ImportJob::create([
                'user_id' => $userId,
                'entity_type' => 'lecturer',
                'file_path' => $fullPath,
                'status' => 'pending',
                'total' => 0,
                'processed_rows' => 0,
                'success' => 0,
                'failed' => 0,
            ]);
            
            // Dispatch job
            AddListLecturer::dispatch($importJob->id);
            
            Log::channel('daily')->info('Lecturer import job created', [
                'import_job_id' => $importJob->id,
                'user_id' => $userId,
                'file_path' => $filePath
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'File đã được nhận và đang được xử lý',
                'data' => [
                    'import_job_id' => $importJob->id,
                    'status' => $importJob->status,
                    'file_path' => $filePath
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error creating lecturer import job', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo import job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tiến trình import (lightweight, cho polling)
     */
    public function getProgress(int $id): JsonResponse
    {
        try {
            $importJob = ImportJob::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'import_job_id' => $importJob->id,
                    'status' => $importJob->status,
                    'total' => $importJob->total,
                    'processed' => $importJob->processed_rows,
                    'success' => $importJob->success,
                    'failed' => $importJob->failed,
                    'percent' => $importJob->getProgressPercent(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy import job',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Lấy chi tiết import job + failures
     */
    public function show(int $id): JsonResponse
    {
        try {
            $importJob = ImportJob::with(['failures'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $importJob->id,
                    'user_id' => $importJob->user_id,
                    'entity_type' => $importJob->entity_type,
                    'file_path' => $importJob->file_path,
                    'status' => $importJob->status,
                    'total' => $importJob->total,
                    'processed_rows' => $importJob->processed_rows,
                    'success' => $importJob->success,
                    'failed' => $importJob->failed,
                    'error' => $importJob->error,
                    'created_at' => $importJob->created_at,
                    'updated_at' => $importJob->updated_at,
                    'failures' => $importJob->failures,
                    'summary' => [
                        'total_rows' => $importJob->total,
                        'success_count' => $importJob->success,
                        'failed_count' => $importJob->failed,
                        'success_rate' => $importJob->total > 0 
                            ? round(($importJob->success / $importJob->total) * 100, 2) 
                            : 0,
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy import job',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

