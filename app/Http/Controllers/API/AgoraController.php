<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\AgoraDynamicKey\RtcTokenBuilder;
use App\Classes\AgoraDynamicKey\RtmTokenBuilder;
use App\Classes\AgoraDynamicKey\ChatTokenBuilder2;
use App\Classes\AgoraDynamicKey\AccessToken2;
use App\Classes\AgoraDynamicKey\AccessToken;
use App\Classes\AgoraDynamicKey\ServiceChat;
use Http;
use App\Models\sprect;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
class AgoraController extends Controller
{

    public function __construct()
    {
        Auth::shouldUse('api');
    }
   
    public function createToken(Request $request)
    {

        /*$appID = '3308acffeda54bdf8313d3ed9ebe7e0e';
        $appCertificate = '08c4487432224713a7fbdf258ece63cc';*/

        $appID = '2a77c9056b12438a9c5b13c3d0a8a0bc';
        $appCertificate = 'c9519b5514f74238be10084150c825c4';
        $expireTimeInSeconds = 120;
        $currentTimestamp = now()->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;
        $accessToken = new AccessToken2($appID, $appCertificate,  $expireTimeInSeconds );
        $serviceChat = new ServiceChat($appID);
         // print_r($serviceChat);die;

        $serviceChat->addPrivilege($serviceChat::PRIVILEGE_APP,  $privilegeExpiredTs);
        $accessToken->addService($serviceChat);
        $agoraToken  = $accessToken->build();
        $uid = $request->username;
        $channelName ='Testing';
        $role =1;
          // echo 'Token with RTC, RTM, CHAT privileges: ' . $agoraToken . PHP_EOL;
        // $token = ChatTokenBuilder2::buildUserToken($appID, $appCertificate,$uid,7200);
        $token = ChatTokenBuilder2::buildUserToken($appID, $appCertificate,7200);
        // $rtmtoken = RtmTokenBuilder::buildToken($appID, $appCertificate,$uid,$channelName,$role,7200);
        $rtmtoken = RtmTokenBuilder::buildToken($appID, $appCertificate,$channelName,7200);
        $data =[];
        $data['badge_no'] = $request->username?$request->username:null;
        $data['agora_token'] = $agoraToken;
        $data['chat_token'] = $token;
        $data['voice_call_token'] = !empty($rtmtoken)?$rtmtoken:'';
        return response()->json(['status' => 200,'success'=>true,'message'=>"token successfull", 'data'=>$data]);
        //return $agoraToken.'-'.$token;
    } //End createToken

    public function createToken2(Request $request)
    {

        /*$appID = '3308acffeda54bdf8313d3ed9ebe7e0e';
        $appCertificate = '08c4487432224713a7fbdf258ece63cc';*/

        $appID = '3e54c7302a7f46f6ad5563ef5d86912c';
        $appCertificate = '9e056244db914f4cabde73f49636449e';
        $expireTimeInSeconds = 7200*60;
        $currentTimestamp = now()->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;
        $accessToken = new AccessToken2($appID, $appCertificate,  $expireTimeInSeconds );
        $serviceChat = new ServiceChat($appID);
         // print_r($serviceChat);die;

        $serviceChat->addPrivilege($serviceChat::PRIVILEGE_APP,  $privilegeExpiredTs);
        $accessToken->addService($serviceChat);
        $agoraToken  = $accessToken->build();
        $uid = null;
        $channelName ='main';
        // $role =1;
        $role = RtcTokenBuilder::RolePublisher;
          // echo 'Token with RTC, RTM, CHAT privileges: ' . $agoraToken . PHP_EOL; 

        $token = ChatTokenBuilder2::buildUserToken($appID, $appCertificate,  $uid,7200);
        $rtctoken = RtcTokenBuilder::buildTokenWithUid($appID,$appCertificate,$channelName,$uid,$role,$privilegeExpiredTs);
        $rtmtoken = RtmTokenBuilder::buildToken($appID,$appCertificate,$channelName,$uid,$role,$privilegeExpiredTs);
       
       
        // assertEqual($parser->message->privileges[$privilegeKey], $expiredTs);
        //$rtmtoken = AccessToken::init($appID, $appCertificate, $uid, $channelName, 1, 7200);
         
     
        $data =[];
        $data['badge_no'] = $request->username;
        $data['agora_token'] = $agoraToken;
        $data['chat_token'] = $token;
        $data['voice_call_token'] = !empty($rtctoken)?$rtctoken:'';
        $data['rtmtoken'] = !empty($rtmtoken)?$rtmtoken:'';
        return response()->json(['status' => 200,'success'=>true,'message'=>"token successfull", 'data'=>$data]);
        //return $agoraToken.'-'.$token;
    } //End createToken2


