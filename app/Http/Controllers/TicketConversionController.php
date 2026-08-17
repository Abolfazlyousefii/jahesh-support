<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\ConvertTicketToTaskAction;
use App\Http\Requests\Ticket\ConvertTicketToTaskRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketConversionController extends Controller
{
    public function create(Ticket $ticket): View
    {
        Gate::authorize('convertToTask', $ticket);
        abort_if($ticket->task()->withTrashed()->exists(), 409, 'این تیکت قبلاً تبدیل شده است.');

        return view('tickets.convert', [
            'ticket' => $ticket,
            'assignees' => User::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ConvertTicketToTaskRequest $request, Ticket $ticket, ConvertTicketToTaskAction $action): RedirectResponse
    {
        Gate::authorize('convertToTask', $ticket);
        $task = $action->execute($request->user(), $ticket, $request->validated());

        return redirect()->route('tasks.show', $task)->with('success', 'تیکت به تسک تبدیل شد.');
    }
}
