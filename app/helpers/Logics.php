<?php 
namespace App\helpers;
// use App\Models\Zone;
// use App\Models\Categorys;
// use App\Models\BusinessSetups;

class Logics
{
    public static function SendCode()
    {
        // echo "Hello";
        // die;
        $code = mt_rand(1000,9999);
        return $code;
    }

    public static function sendPushNotification($fcmtoken, $notification)
    {
        // return "success";
        $notificationData['title'] = $notification['title'];
        $notificationData['body'] = $notification['body'];
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array(
            'registration_ids' => array($fcmtoken),
            'notification' => $notificationData,
            'data' => $notification,
            'priority'=>"high"
        );

        $fields = json_encode($fields);
        $headers = array(
            'Authorization: key='.FIREBASEKEY,
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($ch);
        if ($result === false) {
            die('Curl failed: ' . curl_error($ch));
        }

        $result1 = json_decode($result);
        curl_close($ch);
        return $result1;
    }
}
?>