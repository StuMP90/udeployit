<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('projects', 'pages::projects.index')->name('projects.index');

    Route::livewire('projects/create', 'pages::projects.create')
        ->middleware('can:manage-projects')
        ->name('projects.create');

    Route::livewire('projects/{project}', 'pages::projects.show')->name('projects.show');

    Route::livewire('projects/{project}/deployments', 'pages::projects.deployments')->name('projects.deployments');
});
