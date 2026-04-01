<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $currentUser = auth()->user();
        $registeredApplications = Application::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $accessibleApplicationIds = $currentUser
            ? $currentUser->applications()->pluck('id')->all()
            : [];

        $availableApplications = $registeredApplications->map(function (Application $application) use ($accessibleApplicationIds) {
            $application->setAttribute('is_accessible', in_array($application->id, $accessibleApplicationIds, true));

            return $application;
        });

        $accessibleApplications = $availableApplications->filter(fn (Application $application) => (bool) $application->getAttribute('is_accessible'));

        return view('dashboard', [
            'userCount' => User::count(),
            'activeUserCount' => User::where('is_active', true)->count(),
            'roleCount' => Role::count(),
            'applicationCount' => Application::count(),
            'availableApplications' => $availableApplications,
            'accessibleApplicationCount' => $accessibleApplications->count(),
        ]);
    }
}