    public function userRtcToken(Request $request)
    {

        $input = $request->all();
        $validator = Validator::make($input,[
            'uid' => 'required',
            'channelName' => 'required',
         ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $appID = 'f55dfc4e23bc432088dec77864162083';
        $appCertificate = '22e975500f5b436a931299e5fc2afc62';

        $channelName = $request->channelName;
        $uid = $request->uid;
        $role = RtcTokenBuilder::RolePublisher;
        $rtctoken = RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate,$channelName,$uid,$role,7200);
        $data =[];
        $data['voice_call_token'] = !empty($rtctoken)?$rtctoken:'';
        return response()->json(['status' => 200,'success'=>true,'message'=>"token successfull", 'data'=>$data]);


    }//End userRtcToken
    
     public function createToken_old()
    {

        /*$appID = '3308acffeda54bdf8313d3ed9ebe7e0e';
        $appCertificate = '08c4487432224713a7fbdf258ece63cc';*/

        $appID = 'f55dfc4e23bc432088dec77864162083';
        $appCertificate = '22e975500f5b436a931299e5fc2afc62';
        $expireTimeInSeconds = 120;
        $currentTimestamp = now()->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;
         $accessToken = new AccessToken2($appID, $appCertificate,  $expireTimeInSeconds );
         $serviceChat = new ServiceChat();

         $serviceChat->addPrivilege($serviceChat::PRIVILEGE_APP,  $privilegeExpiredTs);
          $accessToken->addService($serviceChat);
         $agoraToken  = $accessToken->build();
          // echo 'Token with RTC, RTM, CHAT privileges: ' . $agoraToken . PHP_EOL;
        // $token = AccessToken::init($appID, $appCertificate, $channelName, $uid,$privilegeExpiredTs);


        return $agoraToken;
    }

    public function agoraRegister(Request $request)
    {
            $agoraToken=$this->createToken();
            $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/users';
            $headers = [
                  'Authorization' => 'Bearer '.$agoraToken,
                  'Content-Type' => 'application/json'
                ];
            $input = [
               'username' => $request->uniqueId,
               'password'=>'12345678',
               'nickname' => $request->username
               ];
    
            $response = Http::withHeaders($headers)->post($apiURL, $input);
            $statusCode = $response->status();
            $responseBody = json_decode($response->getBody(), true);
            return $responseBody;
    } //End agoraRegister
   

   public function agoraProfileUpdate(Request $request)
   {
            $agoraToken=$this->createToken();
            $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/users/'.$request->username;
            $headers = [
                  'Authorization' => 'Bearer '.$agoraToken,
                  'Content-Type' => 'application/json'
                ];
           $input = [
            'nickname'=> $request->nick_name,
            //'avatarurl'=> $request->profile_url,
            'avatar'=> $request->profile_url,
            'mail'=> $request->mail,
            'phone'=> $request->phone,
            'gender'=> $request->gender
            ];
    
            $response = Http::withHeaders($headers)->put($apiURL, $input);
            $statusCode = $response->status();
            $responseBody = json_decode($response->getBody(), true);
            return $responseBody;          
   } //End agoraProfileUpdate


   public function agoraProfileGet(Request $request)
   {
            $agoraToken=$this->createToken();
            $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/users/'.$request->username;
            $headers = [
                  'Authorization' => 'Bearer '.$agoraToken,
                  'Content-Type' => 'application/json'
                ];

        $response = Http::withHeaders($headers)->get($apiURL);
        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);
        return $responseBody;          
   } //End agoraProfileGet

