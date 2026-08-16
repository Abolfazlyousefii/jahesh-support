<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Support\DatePresenter;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DatePresenter $dates): View
    {
        $canViewCustomers = request()->user()->can('customers.view');

        return view('dashboard', [
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'rolesCount' => Role::query()->count(),
            'today' => $dates->today(),
            'activeCustomers' => $canViewCustomers ? Customer::query()->active()->count() : null,
        ]);
    }
}
