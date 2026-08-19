<?php

namespace App\Http\Controllers;

use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, SettingsService $settings): View
    {
        $user = $request->user();
        $filter = $request->string('filter')->toString();

        $query = $user->notifications()->latest();
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        return view('notifications.index', [
            'notifications' => $query
                ->paginate($settings->paginationPerPage())
                ->withQueryString(),
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return redirect()->to($this->safeUrl((string) ($item->data['url'] ?? route('dashboard')), route('dashboard')));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

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
