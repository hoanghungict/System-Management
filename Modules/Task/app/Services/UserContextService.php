<?php

namespace Modules\Task\app\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Task\app\Exceptions\TaskException;

/**
 * Service: Xử lý User Context từ JWT
 * 
 * Tuân thủ Clean Architecture: Tách biệt logic xử lý user context
 * khỏi Controller và Use Cases
 */
class UserContextService
{
    /**
     * Tạo user object từ JWT attributes
     * 
     * @param Request $request Request hiện tại
     * @return object User object
     * @throws TaskException Nếu không thể tạo user object
     */
    public function createUserFromJwt(Request $request): object
    {
        $userId = $request->attributes->get('jwt_user_id');
        $userType = $request->attributes->get('jwt_user_type');
        
        // Validate JWT attributes
        if (!$userId || !$userType) {
            throw TaskException::businessRuleViolation(
                'User not authenticated',
                ['user_id' => $userId, 'user_type' => $userType]
            );
        }
        
        // Kiểm tra xem user có phải admin không
        $isAdmin = $this->checkAdminStatus($userId, $userType);
        
        // Tạo user object
        $user = (object) [
            'id' => $userId,
            'user_type' => $userType,
            'account' => [
                'is_admin' => $isAdmin
            ]
        ];
        
        return $user;
    }
    
    /**
     * Kiểm tra trạng thái admin của user
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @return bool True nếu là admin
     */
    private function checkAdminStatus(int $userId, string $userType): bool
    {
        if ($userType === 'lecturer') {
            $lecturerAccount = DB::table('lecturer_account')
                ->where('lecturer_id', $userId)
                ->first();
            return $lecturerAccount && $lecturerAccount->is_admin;
        }
        
        return false;
    }
    
    /**
     * Validate user permissions
     * 
     * @param object $user User object
     * @param string $requiredPermission Permission cần thiết
     * @throws TaskException Nếu không có quyền
     */
    public function validatePermission(object $user, string $requiredPermission): void
    {
        $userType = $user->user_type ?? 'unknown';
        $isAdmin = $user->account['is_admin'] ?? false;
        
        switch ($requiredPermission) {
            case 'admin_only':
                if ($userType !== 'admin' && !$isAdmin) {
                    throw TaskException::accessDenied(
                        'Admin permission required',
                        ['user_type' => $userType, 'is_admin' => $isAdmin]
                    );
                }
                break;
                
            case 'lecturer_or_admin':
                if (!in_array($userType, ['lecturer', 'admin']) && !$isAdmin) {
                    throw TaskException::accessDenied(
                        'Lecturer or admin permission required',
                        ['user_type' => $userType, 'is_admin' => $isAdmin]
                    );
                }
                break;
                
            default:
                throw TaskException::businessRuleViolation(
                    'Unknown permission type',
                    ['permission' => $requiredPermission]
                );
        }
    }
}
