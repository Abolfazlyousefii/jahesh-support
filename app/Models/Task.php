<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'customer_id', 'source_ticket_id', 'assignee_id', 'created_by',
        'priority', 'status', 'start_date', 'due_date', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function sourceTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'source_ticket_id')->withTrashed();
    }

    public function scopeAssignedTo(Builder $query, User|int $user): Builder
    {
        return $query->where('assignee_id', $user instanceof User ? $user->getKey() : $user);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->can('tasks.view_all') ? $query : $query->assignedTo($user);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereDate('due_date', '<', today())->active();
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->due_date->isBefore(today()) && ! $this->status->isClosed();
    }
}
