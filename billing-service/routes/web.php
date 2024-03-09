<?php

use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BillingController;
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
    ->get('/dashboard', [BillingController::class, 'list'])->name('dashboard');


Route::get('/sso/login', [SSOController::class, 'login'])
    ->name('sso.login');

Route::get('/sso/callback',[SSOController::class, 'callback'])
    ->name('sso.callback');

Route::get('/sso/user-info',[SSOController::class, 'userInfo'])
    ->name('sso.userInfo');


Route::middleware('auth')->group(function () {
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
