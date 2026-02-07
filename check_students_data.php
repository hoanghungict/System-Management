<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Force file driver to avoid Redis issues in CLI
$_ENV['CACHE_DRIVER'] = 'file';
$_ENV['SESSION_DRIVER'] = 'file';
$_ENV['QUEUE_CONNECTION'] = 'sync';
putenv('CACHE_DRIVER=file');
putenv('SESSION_DRIVER=file');
putenv('QUEUE_CONNECTION=sync');

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$classes = DB::table('classrooms')->get(['id', 'name']);
echo "CLASSES:\n";
foreach ($classes as $class) {
    $count = DB::table('students')->where('class_id', $class->id)->count();
    echo "ID: {$class->id} - Name: {$class->name} - Students: {$count}\n";
}

$studentsWithoutClass = DB::table('students')->whereNull('class_id')->count();
echo "\nStudents without class: {$studentsWithoutClass}\n";
