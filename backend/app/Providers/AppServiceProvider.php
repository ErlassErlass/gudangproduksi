<?php

namespace App\Providers;

use App\Domains\Administration\Models\Role;
use App\Domains\Administration\Policies\AuditLogPolicy;
use App\Domains\Administration\Policies\RolePolicy;
use App\Domains\Administration\Policies\UserPolicy;
use App\Domains\Inventory\Services\InventoryPostingService;
use App\Models\User;
use App\Shared\Audit\AuditLog;
use App\Shared\Audit\AuditLogger;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogger::class, fn ($app) => new AuditLogger($app->bound('request') ? $app->make('request') : null));
        $this->app->bind(InventoryPostingContract::class, InventoryPostingService::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
