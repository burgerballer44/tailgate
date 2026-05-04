<?php

namespace App\Providers;

use App\Clients\CBBDApiClient;
use App\Clients\CFBDApiClient;
use App\Services\Contracts\GameCommandInterface;
use App\Services\Contracts\GameImportManagerInterface;
use App\Services\Contracts\GameQueryInterface;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\GroupQueryInterface;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\MemberQueryInterface;
use App\Services\Contracts\PlayerCommandInterface;
use App\Services\Contracts\PlayerQueryInterface;
use App\Services\Contracts\SeasonCommandInterface;
use App\Services\Contracts\SeasonQueryInterface;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamImportManagerInterface;
use App\Services\Contracts\TeamQueryInterface;
use App\Services\Contracts\UserCommandInterface;
use App\Services\Contracts\UserQueryInterface;
use App\Services\GameCommandService;
use App\Services\GameImportManager;
use App\Services\GameQueryService;
use App\Services\GroupCommandService;
use App\Services\GroupQueryService;
use App\Services\ImportSources\CBBDGameImportSource;
use App\Services\ImportSources\CBBDTeamImportSource;
use App\Services\ImportSources\CFBDGameImportSource;
use App\Services\ImportSources\CFBDTeamImportSource;
use App\Services\MemberCommandService;
use App\Services\MemberQueryService;
use App\Services\PlayerCommandService;
use App\Services\PlayerQueryService;
use App\Services\SeasonCommandService;
use App\Services\SeasonQueryService;
use App\Services\TeamCommandService;
use App\Services\TeamImportManager;
use App\Services\TeamQueryService;
use App\Services\UserCommandService;
use App\Services\UserQueryService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // register the CFBD API client as a singleton
        $this->app->singleton(CFBDApiClient::class, fn (): CFBDApiClient => new CFBDApiClient(
            token: config('services.import.cfbd.token'),
            baseUrl: config('services.import.cfbd.base_url'),
        ));

        // register the CBBD API client as a singleton
        $this->app->singleton(CBBDApiClient::class, fn (): CBBDApiClient => new CBBDApiClient(
            token: config('services.import.cbbd.token'),
            baseUrl: config('services.import.cbbd.base_url'),
        ));

        // register the game import managers, injecting the appropriate import sources
        $this->app->bind(GameImportManagerInterface::class, function ($app): GameImportManager {
            return new GameImportManager(
                seasonCommandService: $app->make(SeasonCommandInterface::class),
                gameCommandService: $app->make(GameCommandInterface::class),
                sources: [
                    $app->make(CFBDGameImportSource::class),
                    $app->make(CBBDGameImportSource::class),
                ],
            );
        });

        // register the team import managers, injecting the appropriate import sources
        $this->app->bind(TeamImportManagerInterface::class, function ($app): TeamImportManager {
            return new TeamImportManager(
                teamCommandService: $app->make(TeamCommandInterface::class),
                sources: [
                    $app->make(CFBDTeamImportSource::class),
                    $app->make(CBBDTeamImportSource::class),
                ],
            );
        });

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
