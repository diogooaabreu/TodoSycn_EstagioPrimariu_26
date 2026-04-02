<?php
/**
 * ============================================================
 * ROTAS: routes/web.php
 * ============================================================
 * Define qual URL vai para qual componente Livewire.
 *
 *  Estas rotas são diferentes das rotas web (routes/web.php):
 *  - Não usam sessões nem cookies
 *  - Usam tokens Sanctum para autenticação
 *  - Devolvem JSON em vez de HTML
 *  - São acedidas pelo Android/iOS em vez do browser
 *
 *  Prefixo automático: /api/...
 *  (configurado no bootstrap/app.php do Laravel)
 *
 * Middlewares usados:
 * - 'auth'  → redireciona para /login se não autenticado
 * - 'guest' → redireciona para / se já autenticado
 *             (impede utilizadores autenticados de aceder ao login)
 * ============================================================
 */

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

// ---- Autenticação (sem token — são para obter o token) ----
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// ---- Rotas protegidas (requerem token válido no cabeçalho) ----
// O middleware 'auth:sanctum' verifica o token Authorization: Bearer TOKEN
Route::middleware('auth:sanctum')->group(function () {

    // Perfil
    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Tarefas — CRUD completo
    Route::get('/tasks',         [TaskController::class, 'index']);   // listar
    Route::post('/tasks',        [TaskController::class, 'store']);   // criar
    Route::get('/tasks/{id}',    [TaskController::class, 'show']);    // detalhe
    Route::patch('/tasks/{id}',  [TaskController::class, 'update']); // actualizar
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']); // eliminar

    // Conclusões
    Route::post('/tasks/{id}/complete',   [TaskController::class, 'complete']);   // marcar feito
    Route::delete('/tasks/{id}/complete', [TaskController::class, 'uncomplete']); // desmarcar

    // Partilhas
    Route::post('/tasks/{id}/share',           [TaskController::class, 'share']);       // partilhar
    Route::delete('/tasks/{id}/share/{userId}', [TaskController::class, 'removeShare']); // remover partilha
});

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
