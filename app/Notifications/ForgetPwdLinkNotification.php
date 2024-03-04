<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;






class ForgetPwdLinkNotification extends Notification implements ShouldQueue

{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $otp;
    public function __construct($otp)
    {
       
        $this->otp = $otp;
       
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
        
        // echo $notifiable;
        // die;
        return (new MailMessage)

        ->subject(trans('emailNotifications.ForgetPassword'))

        ->greeting(trans('emailNotifications.email_verification_content_1', ['userName' => $notifiable->name]))
        ->line(trans('emailNotifications.email_forgot_code',['code' => $this->otp]))
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
