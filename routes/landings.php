<?php

use App\Http\Controllers\App\WebsiteDownloadController;
use App\Http\Controllers\Frontend\LandingsPageController;
use Illuminate\Support\Facades\Route;

Route::get('/landings', [LandingsPageController::class, 'index'])->name('landings.index');
Route::delete('/landings/{landing}', [LandingsPageController::class, 'destroy'])->name('landings.destroy');
Route::get('/landings/{landing}/download', [LandingsPageController::class, 'download'])->name('landings.download');


Route::get('/landings/{landing}/status', [WebsiteDownloadController::class, 'show'])->name('landings.status');

Route::get('/website-downloads', [WebsiteDownloadController::class, 'index'])->name('website-downloads.index');
Route::post('/website-downloads', [WebsiteDownloadController::class, 'store'])->name('website-downloads.store');
Route::get('/website-downloads/{monitor}', [WebsiteDownloadController::class, 'show'])->name('website-downloads.show');
Route::delete('/website-downloads/{monitor}', [WebsiteDownloadController::class, 'destroy'])->name('website-downloads.destroy');
