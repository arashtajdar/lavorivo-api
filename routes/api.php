<?php
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateDayController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// Templates
Route::apiResource('templates', TemplateController::class);

// Template Days (if needed separately, otherwise handled in TemplateController)
Route::apiResource('template-days', TemplateDayController::class);

// Shifts
Route::apiResource('shifts', ShiftController::class);

// Schedules
Route::apiResource('schedules', ScheduleController::class);

// Shops
Route::apiResource('shops', ShopController::class);
