<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PersonalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly string $message,
        public readonly string $url,
        public readonly string $tone = 'neutral',
        public readonly ?string $subjectType = null,
        public readonly ?int $subjectId = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'tone' => $this->tone,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
        ];
    }
}
