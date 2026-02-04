<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Task\Providers\TaskServiceProvider::class,
    Modules\Task\app\Providers\TaskRouteServiceProvider::class,
    Modules\Auth\app\Providers\AuthServiceProvider::class,
];
