<?php

namespace Modules\Task\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Task\app\Services\CacheInvalidationService;

/**
 * Cache Controller - Common cache operations
 * Tuân thủ Clean Architecture: Controller chỉ xử lý HTTP requests/responses
 */
class CacheController extends Controller
{
    public function __construct(
        private CacheInvalidationService $cacheInvalidationService
    ) {}

    /**
     * Kiểm tra sức khỏe cache (Common)
     */
    public function getHealth(Request $request): JsonResponse
    {
        try {
            $health = $this->cacheInvalidationService->getHealthStatus();
            
            return response()->json([
                'success' => true,
                'data' => $health,
                'message' => 'Cache health retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving cache health: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate student cache (Common)
     */
    public function invalidateStudentCache(Request $request): JsonResponse
    {
        try {
            $result = $this->cacheInvalidationService->invalidateStudentCache();
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Student cache invalidated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error invalidating student cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate lecturer cache (Common)
     */
    public function invalidateLecturerCache(Request $request): JsonResponse
    {
        try {
            $result = $this->cacheInvalidationService->invalidateLecturerCache();
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Lecturer cache invalidated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error invalidating lecturer cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate department cache (Common)
     */
    public function invalidateDepartmentCache(Request $request): JsonResponse
    {
        try {
            $result = $this->cacheInvalidationService->invalidateDepartmentCache();
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Department cache invalidated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error invalidating department cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate class cache (Common)
     */
    public function invalidateClassCache(Request $request): JsonResponse
    {
        try {
            $result = $this->cacheInvalidationService->invalidateClassCache();
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Class cache invalidated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error invalidating class cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate bulk cache (Common)
     */
    public function invalidateBulkCache(Request $request): JsonResponse
    {
        try {
            $types = $request->input('types', []);
            $result = $this->cacheInvalidationService->invalidateBulkCache($types);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Bulk cache invalidated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error invalidating bulk cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate all cache (Common)
     */
    public function invalidateAllCache(Request $request): JsonResponse
    {
        try {
            $result = $this->cacheInvalidationService->invalidateAllCache();
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'All cache invalidated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error invalidating all cache: ' . $e->getMessage()
            ], 500);
        }
    }
}