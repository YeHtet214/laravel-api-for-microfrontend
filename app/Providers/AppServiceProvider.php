<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

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
        // Ensure SQLite database file exists and migrations run in production
        if (config('database.default') === 'sqlite') {
            $databasePath = config('database.connections.sqlite.database');
            
            if ($databasePath && !File::exists($databasePath)) {
                // Create directory if it doesn't exist
                $directory = dirname($databasePath);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                
                // Create empty database file
                File::put($databasePath, '');
            }
            
            // Run migrations if database is empty (no tables exist)
            if (!Schema::hasTable('users')) {
                try {
                    Artisan::call('migrate', ['--force' => true]);
                    Artisan::call('db:seed', ['--force' => true]);
                } catch (\Exception $e) {
                    // Log error but don't break the application
                    logger()->error('Failed to run migrations/seeds: ' . $e->getMessage());
                }
            }
        }
    }
}
