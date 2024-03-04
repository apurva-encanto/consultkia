<?php



namespace App\Notifications;



use Illuminate\Bus\Queueable;

use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Notifications\Messages\MailMessage;

use Illuminate\Notifications\Notification;



class emailVerificationRequest extends Notification

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

         $url = url("user/verifyEmail") .'/'.base64_encode($notifiable->email) .'/'. $this->otp;

        return (new MailMessage)

                    ->subject(trans('emailNotifications.email_verification_title'))

                    ->greeting(trans('emailNotifications.email_verification_content_1', ['userName' => $notifiable->name]))

                    ->line("\n")

                    ->line(trans('emailNotifications.email_verification_content_3'))
                    ->line(trans('emailNotifications.email_verification_code', ['code' => $this->otp]))

                   //  ->action(trans('emailNotifications.email_verification_content_2'), url($url))

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

