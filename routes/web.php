<?php

use App\Livewire\LoginForm;
use App\Livewire\RegisterForm;
use App\Livewire\TodoDetail;
use App\Livewire\TodoEdit;
use App\Livewire\TodoList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;

// Lista principal
Route::get('/', TodoList::class)->middleware('auth');

// Detalhe do toodo
Route::get('/todo/{id}', TodoDetail::class)->middleware('auth');

// Edição do toodo
Route::get('/todo/{id}/edit', TodoEdit::class)->middleware('auth');

// Auth
Route::get('/login', LoginForm::class)->name('login')->middleware('guest');
Route::get('/register', RegisterForm::class)->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('logout');


Route::post('/login', [AuthController::class, 'login']);
Route::get('/tasks', [TaskController::class, 'index']);
Route::post('/tasks', [TaskController::class, 'store']);

Route::get('/debug-db', function () {
    $configPath = config_path('database.php');
    $cachedConfigPath = app()->getCachedConfigPath();
    
    return response()->json([
    'app_config_cached' => app()->configurationIsCached(),
    'cached_config_path' => $cachedConfigPath,
    'cached_config_exists' => File::exists($cachedConfigPath),

    'database_php_exists' => File::exists($configPath),
    'database_php_sha1' => File::exists($configPath) ? sha1_file($configPath) : null,
    'database_php_first_500' => File::exists($configPath) ? substr(File::get($configPath), 0, 500) : null,
    
    'config_default' => config('database.default'),
    'env_db_connection' => env('DB_CONNECTION'),
    'mysql_config' => config('database.connections.mysql'),
    'sqlite_config' => config('database.connections.sqlite'),
    
    'loaded_providers_count' => count(app()->getLoadedProviders()),
    ]);
    });