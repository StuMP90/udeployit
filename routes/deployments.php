<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('deployments/{deployment}', 'pages::deployments.show')->name('deployments.show');
});
