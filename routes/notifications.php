<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('notifications', 'pages::notifications.index')->name('notifications.index');
});
