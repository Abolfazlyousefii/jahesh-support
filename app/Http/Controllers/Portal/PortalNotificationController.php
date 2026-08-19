<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalNotificationController extends Controller
{
    public function index(Request $request, SettingsService $settings): View
    {
        $customer = auth('customer')->user();
        $filter = $request->string('filter')->toString();

        $query = $customer->notifications()->latest();
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        return view('portal.notifications.index', [
            'notifications' => $query
                ->paginate($settings->paginationPerPage())
                ->withQueryString(),
            'filter' => $filter,
            'unreadCount' => $customer->unreadNotifications()->count(),
        ]);
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'unread_count' => auth('customer')->user()->unreadNotifications()->count(),
        ]);
    }

    public function open(string $notification): RedirectResponse
    {
        $customer = auth('customer')->user();
        $item = $customer->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return redirect()->to($this->safeUrl((string) ($item->data['url'] ?? route('portal.dashboard')), route('portal.dashboard')));
    }

    public function readAll(): RedirectResponse
    {
        auth('customer')->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'همه اعلان‌های شما خوانده شدند.');
    }

    private function safeUrl(string $url, string $fallback): string
    {
        if ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $fallback;
        }

        return $url;
    }
}
