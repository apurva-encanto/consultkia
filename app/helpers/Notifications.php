<?php
use App\Models\User;
use Carbon\Carbon;

   


    function send_notification1($fcm_token, $data, $info) {
        
        $URL = 'https://fcm.googleapis.com/fcm/send';   
        $post_data = [
          'registration_ids' => (array)$fcm_token, //firebase token array
          'data' => $data, //msg for andriod
          'notification' => $data, //msg for ios
        ];
       
        $crl = curl_init();
        $headr = [];
        $headr[] = 'Content-type: application/json';
        $headr[] = 'Authorization: key='.FCM_KEY; 
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($crl, CURLOPT_URL, $URL);
        curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
        curl_setopt($crl, CURLOPT_POST, true);
        curl_setopt($crl, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        $rest = curl_exec($crl);
        curl_close($crl);   
        return $rest;
   
    }
    function send_notification3($fcm_token, $title, $body, $refrence_id, $data){
        $URL = 'https://fcm.googleapis.com/fcm/send';
        $payload = $data;
        $post_data = [
        'to' => $fcm_token, 
        'notification' => [
                "title" => $title,
                "body" => $body,
                "mutable_content"=> true,
                "sound"=> "Tri-tone"
            ],
        'data' => [
            "title"=> $title,
            "body"=> $body,
            'data' => $data,
            "reference_id" => $refrence_id
            ],
        ];    
        $crl = curl_init();
        $headr = [];
        $headr[] = 'Content-type: application/json';
        $headr[] = 'Authorization: key='.FCM_KEY; 
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($crl, CURLOPT_URL, $URL);
        curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
        curl_setopt($crl, CURLOPT_POST, true);
        curl_setopt($crl, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        $rest = curl_exec($crl);
        curl_close($crl);   
        return $rest;
    }
        
    function get_Formatted_date($date,$format)
    {
        $formattedDate=date($format,strtotime($date));
        return $formattedDate;
    }


   

?>