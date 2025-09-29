<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminApiV1Controller;
use App\Http\Controllers\Api\ApiV1Controller;
use App\Http\Middleware\CheckIsAdmin;

Route::prefix('v1')->middleware('throttle:api-custom')->group(function () {
    // frontend routes
    Route::controller(ApiV1Controller::class)->group(function () {
        Route::get('/header-data',  'headerData');
        Route::get('/footer-data',  'footerData');
        Route::get('/home-data',  'homeData');
        Route::get('/products',  'products');
        Route::get('/products/{id}',  'productDetails');
        Route::post('/send-message',  'sendMessage');
    });

    // admin routes
    Route::controller(AdminApiV1Controller::class)->prefix('admin')->group(function () {
        Route::post('/login', 'login');
        Route::post('/send-reset-password-link', 'sendResetPasswordLink');
        Route::post('/contact-us', 'contactUs');

        Route::middleware(['auth:sanctum', CheckIsAdmin::class])->group(function(){
            Route::post('/logout', 'logout');
            Route::get('/dashboard', 'dashboard');
            Route::post('/reset-password', 'resetPassword');
            Route::post('/change-password', 'changePassword');
            Route::get('/categories', 'categories');
            Route::post('/add-category', 'AddCategory');
            Route::get('/categories/{id}', 'categoryDetails');
            Route::post('/update-category/{id}', 'updateCategory');
            Route::post('/delete-category/{id}', 'deleteCategory');
            Route::get('/products', 'products');
            Route::post('/add-product', 'AddProduct');
            Route::get('/products/{id}', 'productDetails');
            Route::post('/update-product/{id}', 'updateProduct');
            Route::post('/delete-product/{id}', 'deleteProduct');
            Route::get('/messages', 'messages');
            Route::post('/delete-message', 'deleteMessage');
            Route::get('/settings', 'settings');
            Route::post('/update-settings', 'updateSettings');
            Route::get('/get-slider-list', 'sliderList');
            Route::post('/save-slider-image', 'saveSliderImage');
            Route::post('/delete-slider-image/{id}', 'deleteSliderImage');
        });
    });
});