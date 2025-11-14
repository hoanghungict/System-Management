<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure polymorphic relationships
        Relation::morphMap([
            'lecturer' => \Modules\Auth\app\Models\Lecturer::class,
            'student' => \Modules\Auth\app\Models\Student::class,
            'admin' => \App\Models\User::class,
        ]);
        
        // Force UTF-8 encoding
        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');
        mb_regex_encoding('UTF-8');
        
        // Set default JSON encoding options
        if (function_exists('json_encode')) {
            ini_set('default_charset', 'UTF-8');
        }
    }
}
