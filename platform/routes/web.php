<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProposalPdfController;
use App\Livewire\Admin;
use App\Livewire\Client\ProposalViewer;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// ---------------------------------------------------------------- auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------------------------------------------------------------- admin panel
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/proposals')->name('home');

    Route::livewire('/proposals', Admin\Proposals\Index::class)->name('proposals.index');
    Route::livewire('/proposals/create', Admin\Proposals\Form::class)->name('proposals.create');
    Route::livewire('/proposals/{proposal}/edit', Admin\Proposals\Form::class)->name('proposals.edit');
    Route::get('/proposals/{proposal}/pdf', [ProposalPdfController::class, 'admin'])->name('proposals.pdf');
    Route::get('/export/proposals.csv', [ExportController::class, 'proposals'])->name('proposals.export');
    Route::get('/export/clients.csv', [ExportController::class, 'clients'])->name('clients.export');

    Route::livewire('/profile', Admin\Profile::class)->name('profile');
    Route::livewire('/clients', Admin\Clients::class)->name('clients');
    Route::livewire('/services', Admin\Services::class)->name('services');
    Route::livewire('/case-studies', Admin\CaseStudies::class)->name('case-studies');
    Route::livewire('/templates', Admin\Templates::class)->name('templates');
    Route::livewire('/templates/create', Admin\Templates\Edit::class)->name('templates.create');
    Route::livewire('/templates/combine', Admin\Templates\Combine::class)->name('templates.combine');
    Route::livewire('/templates/{template}/edit', Admin\Templates\Edit::class)->name('templates.edit');
    Route::livewire('/settings', Admin\Settings::class)->name('settings');
});

// ---------------------------------------------------------------- public client viewer (unlisted)
Route::middleware('throttle:120,1')->group(function () {
    Route::livewire('/proposal/{slug}', ProposalViewer::class)->name('proposal.show');
    Route::get('/proposal/{slug}/pdf', [ProposalPdfController::class, 'client'])->name('proposal.pdf');
});
