<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\UserCommandInterface;
use App\Services\Contracts\UserQueryInterface;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamQueryInterface;
use App\Services\UserCommandService;
use App\Services\UserQueryService;
use App\Services\TeamCommandService;
use App\Services\TeamQueryService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserCommandInterface::class, UserCommandService::class);
        $this->app->bind(UserQueryInterface::class, UserQueryService::class);
        $this->app->bind(TeamCommandInterface::class, TeamCommandService::class);
        $this->app->bind(TeamQueryInterface::class, TeamQueryService::class);
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
