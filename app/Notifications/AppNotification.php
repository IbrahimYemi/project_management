<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

use Illuminate\Notifications\Messages\MailMessage;

class AppNotification extends Notification
{
    public string $message;
    public string $type;
    public string|int|null $dataId;
    public bool $sendMail;

    public array $task;

    public function __construct(string $message, string $type = 'general', string|int|null $dataId = null, bool $sendMail = false, array $task = [])
    {
        $this->message = $message;
        $this->type = $type;
        $this->dataId = $dataId;
        $this->sendMail = $sendMail;
        $this->task = $task;
    }

    public function via($notifiable)
    {
        $channels = ['database'];

        if ($this->sendMail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'dataId' => $this->dataId,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Task Notification')
            ->view('emails.task_notification', [
                'taskMessage' => $this->message,
                'task' => $this->task,
                'url' => config('app.frontend_url')."/tasks/{$this->dataId}"
            ]);
    }

}

