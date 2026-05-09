<?php

namespace App\Providers;

use App\Models\Project;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
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

        View::composer('layouts.app', function (\Illuminate\View\View $view): void {
            if (! Auth::check()) {
                $view->with([
                    'sidebarPurchaseProjects' => collect(),
                ]);

                return;
            }

            $view->with([
                'sidebarPurchaseProjects' => Project::query()
                    ->where('field_type', 'purchase')
                    ->orderBy('name')
                    ->limit(60)
                    ->get(['id', 'name']),
            ]);
        });
    }
}
