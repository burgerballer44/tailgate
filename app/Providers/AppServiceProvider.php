<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\UserCommandInterface;
use App\Services\Contracts\UserQueryInterface;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamQueryInterface;
use App\Services\Contracts\SeasonCommandInterface;
use App\Services\Contracts\SeasonQueryInterface;
use App\Services\Contracts\GameCommandInterface;
use App\Services\Contracts\GameQueryInterface;
use App\Services\Contracts\PlayerCommandInterface;
use App\Services\Contracts\PlayerQueryInterface;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\MemberQueryInterface;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\GroupQueryInterface;
use App\Services\UserCommandService;
use App\Services\UserQueryService;
use App\Services\TeamCommandService;
use App\Services\TeamQueryService;
use App\Services\SeasonCommandService;
use App\Services\SeasonQueryService;
use App\Services\GameCommandService;
use App\Services\GameQueryService;
use App\Services\PlayerCommandService;
use App\Services\PlayerQueryService;
use App\Services\MemberCommandService;
use App\Services\MemberQueryService;
use App\Services\GroupCommandService;
use App\Services\GroupQueryService;

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
        $this->app->bind(SeasonCommandInterface::class, SeasonCommandService::class);
        $this->app->bind(SeasonQueryInterface::class, SeasonQueryService::class);
        $this->app->bind(GameCommandInterface::class, GameCommandService::class);
        $this->app->bind(GameQueryInterface::class, GameQueryService::class);
        $this->app->bind(PlayerCommandInterface::class, PlayerCommandService::class);
        $this->app->bind(PlayerQueryInterface::class, PlayerQueryService::class);
        $this->app->bind(MemberCommandInterface::class, MemberCommandService::class);
        $this->app->bind(MemberQueryInterface::class, MemberQueryService::class);
        $this->app->bind(GroupCommandInterface::class, GroupCommandService::class);
        $this->app->bind(GroupQueryInterface::class, GroupQueryService::class);
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
