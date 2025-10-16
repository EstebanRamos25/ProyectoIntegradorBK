<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TestingController;

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('testing/smoke', [TestingController::class, 'smoke'])
         ->name('testing.smoke');

    Route::get('testing/testcases', [TestingController::class, 'testCases'])
         ->name('testing.testcases');

    Route::get('testing/integration', [TestingController::class, 'integration'])
         ->name('testing.integration');

    Route::get('testing/locust', [TestingController::class, 'locust'])
         ->name('testing.locust');

     Route::get('testing/status/{uuid}', [TestingController::class, 'status'])
     ->name('testing.status');

     Route::get('testing/download/{uuid}', [TestingController::class, 'downloadReport'])
     ->name('testing.download');
});


