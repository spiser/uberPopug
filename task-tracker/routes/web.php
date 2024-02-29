<?php

use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])
    ->get('/dashboard', [TaskController::class, 'list'])->name('dashboard');


Route::get('/sso/login', [SSOController::class, 'login'])
    ->name('sso.login');

Route::get('/sso/callback',[SSOController::class, 'callback'])
    ->name('sso.callback');

Route::get('/sso/user-info',[SSOController::class, 'userInfo'])
    ->name('sso.userInfo');


Route::middleware('auth')->group(function () {
    Route::get('/task', [TaskController::class, 'edit'])->name('task.view');
    Route::get('/task/shuffle', [TaskController::class, 'shuffle'])->name('task.shuffle');
    Route::get('/task/{id}', [TaskController::class, 'edit'])->name('task.view');
    Route::post('/task', [TaskController::class, 'store'])->name('task.store');
    Route::patch('/task/{id}', [TaskController::class, 'update'])->name('task.update');
    Route::patch('/task/{id}/done', [TaskController::class, 'done'])->name('task.done');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
