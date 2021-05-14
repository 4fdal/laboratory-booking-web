<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BorrowerScheduleController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\LaboratoryBookingController;
use App\Http\Controllers\LaboratoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('me', [AuthController::class, 'me']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('forget_password', [AuthController::class, 'forgetPassword']);
        Route::post('reset_password', [AuthController::class, 'resetPassword']);
    });

    Route::group(['middleware' => 'auth:api-jwt'], function () {
        Route::group(['group' => 'profile'], function () {
            Route::get('/', [ProfileController::class, 'getProfile']);
            Route::put('/edit', [ProfileController::class, 'editProfile']);
        });

        Route::group(['prefix' => 'laboratory', 'middleware' => 'auth:api-jwt'], function () {
            Route::get('/', [LaboratoryController::class, 'getUsesLaboratory']);
            Route::group(['prefix' => 'booking'], function () {
                Route::get('/', [LaboratoryBookingController::class, 'getLaboratoryBooking']);
                Route::post('/plea_submission', [LaboratoryBookingController::class, 'pleaSubmission']);
            });
            Route::group(['prefix' => 'borrower_schedule'], function () {
                Route::get('/', [BorrowerScheduleController::class, 'getSchedule']);
            });
        });

        Route::group(['prefix' => 'inbox'], function () {
            Route::get('/', [InboxController::class, 'getInbox']);
        });
    });
});
