<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $pdfViews = [
            'daybook.ledger-pdf',
            'daybook.report-pdf',
            'purchases.ledger-pdf',
            'purchases.lines-pdf',
            'purchases.files.payment-sheet-pdf',
            'purchases.files.view-pdf',
            'purchases.files.ledger-pdf',
            'projects.sale-land-pdf',
            'projects.ledger-pdf',
        ];

        View::composer($pdfViews, function ($view) {
            if (! $view->offsetExists('pdfCompany')) {
                $view->with('pdfCompany', Company::forReports());
            }
        });
    }
}
