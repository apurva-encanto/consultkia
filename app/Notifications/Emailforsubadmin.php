<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;




class Emailforsubadmin extends Notification
{
    use Queueable;
     protected $token;
    protected $email;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
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

         //$url = url("user/verifyEmail") .'/'.base64_encode($notifiable->email) .'/'. $this->otp;
         $url = 'https://mmfinfotech.co/tink/';

       // $url = config('app.Ranch_App_URL/user/verifyEmail/'.$notifiable->email.'/'.$this->token);
      

         // $url = url('app.Ranch_App_URL/user/verifyEmail/' . $this->token .'/'.base64_encode($notifiable->email));
     // print_r($url); die;
        return (new MailMessage)

                    ->subject(trans('emailNotifications.email_verification_title'))
                    ->greeting(trans('emailNotifications.email_verification_content_1', ['userName' => $notifiable->first_name]))
                    ->line("\n")
                    ->line(new HtmlString('You are receiving this email because you were added as a Sub-admin on <strong>Tink app</strong>'))
                    ->line(new HtmlString('Please use this email address for login <strong>' . $notifiable->email . '</strong>'))
                    ->line(new HtmlString('Your verification code is <strong>' . $this->otp . '</strong>'))
                    ->line(new HtmlString('<strong>Please change this after the first time you log in.</strong>'))
                  ->action('Click here for the website link', url($url))
                    ->line(new HtmlString('<strong>Please ignore if this email does not belong to you.</strong>'))
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