   public function agoraUserList(Request $request)
   {
            $agoraToken=$this->createToken();
            $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/users';
            $headers = [
                  'Authorization' => 'Bearer '.$agoraToken,
                  'Content-Type' => 'application/json'
                ];
    
    $response = Http::withHeaders($headers)->get($apiURL);
    $statusCode = $response->status();
    $responseBody = json_decode($response->getBody(), true);
    return $responseBody;          
   } //End agoraUserList

   public function agoraUserDelete(Request $request)
   {
            $agoraToken=$this->createToken();
            $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/users/'.$request->username;
            $headers = [
                  'Authorization' => 'Bearer '.$agoraToken,
                  'Content-Type' => 'application/json'
                ];
    
    $response = Http::withHeaders($headers)->delete($apiURL);
    $statusCode = $response->status();
    $responseBody = json_decode($response->getBody(), true);
    return $responseBody;          
   } //End agoraUserDelete


   public function agoraWithAppUserDelete(Request $request)
   {
        // $data = $request->all();
        // $validator = Validator::make($data, [
        //     'user_id'        => 'required',
        // ]);
    
        // if($validator->fails()) 
        // {
        //     return response()->json(["status" => false, "message" => $validator->messages()->first()]);
        // }
            // $tt =Auth::user();
            // return $tt; die;
            $user_id    = Auth::user()->id;
            $userInfo   = User::where('id',$user_id)->first();  

            $agoraToken=$this->createToken();
            $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/users/'.$userInfo->badge_no;
            $headers = [
                  'Authorization' => 'Bearer '.$agoraToken,
                  'Content-Type' => 'application/json'
                ];
    
            $response = Http::withHeaders($headers)->delete($apiURL);
            $statusCode = $response->status();
            $responseBody = json_decode($response->getBody(), true);
            //return $responseBody;    

        $result=  User::where('id',$user_id)->update(['status' => 2]);


        if ($result==1) {
            return response()->json(["status" => true, "message" => trans('messages.user_delete_successfully')]);
        } else {
            return response()->json(["status" => false, "message" => trans('messages.user_delete_fail')]);
        }
       
   } //End agoraWithAppUserDelete

