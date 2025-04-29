<?php

use App\Http\Controllers\Frontend\LandingsPageController;
use Illuminate\Support\Facades\Route;

Route::get('/landings', [LandingsPageController::class, 'index'])->name('landings.index');
Route::delete('/landings/{landing}', [LandingsPageController::class, 'destroy'])->name('landings.destroy');
Route::get('/landings/{landing}/download', [LandingsPageController::class, 'download'])->name('landings.download');
