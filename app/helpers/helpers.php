<?php

use Carbon\Carbon;
// use App\models\Production;

// if(!function_exists("get_Formatted_date")){
//     function get_Formatted_date($date,$format){
//         $formattedDate=date($format,strtotime($date));
//         return $formattedDate;
//     }
// }


function generateOrderNumber() {
    $currentTime = Carbon::now();
    $orderNumber = 'Order-' . $currentTime->format('YmdHis');
    return $orderNumber;
}

function generateRandomString($length = 8) {
   $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $randomString = substr(str_shuffle($characters), 0, $length);

   return $randomString;
}

?>