    public function sendOneToOneMsg(Request $request)
    {
        $agoraToken=$this->createToken();
        $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/messages/users';
        $headers = [
              'Authorization' => 'Bearer '.$agoraToken,
              'Content-Type' => 'application/json'
            ];
            
            $requestData = [
                "from"  => "975367",
                "type"  => "txt",
                //"chatType"  => "singleChat",
                "to"    => ["123123"],
                "body"  => ["msg" => "Hello Rupesh Welcome to agora chat"]
            ];
    
        $response = Http::withHeaders($headers)->post($apiURL, $requestData);
        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);
        return $responseBody;
    } //End sendOneToOneMsg "from"  => "975367",


    ############### ChatRoom Start  ################
    public function chatroomsCreate(Request $request)
    {
        $agoraToken=$this->createToken();
        $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/chatrooms';
        $headers = [
              'Authorization' => 'Bearer '.$agoraToken,
              'Content-Type' => 'application/json'
            ];

            $requestData = [
                "name"  => "testchatroom_2",
                "description"  => "test_2",
                "maxusers" => 5,
                "owner"  => "975367",
                "members"  => ["123123"]
            ];
    
        $response = Http::withHeaders($headers)->post($apiURL, $requestData);
        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);
        return $responseBody;
    } //End chatroomsCreate

    public function chatroomsDetails(Request $request)
    {
        $agoraToken=$this->createToken();
        $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/chatrooms/'.$request->chatroom_id;
        $headers = [
              'Authorization' => 'Bearer '.$agoraToken,
              'Content-Type' => 'application/json'
            ];

        $response = Http::withHeaders($headers)->get($apiURL);
        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);
        return $responseBody;
    } //End chatroomsCreate

    public function sendChatroomsMsg(Request $request)
    {
        $agoraToken=$this->createToken();
        $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/messages/chatrooms';
        $headers = [
              'Authorization' => 'Bearer '.$agoraToken,
              'Content-Type' => 'application/json'
            ];
            
            $requestData = [
                "from"  => "123123",
                "type"  => "txt",
                "to"    => ["205551085617153"],
                "body"  => ["msg" => "Welcome chatrooms"]
            ];
    
        $response = Http::withHeaders($headers)->post($apiURL, $requestData);
        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);
        return $responseBody;
    } //End sendChatroomsMsg 

    ############### ChatRoom End  ################



    public function contactManage(Request $request)
    {
        $agoraToken=$this->createToken();
        $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/users/'.$request->owner_username.'/contacts/users/'.$request->friend_username;
        $headers = [
              'Authorization' => 'Bearer '.$agoraToken,
              'Content-Type' => 'application/json'
            ];

        $response = Http::withHeaders($headers)->post($apiURL);
        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);
        return $responseBody;
    } //End contactManage


    public function msgSendForWeb(Request $request)
    {

        $agoraToken=$this->createToken();
        $apiURL  =  'https://a61.chat.agora.io/61930021/1086476/messages/users';
        $headers = [
              'Authorization' => 'Bearer '.$agoraToken,
              'Content-Type' => 'application/json'
            ];
            
            $requestData = [
                "from"  => "975367",
                "type"  => "txt",
                //"chatType"  => "singleChat",
                "to"    => ["123123"],
                "body"  => ["msg" => "Hello Rupesh Welcome to agora chat"]
            ];
    
        $response = Http::withHeaders($headers)->post($apiURL, $requestData);
        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);
        return $responseBody;
        
    } //End msgSendForWeb 


    public function sprect_test(Request $request)
    {
        $search = [10,4]; //1,2,3
        $sprect_five =  sprect::when(!empty($search), function($query) use($search) {
                        foreach($search as $term) {
                            $query->orwhereRaw("find_in_set('".$term."',lang_id)");
                        };
                    })->get();
        return $sprect_five; die;
                /*if(sizeof($sprect_five)){
                    $data1 = array();

                    foreach ($sprect_five as $row1) {
                            
                            $sprect_ten = sprect::where('description', $search)->get();
                            if(sizeof($sprect_ten)){
                                $data2 = array();
                                foreach ($sprect_ten as $row2) {
                                    $array_key2['description'] = $row2->description;
                                    $data2[] = $array_key2;
                                }
                            }else{
                                $array_key1['description'] = $row1->description;
                                $data1[] = $array_key1; 
                            }

                       // $array_key1['description'] = $row1->description;
                       //  $data1[] = $array_key1;
                    }
                }
                 return response()->json(['status' => true, 'data' => $data1]);*/
    } //End sprect_test  

    public function userDetails($user_id)
    {
       $userdata = User::where('badge_no',$user_id)->first();
       //return $userdata;
       if(!empty($userdata)){
        $data['id'] = $userdata->id;
        $data['badge_no'] = $userdata->badge_no;
        $data['user_name'] = $userdata->user_name;
        $data['fcm_token'] = $userdata->fcm_token;
        $data['profile_img'] = $userdata->profile_img ? imgUrl .'profileImg/'. $userdata->profile_img : imgUrl. '/profileImg/default.png';
        $data['email'] = $userdata->email;

        return response()->json(['status' => true, 'message' => trans('messages.record_available'), 'data' =>$data ]);
       }else{
         return response()->json(['status' => false, 'message' => trans('messages.no_record_found'), 'data' =>[]]);
       }
    }//End userDetails
    
    

}
