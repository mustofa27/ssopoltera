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
        return view('dashboard', [
            'userCount' => User::count(),
            'activeUserCount' => User::where('is_active', true)->count(),
            'roleCount' => Role::count(),
            'applicationCount' => Application::count(),
        ]);
    }
}
