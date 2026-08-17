<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $request->user('customer');
        $customer->load('phones');

        return view('portal.profile', compact('customer'));
    }
}
