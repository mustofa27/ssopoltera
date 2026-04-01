<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $currentUser = auth()->user();
        $showAdminStats = $currentUser
            ? (
                $currentUser->hasPermission('manage_users')
                || $currentUser->hasPermission('manage_roles')
                || $currentUser->hasPermission('manage_applications')
                || $currentUser->hasPermission('manage_sessions')
                || $currentUser->hasPermission('view_audit_logs')
            )
            : false;

        $registeredApplications = Application::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $availableApplications = $registeredApplications
            ->filter(function (Application $application) use ($currentUser) {
                return $currentUser ? $application->allowsUserType($currentUser->user_type) : false;
            })
            ->map(function (Application $application) use ($currentUser) {
                $application->setAttribute('is_accessible', $currentUser ? $application->isAccessibleToUser($currentUser) : false);
                $application->setAttribute('launch_url', $this->resolveLaunchUrl((string) $application->redirect_uri));

                return $application;
            })
            ->values();

        $accessibleApplications = $availableApplications->filter(fn (Application $application) => (bool) $application->getAttribute('is_accessible'));

        return view('dashboard', [
            'showAdminStats' => $showAdminStats,
            'userCount' => $showAdminStats ? User::count() : null,
            'activeUserCount' => $showAdminStats ? User::where('is_active', true)->count() : null,
            'roleCount' => $showAdminStats ? Role::count() : null,
            'applicationCount' => $showAdminStats ? Application::count() : null,
            'availableApplications' => $availableApplications,
            'accessibleApplicationCount' => $accessibleApplications->count(),
        ]);
    }

    private function resolveLaunchUrl(string $redirectUri): string
    {
        $parts = parse_url($redirectUri);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return $redirectUri;
        }

        $baseUrl = $parts['scheme'] . '://' . $parts['host'];

        if (! empty($parts['port'])) {
            $baseUrl .= ':' . $parts['port'];
        }

        return $baseUrl;
    }
}
