<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityCatalog;
use Carbon\CarbonImmutable;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $event = array_key_exists($request->string('event')->toString(), ActivityCatalog::events())
            ? $request->string('event')->toString()
            : null;
        $subjectType = array_key_exists($request->string('subject_type')->toString(), ActivityCatalog::subjectTypes())
            ? $request->string('subject_type')->toString()
            : null;
        $actorId = $request->integer('actor_id') ?: null;
        $from = $this->dateOrNull($request->string('from')->toString());
        $to = $this->dateOrNull($request->string('to')->toString());

        $query = ActivityLog::query()
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%")
                    ->orWhere('subject_label', 'like', "%{$search}%");
            }))
            ->when($event, fn (Builder $query) => $query->where('event', $event))
            ->when($subjectType, fn (Builder $query) => $query->where('subject_type', $subjectType))
            ->when($actorId, fn (Builder $query) => $query
                ->where('actor_type', (new User)->getMorphClass())
                ->where('actor_id', $actorId))
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to->endOfDay()));

        $logs = (clone $query)
            ->latest()
            ->paginate(app(SettingsService::class)->paginationPerPage())
            ->withQueryString();

        return view('activity.index', [
            'logs' => $logs,
            'search' => $search,
            'event' => $event,
            'subjectType' => $subjectType,
            'actorId' => $actorId,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            'events' => ActivityCatalog::events(),
            'subjectTypes' => ActivityCatalog::subjectTypes(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'todayCount' => ActivityLog::query()->whereDate('created_at', today())->count(),
            'weekCount' => ActivityLog::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'financeCount' => ActivityLog::query()->where('event', 'like', 'finance.%')->count(),
        ]);
    }

    public function show(ActivityLog $activity): View
    {
        return view('activity.show', compact('activity'));
    }

    private function dateOrNull(string $date): ?CarbonImmutable
    {
        if ($date === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date);
        } catch (Throwable) {
            return null;
        }
    }
}
