<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    public string $message;
    public string $type;
    public string|int|null $dataId;

    public function __construct(string $message, string $type = 'general', string|int|null $dataId = null)
    {
        $this->message = $message;
        $this->type = $type;
        $this->dataId = $dataId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'dataId' => $this->dataId,
        ];
    }
}
