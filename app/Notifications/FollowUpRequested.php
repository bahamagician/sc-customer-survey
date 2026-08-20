<?php

namespace App\Notifications;

use App\Filament\Resources\SurveyResponses\SurveyResponseResource;
use App\Models\SurveyResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class FollowUpRequested extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SurveyResponse $response) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $customer = $this->response->name ?? $this->response->email ?? 'A customer';

        return (new MailMessage)
            ->subject("Survey follow-up requested by {$customer}")
            ->line("{$customer} asked for someone to follow up about their experience.")
            ->line('Email: '.($this->response->email ?? '—'))
            ->line('Overall score: '.($this->response->overall_score !== null ? "{$this->response->overall_score} / 10" : 'not answered'))
            ->line('Location: '.($this->response->location->name ?? '—'))
            ->line('Channel: '.$this->response->source->getLabel())
            ->action('View full response', SurveyResponseResource::getUrl('view', ['record' => $this->response]))
            ->line('They also appear in the Follow-up Queue in the admin.');
    }
}
