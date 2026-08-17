<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $request->user('customer');

        return view('portal.dashboard', [
            'customer' => $customer,
            'recentTickets' => $customer->tickets()->limit(5)->get(),
        ]);
    }
}
