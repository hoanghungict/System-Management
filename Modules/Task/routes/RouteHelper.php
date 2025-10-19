<?php

namespace Modules\Task\routes;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/**
 * Helper class để quản lý routes trong module Task với JWT và phân quyền
 * 
 * Class này cung cấp các method để đăng ký routes một cách gọn gàng và có tổ chức
 */
class RouteHelper
{
    /**
     * Đăng ký nhóm routes với middleware và phân quyền
     * 
     * @param array $config Cấu hình routes
     * @return void
     */
    public static function registerRoutesGroup(array $config)
    {
        $middleware = $config['middleware'] ?? [];
        $prefix = $config['prefix'] ?? '';

        Route::middleware($middleware)->prefix('api/' . $prefix)->group(function () use ($config) {
            // Đăng ký routes cho Tasks
            if (isset($config['tasks'])) {
                self::registerTaskRoutes($config['tasks']);
            }

            // Đăng ký routes cho Task Dependencies
            if (isset($config['task-dependencies'])) {
                self::registerTaskDependencyRoutes($config['task-dependencies']);
            }

            // Đăng ký routes cho Calendar
            if (isset($config['calendar'])) {
                self::registerCalendarRoutes($config['calendar']);
            }

            // Đăng ký routes cho Cache
            if (isset($config['cache'])) {
                self::registerCacheRoutes($config['cache']);
            }

            // Đăng ký routes cho Monitoring
            if (isset($config['monitoring'])) {
                self::registerMonitoringRoutes($config['monitoring']);
            }

            // Đăng ký routes cho Admin Tasks
            if (isset($config['admin-tasks'])) {
                self::registerAdminTaskRoutes($config['admin-tasks']);
            }

            // Đăng ký routes cho Email
            if (isset($config['email'])) {
                self::registerEmailRoutes($config['email']);
            }

            // Đăng ký routes cho Lecturer Tasks
            if (isset($config['lecturer-tasks'])) {
                self::registerLecturerTaskRoutes($config['lecturer-tasks']);
            }

            // Đăng ký routes cho Lecturer Calendar
            if (isset($config['lecturer-calendar'])) {
                self::registerLecturerCalendarRoutes($config['lecturer-calendar']);
            }

            // Đăng ký routes cho Lecturer Profile
            if (isset($config['lecturer-profile'])) {
                self::registerLecturerProfileRoutes($config['lecturer-profile']);
            }

            // Đăng ký routes cho Lecturer Classes
            if (isset($config['lecturer-classes'])) {
                self::registerLecturerClassRoutes($config['lecturer-classes']);
            }

            // Đăng ký routes cho Student Tasks
            if (isset($config['student-tasks'])) {
                self::registerStudentTaskRoutes($config['student-tasks']);
            }

            // Đăng ký routes cho Student Calendar
            if (isset($config['student-calendar'])) {
                self::registerStudentCalendarRoutes($config['student-calendar']);
            }

            // Đăng ký routes cho Student Profile
            if (isset($config['student-profile'])) {
                self::registerStudentProfileRoutes($config['student-profile']);
            }

            // Đăng ký routes cho Student Class
            if (isset($config['student-class'])) {
                self::registerStudentClassRoutes($config['student-class']);
            }

            // Đăng ký routes cho Statistics
            if (isset($config['statistics'])) {
                self::registerStatisticsRoutes($config['statistics']);
            }

            // Đăng ký routes cho Reports
            if (isset($config['reports'])) {
                self::registerReportsRoutes($config['reports']);
            }

            // Đăng ký routes cho Reminders
            if (isset($config['reminders'])) {
                self::registerRemindersRoutes($config['reminders']);
            }
        });
    }

