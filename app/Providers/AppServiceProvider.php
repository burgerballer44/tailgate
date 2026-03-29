<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\UserCommandInterface;
use App\Services\Contracts\UserQueryInterface;
use App\Services\UserCommandService;
use App\Services\UserQueryService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserCommandInterface::class, UserCommandService::class);
        $this->app->bind(UserQueryInterface::class, UserQueryService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('renderTableData', function ($expression) {
            return "<?php echo is_callable($expression) ? $expression(\$row) : e(data_get(\$row, $expression)); ?>";
        });
    }
}
