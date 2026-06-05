<?php

use Illuminate\Support\Facades\Route;
use NexusPlugin\LuckyWheel\Http\Controllers\LuckyWheelController;

Route::group([
    'prefix' => 'web/lucky-wheel',
    'middleware' => ['web', 'auth.nexus:nexus-web'],
], function () {
    Route::get('/', [LuckyWheelController::class, 'index'])->name('lucky-wheel.index');
    Route::post('/spin', [LuckyWheelController::class, 'spin'])->name('lucky-wheel.spin');
});