    /**
     * Đăng ký routes cho Tasks
     * 
     * @param array $taskConfig Cấu hình task routes
     * @return void
     */
    protected static function registerTaskRoutes(array $taskConfig)
    {
        $prefix = $taskConfig['prefix'];
        $controller = $taskConfig['controller'];
        $name = $taskConfig['name'];
        $additionalRoutes = $taskConfig['additional_routes'] ?? [];
        $resourceOnly = $taskConfig['resource_only'] ?? [];
        $resourceActions = $taskConfig['resource_actions'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $additionalRoutes, $resourceOnly, $resourceActions) {
            // Đăng ký các routes bổ sung trước để tránh xung đột với resource routes
            foreach ($additionalRoutes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }

            // Đăng ký resource routes nếu được chỉ định
            if (!empty($resourceOnly)) {
                Route::apiResource('', $controller)
                    ->only($resourceOnly)
                    ->parameters(['' => 'task']);
            }

            if (!empty($resourceActions)) {
                Route::apiResource('', $controller)
                    ->only($resourceActions)
                    ->parameters(['' => 'task']);
            }
        });
    }

    /**
     * Đăng ký routes cho Calendar
     * 
     * @param array $calendarConfig Cấu hình calendar routes
     * @return void
     */
    protected static function registerCalendarRoutes(array $calendarConfig)
    {
        $prefix = $calendarConfig['prefix'];
        $name = $calendarConfig['name'];
        $controller = $calendarConfig['controller'];
        $routes = $calendarConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký resource routes với các routes bổ sung (legacy method)
     * 
     * @param string $prefix Prefix cho routes
     * @param string $controller Controller class
     * @param string $name Tên cho route names
     * @param array $additionalRoutes Các routes bổ sung
     * @return void
     */
    public static function registerResourceRoutes($prefix, $controller, $name, $additionalRoutes = [])
    {
        // Đăng ký các routes bổ sung trước để tránh xung đột với {task}
        foreach ($additionalRoutes as $route) {
            $methods = $route['methods'] ?? ['GET'];
            $uri = trim($route['uri'], '/');
            $action = $route['action'];
            $routeName = $route['name'];

            $fullUri = $prefix . ($uri !== '' ? '/' . $uri : '');
            Route::match($methods, $fullUri, [$controller, $action])->name($name . '.' . $routeName);
        }

        // Đăng ký resource trực tiếp với prefix để tham số có tên hợp lệ (api/tasks/{task})
        Route::apiResource($prefix, $controller)
            ->names($name)
            ->parameters([$prefix => 'task']);
    }

    /**
     * Đăng ký nested routes
     * 
     * @param string $prefix Prefix cho routes
     * @param string $name Tên cho route names
     * @param array $routes Các routes con
     * @return void
     */
    public static function registerNestedRoutes($prefix, $name, $routes)
    {
        Route::prefix($prefix)->name($name . '.')->group(function () use ($routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $controller = $route['controller'];
                $action = $route['action'];
                $routeName = $route['name'];
                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Cache
     * 
     * @param array $cacheConfig Cấu hình cache routes
     * @return void
     */
    protected static function registerCacheRoutes(array $cacheConfig)
    {
        $prefix = $cacheConfig['prefix'];
        $controller = $cacheConfig['controller'];
        $name = $cacheConfig['name'];
        $additionalRoutes = $cacheConfig['additional_routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $additionalRoutes) {
            // Đăng ký additional routes
            foreach ($additionalRoutes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];
                
                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * ✅ Đăng ký routes cho Monitoring
     * 
     * @param array $monitoringConfig Cấu hình monitoring routes
     * @return void
     */
    protected static function registerMonitoringRoutes(array $monitoringConfig)
    {
        $prefix = $monitoringConfig['prefix'];
        $controller = $monitoringConfig['controller'];
        $name = $monitoringConfig['name'];
        $routes = $monitoringConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Admin Tasks
     * 
     * @param array $adminTaskConfig Cấu hình admin task routes
     * @return void
     */
    protected static function registerAdminTaskRoutes(array $adminTaskConfig)
    {
        $prefix = $adminTaskConfig['prefix'];
        $controller = $adminTaskConfig['controller'];
        $name = $adminTaskConfig['name'];
        $routes = $adminTaskConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Email
     * 
     * @param array $emailConfig Cấu hình email routes
     * @return void
     */
    protected static function registerEmailRoutes(array $emailConfig)
    {
        $prefix = $emailConfig['prefix'];
        $controller = $emailConfig['controller'];
        $name = $emailConfig['name'];
        $routes = $emailConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Lecturer Tasks
     * 
     * @param array $lecturerTaskConfig Cấu hình lecturer task routes
     * @return void
     */
    protected static function registerLecturerTaskRoutes(array $lecturerTaskConfig)
    {
        $prefix = $lecturerTaskConfig['prefix'];
        $controller = $lecturerTaskConfig['controller'];
        $name = $lecturerTaskConfig['name'];
        $additionalRoutes = $lecturerTaskConfig['additional_routes'] ?? [];
        $resourceActions = $lecturerTaskConfig['resource_actions'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $additionalRoutes, $resourceActions) {
            // Đăng ký các routes bổ sung trước để tránh xung đột với resource routes
            foreach ($additionalRoutes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }

            // Đăng ký resource routes nếu được chỉ định
            if (!empty($resourceActions)) {
                Route::apiResource('', $controller)
                    ->only($resourceActions)
                    ->parameters(['' => 'task']);
            }
        });
    }

    /**
     * Đăng ký routes cho Lecturer Calendar
     * 
     * @param array $lecturerCalendarConfig Cấu hình lecturer calendar routes
     * @return void
     */
    protected static function registerLecturerCalendarRoutes(array $lecturerCalendarConfig)
    {
        $prefix = $lecturerCalendarConfig['prefix'];
        $controller = $lecturerCalendarConfig['controller'];
        $name = $lecturerCalendarConfig['name'];
        $routes = $lecturerCalendarConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Lecturer Profile
     * 
     * @param array $lecturerProfileConfig Cấu hình lecturer profile routes
     * @return void
     */
    protected static function registerLecturerProfileRoutes(array $lecturerProfileConfig)
    {
        $prefix = $lecturerProfileConfig['prefix'];
        $controller = $lecturerProfileConfig['controller'];
        $name = $lecturerProfileConfig['name'];
        $routes = $lecturerProfileConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Lecturer Classes
     * 
     * @param array $lecturerClassConfig Cấu hình lecturer class routes
     * @return void
     */
    protected static function registerLecturerClassRoutes(array $lecturerClassConfig)
    {
        $prefix = $lecturerClassConfig['prefix'];
        $controller = $lecturerClassConfig['controller'];
        $name = $lecturerClassConfig['name'];
        $routes = $lecturerClassConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Student Tasks
     * 
     * @param array $studentTaskConfig Cấu hình student task routes
     * @return void
     */
    protected static function registerStudentTaskRoutes(array $studentTaskConfig)
    {
        $prefix = $studentTaskConfig['prefix'];
        $controller = $studentTaskConfig['controller'];
        $name = $studentTaskConfig['name'];
        $additionalRoutes = $studentTaskConfig['additional_routes'] ?? [];
        $resourceActions = $studentTaskConfig['resource_actions'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $additionalRoutes, $resourceActions) {
            // Đăng ký các routes bổ sung trước để tránh xung đột với resource routes
            foreach ($additionalRoutes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }

            // Đăng ký resource routes nếu được chỉ định
            if (!empty($resourceActions)) {
                Route::apiResource('', $controller)
                    ->only($resourceActions)
                    ->parameters(['' => 'task']);
            }
        });
    }

    /**
     * Đăng ký routes cho Student Calendar
     * 
     * @param array $studentCalendarConfig Cấu hình student calendar routes
     * @return void
     */
    protected static function registerStudentCalendarRoutes(array $studentCalendarConfig)
    {
        $prefix = $studentCalendarConfig['prefix'];
        $controller = $studentCalendarConfig['controller'];
        $name = $studentCalendarConfig['name'];
        $routes = $studentCalendarConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Student Profile
     * 
     * @param array $studentProfileConfig Cấu hình student profile routes
     * @return void
     */
    protected static function registerStudentProfileRoutes(array $studentProfileConfig)
    {
        $prefix = $studentProfileConfig['prefix'];
        $controller = $studentProfileConfig['controller'];
        $name = $studentProfileConfig['name'];
        $routes = $studentProfileConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Student Class
     * 
     * @param array $studentClassConfig Cấu hình student class routes
     * @return void
     */
    protected static function registerStudentClassRoutes(array $studentClassConfig)
    {
        $prefix = $studentClassConfig['prefix'];
        $controller = $studentClassConfig['controller'];
        $name = $studentClassConfig['name'];
        $routes = $studentClassConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Task Dependencies
     * 
     * @param array $taskDependencyConfig Cấu hình task dependency routes
     * @return void
     */
    protected static function registerTaskDependencyRoutes(array $taskDependencyConfig)
    {
        $prefix = $taskDependencyConfig['prefix'];
        $controller = $taskDependencyConfig['controller'];
        $name = $taskDependencyConfig['name'];
        $additionalRoutes = $taskDependencyConfig['additional_routes'] ?? [];

        // ✅ Debug: Log route registration
        Log::info('Registering TaskDependency routes', [
            'prefix' => $prefix,
            'controller' => $controller,
            'name' => $name,
            'routes_count' => count($additionalRoutes)
        ]);

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $additionalRoutes) {
            foreach ($additionalRoutes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                // ✅ Debug: Log each route
                Log::info('Registering dependency route', [
                    'methods' => $methods,
                    'uri' => $uri,
                    'action' => $action,
                    'routeName' => $routeName,
                    'controller' => $controller
                ]);

                try {
                    Route::match($methods, $uri, [$controller, $action])->name($routeName);
                    Log::info('✅ Route registered successfully', ['routeName' => $routeName]);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to register route', [
                        'routeName' => $routeName,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }

    /**
     * Đăng ký routes cho Statistics
     * 
     * @param array $statisticsConfig Cấu hình statistics routes
     * @return void
     */
    protected static function registerStatisticsRoutes(array $statisticsConfig)
    {
        $prefix = $statisticsConfig['prefix'];
        $controller = $statisticsConfig['controller'];
        $name = $statisticsConfig['name'];
        $routes = $statisticsConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Reports
     * 
     * @param array $reportsConfig Cấu hình reports routes
     * @return void
     */
    protected static function registerReportsRoutes(array $reportsConfig)
    {
        $prefix = $reportsConfig['prefix'];
        $controller = $reportsConfig['controller'];
        $name = $reportsConfig['name'];
        $routes = $reportsConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }

    /**
     * Đăng ký routes cho Reminders
     * 
     * @param array $remindersConfig Cấu hình reminders routes
     * @return void
     */
    protected static function registerRemindersRoutes(array $remindersConfig)
    {
        $prefix = $remindersConfig['prefix'];
        $controller = $remindersConfig['controller'];
        $name = $remindersConfig['name'];
        $routes = $remindersConfig['routes'] ?? [];

        Route::prefix($prefix)->name($name . '.')->group(function () use ($controller, $routes) {
            foreach ($routes as $route) {
                $methods = $route['methods'] ?? ['GET'];
                $uri = $route['uri'];
                $action = $route['action'];
                $routeName = $route['name'];

                Route::match($methods, $uri, [$controller, $action])->name($routeName);
            }
        });
    }
}
