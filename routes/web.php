<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DayBookController;
use App\Http\Controllers\FactoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LandController;
use App\Http\Controllers\LandTypeController;
use App\Http\Controllers\PartyCategoryController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PartySubCategoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('users.index') : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::get('sale', [ProjectController::class, 'saleIndex'])->name('sale.index');
    Route::get('purchase', [ProjectController::class, 'purchaseIndex'])->name('purchase.index');
    Route::post('projects/quick-store', [ProjectController::class, 'quickStore'])->name('projects.quick-store');
    Route::post('parties/quick-store', [PartyController::class, 'quickStore'])->name('parties.quick-store');
    Route::get('projects/{project}/ledger-pdf', [ProjectController::class, 'ledgerPdf'])->name('projects.ledger.pdf');
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/files', [ProjectController::class, 'addFile'])->name('projects.files.store');
    Route::put('projects/{project}/files/{projectFile}/sell', [ProjectController::class, 'sellFile'])->name('projects.files.sell');
    Route::post('projects/{project}/files/{projectFile}/documents', [ProjectController::class, 'uploadFileDocument'])->name('projects.files.documents.store');
    Route::delete('projects/{project}/files/{projectFile}/documents/{document}', [ProjectController::class, 'destroyFileDocument'])->name('projects.files.documents.destroy');
    Route::resource('lands', LandController::class);
    Route::post('lands/{land}/plots', [LandController::class, 'addPlot'])->name('lands.plots.store');
    Route::put('lands/{land}/plots/{plot}/sell', [LandController::class, 'sellPlot'])->name('lands.plots.sell');
    Route::post('lands/{land}/plots/{plot}/documents', [LandController::class, 'uploadPlotDocument'])->name('lands.plots.documents.store');
    Route::delete('lands/{land}/plots/{plot}/documents/{document}', [LandController::class, 'destroyPlotDocument'])->name('lands.plots.documents.destroy');
    Route::resource('factories', FactoryController::class);
    Route::get('daybook', [DayBookController::class, 'index'])->name('daybook.index');
    Route::get('daybook/ledger', [DayBookController::class, 'ledger'])->name('daybook.ledger');
    Route::get('daybook/ledger/pdf', [DayBookController::class, 'ledgerPdf'])->name('daybook.ledger.pdf');
    Route::get('daybook/report/pdf', [DayBookController::class, 'reportPdf'])->name('daybook.report.pdf');
    Route::post('daybook/petty-cash', [DayBookController::class, 'updatePettyCash'])->name('daybook.petty-cash');
    Route::get('daybook/create', [DayBookController::class, 'create'])->name('daybook.create');
    Route::post('daybook', [DayBookController::class, 'store'])->name('daybook.store');
    Route::get('daybook/{entry}', [DayBookController::class, 'show'])->name('daybook.show');
    Route::get('daybook/{entry}/edit', [DayBookController::class, 'edit'])->name('daybook.edit');
    Route::put('daybook/{entry}', [DayBookController::class, 'update'])->name('daybook.update');
    Route::delete('daybook/{entry}', [DayBookController::class, 'destroy'])->name('daybook.destroy');
    Route::resource('jobs', JobController::class);
    Route::resource('party-categories', PartyCategoryController::class)->except(['show']);
    Route::resource('party-sub-categories', PartySubCategoryController::class)->except(['show']);
    Route::resource('land-types', LandTypeController::class)->except(['show']);
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
