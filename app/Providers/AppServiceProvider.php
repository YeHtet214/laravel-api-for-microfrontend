<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

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
        // Ensure SQLite database file exists in production
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
        }
    }
}
