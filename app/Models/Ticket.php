<?php

namespace App\Models;

use App\Enums\TicketMessageType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'subject',
        'priority',
        'status',
        'assigned_to',
        'closed_at',
        'last_customer_message_at',
        'last_staff_message_at',
        'customer_last_read_at',
        'assignee_last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'closed_at' => 'datetime',
            'last_customer_message_at' => 'datetime',
            'last_staff_message_at' => 'datetime',
            'customer_last_read_at' => 'datetime',
            'assignee_last_read_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest();
    }

    public function latestPublicMessage(): HasOne
    {
        return $this->hasOne(TicketMessage::class)
            ->where('message_type', TicketMessageType::Public->value)
            ->latestOfMany();
    }

    public function task(): HasOne
    {
        return $this->hasOne(Task::class, 'source_ticket_id')->withTrashed();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', TicketStatus::Closed->value);
    }

    public function scopeAssignedTo(Builder $query, User|int $user): Builder
    {
        return $query->where('assigned_to', $user instanceof User ? $user->id : $user);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->can('tickets.view_all') ? $query : $query->assignedTo($user);
    }

    public function scopeUnreadForStaff(Builder $query): Builder
    {
        return $query
            ->whereNotNull('last_customer_message_at')
            ->where(function (Builder $query) {
                $query->whereNull('assignee_last_read_at')
                    ->orWhereColumn('last_customer_message_at', '>', 'assignee_last_read_at');
            });
    }

    public function hasUnreadCustomerReply(): bool
    {
        if ($this->last_customer_message_at === null) {
            return false;
        }

        return $this->assignee_last_read_at === null
            || $this->last_customer_message_at->gt($this->assignee_last_read_at);
    }

    public function hasUnreadStaffReply(): bool
    {
        if ($this->last_staff_message_at === null) {
            return false;
        }

        return $this->customer_last_read_at === null
            || $this->last_staff_message_at->gt($this->customer_last_read_at);
    }
}
