<?php

namespace App\Providers;

use App\Models\BudgetGroup;
use App\Policies\BudgetGroupPolicy;
use App\Services\Budgeting\AIBudgetService;
use App\Services\Budgeting\BudgetingServiceInterface;
use App\Support\Inertia\MaterialAwareViewFinder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        BudgetGroup::class => BudgetGroupPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BudgetingServiceInterface::class, AIBudgetService::class);

        // Multi-UI: finder Inertia yang mengenali halaman `Material/*`
        // di resources/js/Material/Pages/.
        $this->app->bind('inertia.view-finder', function ($app) {
            return new MaterialAwareViewFinder(
                $app['files'],
                $app['config']->get('inertia.pages.paths'),
                $app['config']->get('inertia.pages.extensions')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
