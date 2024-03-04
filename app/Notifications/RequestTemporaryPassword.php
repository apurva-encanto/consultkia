<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestTemporaryPassword extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
         $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = ('https://roadeye.ae/Driver-Company');
        return (new MailMessage)

                    ->subject(trans('emailNotifications.password_newset_title'))

                    ->greeting(trans('emailNotifications.email_verification_content_1', ['userName' => $notifiable->name]))

                    ->line("\n")

                    ->line(trans('emailNotifications.password_newset_content_1'))

                    ->action(trans('emailNotifications.password_newset_button'), url($url))

                    //->line(trans('emailNotifications.password_reset_content_2'))

                    ->line("\n")

                    ->line(trans('emailNotifications.password_newset_content_3'))
                      ->line(trans('emailNotifications.temporary_password', ['code' => $notifiable->temporary_password]))

                    ->line("\n")

                    ->line(trans('emailNotifications.Regards'))

                    ->salutation(config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
