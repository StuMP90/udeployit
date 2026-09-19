<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage-users'])->group(function () {
    Route::livewire('users', 'pages::users.index')->name('users.index');
    Route::livewire('users/create', 'pages::users.create')->name('users.create');
    Route::livewire('users/{user}/edit', 'pages::users.edit')->name('users.edit');
});
