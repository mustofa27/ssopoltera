<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Role;
use App\Models\SsoSession;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use App\Policies\RolePolicy;
use App\Policies\SsoSessionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Application::class => ApplicationPolicy::class,
        SsoSession::class => SsoSessionPolicy::class,
    ];

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
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}

