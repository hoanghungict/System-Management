<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('encoding:check', function () {
    $this->info('Checking encoding settings...');
    
    // Check PHP encoding
    $this->info('PHP internal encoding: ' . mb_internal_encoding());
    $this->info('PHP HTTP output encoding: ' . mb_http_output());
    $this->info('PHP default charset: ' . ini_get('default_charset'));
    
    // Check database connection
    try {
        $pdo = DB::connection()->getPdo();
        $result = $pdo->query("SHOW VARIABLES LIKE 'character_set%'")->fetchAll();
        
        $this->info('Database character sets:');
        foreach ($result as $row) {
            $this->info("  {$row[0]}: {$row[1]}");
        }
        
        $collationResult = $pdo->query("SHOW VARIABLES LIKE 'collation%'")->fetchAll();
        $this->info('Database collations:');
        foreach ($collationResult as $row) {
            $this->info("  {$row[0]}: {$row[1]}");
        }
        
    } catch (Exception $e) {
        $this->error('Database connection error: ' . $e->getMessage());
    }
    
})->purpose('Check encoding settings for PHP and database');