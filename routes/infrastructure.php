<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage-infrastructure'])->group(function () {
    Route::livewire('github-credentials', 'pages::github-credentials.index')->name('github-credentials.index');
    Route::livewire('github-credentials/create', 'pages::github-credentials.create')->name('github-credentials.create');
    Route::livewire('github-credentials/{credential}/edit', 'pages::github-credentials.edit')->name('github-credentials.edit');

    Route::livewire('servers', 'pages::servers.index')->name('servers.index');
    Route::livewire('servers/create', 'pages::servers.create')->name('servers.create');
    Route::livewire('servers/{server}/edit', 'pages::servers.edit')->name('servers.edit');

    Route::livewire('templates', 'pages::templates.index')->name('templates.index');
    Route::livewire('templates/create', 'pages::templates.create')->name('templates.create');
    Route::livewire('templates/{template}/edit', 'pages::templates.edit')->name('templates.edit');

    Route::livewire('polling', 'pages::polling.edit')->name('polling.edit');
});
