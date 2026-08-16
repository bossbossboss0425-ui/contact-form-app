<?php

use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\ContactController;
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

Route::get('/', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])->name('contact.confirm');
Route::post('/contacts', [ContactController::class, 'store'])->name('contact.store');
Route::get('/thanks', [ContactController::class, 'thanks'])->name('contact.thanks');
// CSVエクスポート
Route::get('/contacts/export', [AdminContactController::class, 'export'])->name('contact.export');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // 管理者公開トップ画面
    Route::get('/', [AdminContactController::class, 'index'])->name('index');

    // お問い合わせ詳細
    Route::get('/contacts/{contact}', [AdminContactController::class, 'show'])->name('contact.show');

    // お問い合わせ削除
    Route::delete('/contacts/{contact}', [AdminContactController::class, 'destroy'])->name('contact.destroy');

    // タグの追加
    Route::post('/tags', [TagController::class, 'store'])->name('tag.store');

    // タグの編集
    Route::get('/tags/{tag}/edit', [TagController::class, 'edit'])->name('tag.edit');

    // タグの更新
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tag.update');

    // タグの削除
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tag.destroy');

});
