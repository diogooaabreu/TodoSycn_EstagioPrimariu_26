<?php

use App\Livewire\LoginForm;
use App\Livewire\RegisterForm;
use App\Livewire\TodoDetail;
use App\Livewire\TodoEdit;
use App\Livewire\TodoList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
