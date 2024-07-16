<?php

use App\Http\Controllers\PDFController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/admin');
    } else {
        return redirect('/admin/login');
    }
});

Route::get('pdf/{order}', PDFController::class)->name('pdf');
