<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\DatePresenter;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DatePresenter $dates): View
    {
        return view('dashboard', [
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'rolesCount' => Role::query()->count(),
            'today' => $dates->today(),
        ]);
    }
}
