<?php

namespace App\Providers;

use App\Models\BudgetGroup;
use App\Policies\BudgetGroupPolicy;
use App\Services\Budgeting\AIBudgetService;
use App\Services\Budgeting\BudgetingServiceInterface;
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
