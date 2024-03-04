<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Validator;
use Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Notification;
use App\Models\Appointment;
use App\Models\LawyerDetail;
use App\Models\Ticketlist;
use App\Models\Transaction;
use App\Models\Slot;
use App\Models\Order;
// date_default_timezone_set('Asia/Kolkata');

use App\Models\UserStatus;


use Illuminate\Http\JsonResponse;
// use Illuminate\Http\JsonResponse;
use App\helpers\Notifications;

class NotificationController extends ApiController

{
   public function __construct()
    {  
      Auth::shouldUse('api');
    }

    
    public function notify(){     
      $payload="bkjbdefjhvbhjbhjv";
      $name="bujivedglbgklrvfew";
      return send_notification1($payload,$name);
      
    }

    public function notificationlist(){
      //type 1-offer service,2-appointment request,3-appointment approved,4-appointment complete,5-payment successfull,6-appointment cancellation
        $user=Auth::user();
        if(!empty($user)){
          $updatecount=User::where('id',$user->id)->update([
            "notification_count"=>0
          ]);
          $getnotification=Notification::where('reciever_id',$user->id)->orderBy('id','desc')->paginate(10);
          
          // return$user;
          if(count($getnotification) > 0){
              foreach($getnotification as $row){
                $name='';
                $profile='';
                $getuser=User::where('id',$row->sender_id)->first();
                if(!empty($getuser)){
                  $name=$getuser->name;
                  $profile=$getuser->profile_img?imgUrl.'user_profile/'.$getuser->profile_img:default_img;
                }
                $date='';
                $stime='';
                $etime='';
                $sid='';
                $status='';

                $getappinment=Appointment::where('id',$row->appointment_id)->first();
                if(!empty($getappinment)){

                  $date=$getappinment->date;

                  $slot1=Slot::where('id',$getappinment->slot_id)->first();
                  // return $slot;
                  if(!empty($slot1)){
                    $stime=$slot1->start_time;
                    $etime=$slot1->end_time;
                    $sid=$getappinment->slot_id;
                    if($slot1->end_time!=''){
                      $status=true;
                    }else{
                      $status=false;
                    }
                    
                  }
                }
                if($sid==null){
                  $slot=null;
                }else{
                  $slot['id']=(int)$sid;
                  $slot['fromTime']=$stime;
                  $slot['toTime']=$etime;
                  $slot['status']=$status;
                }
                if($row->type==1){
                  $type="New Offer";
                }elseif($row->type==2){
                  $type="New appointment request";
                }elseif($row->type==3){
                  $type="Appointment has approved from RVN";
                }elseif($row->type==4){
                  $type="New Offer";
                }else{
                  $type="New Offer";
                }

                if($row->type==14){
                  $getticketnumber=Ticketlist::where('id',$row->appointment_id)->first();
                  $getticketimage=null;
                  if($getticketnumber->is_file==1){
                      $getticketimage=Ticketlistimage::where('ticket_id',$row->appointment_id)->first();
                      $getticketimage=$getticketimage->image?imgUrl.'Ticket'.$getticketimage->image:null;
                  }
                  $othersdata=array(
                          "id"=>(int)$row->id,
                          'ticket_id'=>(string)$row->appointment_id,
                          'user_id'=>$user->id,
                          'subject'=>$getticketnumber->subject?$getticketnumber->subject:null,
                          'discription'=>$getticketnumber->discription?$getticketnumber->discription:null,
                          'status'=>$getticketnumber->status?(string)$getticketnumber->status:'0',
                          'created_at'=>$getticketnumber->created_at,
                          'updated_at'=>$getticketnumber->updated_at,
                          'image'=>$getticketimage
                      );
                  $row->otherMiscData=json_encode($othersdata);
                }
                $row->name=$name;
                $row->sender_id=(int)$row->sender_id;
                $row->reciever_id=(int)$row->reciever_id;
                $row->appointment_id=(int)$row->appointment_id;
                $row->appointment_date=$date;
                $row->status=(int)$row->status;
                $row->type=(int)$row->type;
                $row->user_profile=$profile;
                $row->type_msg=$type;                
                $row->notification_status=(int)$row->notification_status;
                if($row->type!=14){
                  $row->slot=$slot;
                }                
                $data[]=$row;
                unset($row->status);
                unset($row->created_at);
                unset($row->response);
                unset($row->updated_at);

              }

                $getarray1['status']= 200;
                $getarray1['success']= true;
                $getarray1['message']="Notifications list";
                $getarray1['data']=!empty($data)?$data:array();
                $getarray1['currentPage'] = $getnotification->currentPage();
                $getarray1['last_page'] = $getnotification->lastPage();
                $getarray1['total_record'] = $getnotification->total();
                $getarray1['per_page'] = $getnotification->perPage();
              return response()->json($getarray1);
          }else{
                $getarray1['status']= 200;
                $getarray1['success']= true;
                $getarray1['message']="";
                $getarray1['data']=array();
                $getarray1['currentPage'] = $getnotification->currentPage();
                $getarray1['last_page'] = $getnotification->lastPage();
                $getarray1['total_record'] = $getnotification->total();
                $getarray1['per_page'] = $getnotification->perPage();
              return response()->json($getarray1);
             
          }
        }else{
          return response()->json(['status'=>400,'success'=>false,'message'=>"You've not logged In. Please Login first to get access this"]);
        }
    
    }

    public function notificationCount(){
        $user=Auth::user();
        if(!empty($user)){
          $count=Notification::where('reciever_id',$user->id)->where('status','0')->count('id');
          if($count > 0){
            return response()->json(['status'=>200,'success'=>true,'message'=>"You've " .$count ." new notifications","data"=>$count]);
          }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"You've no new notifications"]);
          }
        }else{
          return response()->json(['status'=>400,'success'=>false,'message'=>"You've not logged In. Please Login first to access this"]);
        }
    
    }

    public function notificationDelete(){
        $user=Auth::user();
        if(!empty($user)){
          $delete=Notification::where('reciever_id',$user->id)->delete();
          if($delete==true){
            return response()->json(['status'=>200,'success'=>true,'message'=>"All notifications has been deleted"]);
          }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"failed try again"]);
          }
        }else{
          return response()->json(['status'=>400,'success'=>false,'message'=>"You've not logged In. Please Login first to get access this"]);
        }
    
    }

    public function callNotification(Request $request){
      // return "hello";
                              $validator= Validator::make($request->all(),[
                                  "reciever_id"=>"required"
                              ]);
                              if($validator->fails()){
                                  return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
                              }
                              $user=Auth::user();

                              if(!empty($user)){

                              $myLawyerCheck=LawyerDetail::select('is_available')->where('user_id',$request->reciever_id)->first();;


                              $getrate=LawyerDetail::select('call_charge')->where('user_id',$request->reciever_id)->first();
                              if(!empty($getrate)){
                                  $getrate=$getrate->call_charge;
                              }else{
                                  $getrate=0;
                              }
                              $maxtime=0;
                              $maxtime=round($user->wallet_ballance/$getrate); 

                              $time=Carbon::parse($request->call_start)->format('H:i:s');
                              $date=Carbon::parse($request->call_start)->format('Y-m-d');
                              $dated=date('y-m-d h:i:s');  
                              if($request->type=='0'){

                                $orderRunning=Order::where('user_id',$user->id)->where('lawyer_id',$request->reciever_id)->where('call_type','1')->where('duration',0)->where('total_amount',0)->where('end_by',0)->where('status','0')->first();
                                if(empty($orderRunning) && $myLawyerCheck->is_available ==1)
                                {
                                $order=new Order();
                                $order->user_id=$user->id;
                                $order->lawyer_id=$request->reciever_id;                   
                                $order->call_rate=$getrate;
                                $order->call_start=$time;
                                $order->date=$date;
                                $order->status='0';
                                $order->call_end='00:00:00';
                                $order->duration=$request->duration?$request->duration:0;
                                $order->end_by=0;
                                $query=$order->save();
                                $msg="Order Successfully created 123";
                                $status=200;
                                $array['id']=$order->id;
                                $array['max_time']=(int)$maxtime;
                                if($query==true){



                                  $addtransaction=New Transaction();
                                  $addtransaction->txn_id=$order->id;
                                  $addtransaction->order_number=generateOrderNumber();
                                  $addtransaction->from=$user->id;
                                  $addtransaction->to=$request->reciever_id;
                                  $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i:s');
                                  $addtransaction->sender_type="2";
                                  $addtransaction->reciever_type="3";   
                                  $addtransaction->amount=0;
                                  $addtransaction->status=0;
                                  // $addtransaction->dateTime=$request->dateTime;
                                  // return $addtransaction;
                                  $query1=$addtransaction->save();                                    
                                    $updatequery=LawyerDetail::where('user_id',$request->reciever_id)->update([
                                    "is_available"=>'0'
                                    ]);
                                  }

                                }else{
                                  $array['id']=$orderRunning->id;
                                }


                                }else{

                                  $transaction=Transaction::where('txn_id',$request->order_id)->first();
                                  if(!empty($transaction))
                                  {
                                    Transaction::where('id',$transaction->id)->delete();                                    
                                  }

                                  Order::where('id',$request->order_id)->delete();

                                  $status=200;
                                  $array['id']=(int)$request->order_id;
                                  $array['max_time']=(int)0;

                                  $updatequery=LawyerDetail::where('user_id',$request->reciever_id)->update([
                                    "is_available"=>'1'
                                    ]);

                                }


                          $getfcm=User::select('fcm_token','first_name','last_name','user_name')->where('id',$request->reciever_id)->first();

                
                          if(!empty($getfcm)){
                            $fcm=$getfcm->fcm_token;
                            $name=$getfcm->user_name;
                          }else{
                            $fcm='';
                            $name="Consultkiya user";
                          }
                            if($request->type=='1'){
                              $title = "You have call end notification from ". $user->user_name;
                              $body ="You have a new end call notification " . $user->user_name;
                              $type =2;
                              $msg="Call end notification successfully sent";

                              $data=array(
                                'title' =>$title,
                                'body' =>$body,
                                'type' =>$type,
                                'profile_img' =>$user->profile_img?imgUrl.'profile/'.$user->profile_img:default_img,
                                "user_name"=>$user->user_name?$user->user_name:"Anonymous",
                                "user_id"=>$user->id,
                                'reference_id' => '14',
                                'order_id'=>0,
                                // 'badge' => '1',
                                'android_channel_id' => 1,                
                                "sound" => "default",
                                "status" => "done",
                                "mutable_content"=> true,
                                
                              );

                            }else{
                              $title ="You have call notification from ". $user->user_name;
                              $body ="You have a new call notification " . $user->user_name;
                              $type =1;
                              $msg="Notification successfully sent";   
                              
                              $data=array(
                                'title' =>$title,
                                'body' =>$body,
                                'type' =>$type,
                                'profile_img' =>$user->profile_img?imgUrl.'profile/'.$user->profile_img:default_img,
                                "user_name"=>$user->user_name?$user->user_name:"Anonymous",
                                "user_id"=>$user->id,
                                'reference_id' => '14',
                                'order_id'=>$array['id'],

                                // 'badge' => '1',
                                'android_channel_id' => 1,                
                                "sound" => "default",
                                "status" => "done",
                                "mutable_content"=> true,
                                
                              );
                            }

                        
                          
                          $details='';
                          $response=send_notification1($fcm,$data,$details='');
                          $dta = json_decode($response);

                          if(!empty($dta))
                          {
                            if($dta->success==1){                                   
                              return response()->json(['status'=>200,'success' => true,'message'=>'Call Placed', 'data' =>$array]);
                            }else{
                              return response()->json(['status'=>400,'success' => false, 'message' => "failed try again"]);
        
                            }
                          }else{
                            return response()->json(['status'=>400,'success' => false, 'message' => "failed try again"]);
                          }

                        
                
                    // return $dta;
                   
                    
        }else{
          return response()->json(['status'=>400,'success' => false, 'message' => "Login first to access this"]);
        }

    }



    public function sendRequest(Request $request){
      // return "hello";
        $validator= Validator::make($request->all(),[
            "reciever_id"=>"required"
        ]);
        if($validator->fails()){
            return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
        }
        $user=Auth::user();
        if(!empty($user)){
          $getfcm=User::select('fcm_token','user_name')->where('id',$request->reciever_id)->first();
          if(!empty($getfcm)){
            $fcm=$getfcm->fcm_token;
            $name=$getfcm->user_name?$getfcm->user_name:"Anonymous";
          }else{
            $fcm='';
            $name="Anonymous";
          }

          $orderRunning=Order::where('lawyer_id',$request->reciever_id)->where('call_type','0')->where('total_amount',0)->where('status','0')->orderBy('id','desc')->first();

          if(!empty($orderRunning))
          {

            return response()->json(['status'=>400,'success' => false, 'message' => "Lawyer is just busy on another Chat"]);
          }
                       
            
              $title = "You have chat request from ". $user->user_name;
              $body = $user->user_name . "send message to you";
              $type =3;
              $msg="Your request has successfully submitted";
            
                $order=new Order();
                $order->user_id=$user->id;
                $order->lawyer_id=$request->reciever_id;
                $order->call_type='0';                
                $order->status='0';   
                $order->end_by=0;     
                $order->date=date('Y-m-d');  
                $order->call_start=$request->call_start;  
                $query=$order->save();  


            $data=array(
              'title' =>$title,
              'body' =>$body,
              'profile_img' =>$user->profile_img?imgUrl.'profile/'.$user->profile_img:default_img,
              "user_name"=>$user->user_name?$user->user_name:"Anonymous",
              "chat_id"=>$order->id,
              "user_id"=>$user->id,
              'type' =>$type,
              'reference_id' => '14',
              // 'badge' => '1',
              'android_channel_id' => 1,                
              "sound" => "default",
              "status" => "done",
              "mutable_content"=> true,
              
            );
            
            $details='';

            $response=send_notification1($fcm,$data,$details='');
            $dta = json_decode($response);
           
            if($dta->success==1){
                // $order=new Order();                     
            
            
              return response()->json(['status'=>200,'success' => true, 'message' =>$msg,'data'=>$order->id]);
            }else{
              $delete=Order::where('id',$order->id)->delete();
              return response()->json(['status'=>400,'success' => false, 'message' => "failed try again"]);

            }
                    
        }else{
          return response()->json(['status'=>400,'success' => false, 'message' => "Login first to access this"]);
        }

    }



    public function acceptRequest(Request $request){
      // return "hello";
        $validator= Validator::make($request->all(),[
            "reciever_id"=>"required"
        ]);
        if($validator->fails()){
            return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
        }
        $user=Auth::user();
        if(!empty($user)){
          $getfcm=User::select('fcm_token','user_name')->where('id',$request->reciever_id)->first();
          // return $getfcm;
          if(!empty($getfcm)){
            $fcm=$getfcm->fcm_token;
            $name=$getfcm->user_name?$getfcm->user_name:"Anonymous";
          }else{
            $fcm='';
            $name="Anonymous";
          }
              $check=Order::where('user_id',$request->reciever_id)->where('lawyer_id',$user->id)->orderbydesc('id')->first();
              // return $check;
                if(!empty($check)){
                    if($check->status=='2'){                      
                      return response()->json(['status'=>400,'success' => false, 'message' =>"User Rejected this request"]);
                    }else{
                      $updaetstatus=Order::where('id',$check->id)->update([
                        "status"=>'1'
                      ]);
                     $userstatus=new UserStatus();
                     $userstatus->lawyer_id=$user->id;
                     $userstatus->user_id=$request->reciever_id;
                     $userstatus->date_time=date('Y-m-d H:i:s');
                     $userstatus->chat_id=$check->id;
                     $userstatus->auth_token='';
                     $userstatus->save();
                    }
                }
              $title = "Lawyer ".$user->user_name . "has accepted your chat request";
              $body ="Lawyer ".$user->user_name . "has accepted your chat request";
              $type =4;
              $msg="Chat request accepted";
            

            $data=array(
              'title' =>$title,
              'body' =>$body,
              'profile_img' =>$user->profile_img?imgUrl.'profile/'.$user->profile_img:default_img,
              "user_name"=>$user->user_name?$user->user_name:"Anonymous",
              'type' =>$type,
              'chat_id' =>$check->id,
              'reference_id' => '14',
              "user_id"=>$user->id,              
              'android_channel_id' => 1,                
              "sound" => "default",
              "status" => "done",
              "mutable_content"=> true,
              
            );
            
            $details='';

            $response=send_notification1($fcm,$data,$details='');
            $dta = json_decode($response);
            // return $dta ;
            if($dta->success==1){              
              $updatequery=LawyerDetail::where('user_id',$user->id)->update([
                          "is_available"=>'0'
                        ]);
              return response()->json(['status'=>200,'success' => true, 'message' =>$msg]);
            }else{
              $updaetstatus=Order::where('id',$check->id)->update([
                    "status"=>'0'
                  ]);
              return response()->json(['status'=>400,'success' => false, 'message' => "failed try again"]);

            }
                    
        }else{
          return response()->json(['status'=>400,'success' => false, 'message' => "Login first to access this"]);
        }

    }


    public function cancelledRequest(Request $request){
      // return "hello";
        $validator= Validator::make($request->all(),[
            "reciever_id"=>"required"
        ]);
        if($validator->fails()){
            return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
        }
        $user=Auth::user();
        if(!empty($user)){
          $getfcm=User::select('fcm_token','user_name')->where('id',$request->reciever_id)->first();
          if(!empty($getfcm)){
            $fcm=$getfcm->fcm_token;
            $name=$getfcm->user_name?$getfcm->user_name:"Anonymous";
          }else{
            $fcm='';
            $name="Anonymous";
          }

              $check=Order::where('user_id',$user->id)->where('lawyer_id',$request->reciever_id)->orderbydesc('id')->where('status','0')->first();
                

                if(!empty($check)){
                    if($check->status=='1'){                      
                      return response()->json(['status'=>400,'success' => false, 'message' =>"You can't cancelled this chat request"]);
                    }else{
                      $updaetstatus=Order::where('id',$check->id)->update([
                        "status"=>'2'
                      ]);
                    }
                }
            
              $title =$user->user_name . "has cancelled chat request";
              $body =$user->user_name . "has cancelled chat request";
              $type =5;
              $msg="Chat request cancelled";
            

            $data=array(
              'title' =>$title,
              'body' =>$body,
              "profile_img"=>$user->profile_img?imgUrl.'user_profile/'.$user->profile_img:default_img,
              "user_name"=>$user->user_name?$user->user_name:"Anonymous",
              'type' =>$type,
              'reference_id' => '14',
              "user_id"=>$user->id,
              // 'badge' => '1',
              'android_channel_id' => 1,                
              "sound" => "default",
              "status" => "done",
              "mutable_content"=> true,
              
            );
            
            $details='';

            $response=send_notification1($fcm,$data,$details='');
            $dta = json_decode($response);
            if($dta->success==1){
               $updaetstatus=Order::where('id',$check->id)->update([
                    "status"=>'2'
                  ]);

              return response()->json(['status'=>200,'success' => true, 'message' =>$msg]);
            }else{
              return response()->json(['status'=>400,'success' => false, 'message' => "failed try again"]);

            }
                    
        }else{
          return response()->json(['status'=>400,'success' => false, 'message' => "Login first to access this"]);
        }

    }



    public function updateChat(Request $request){
      // return "hello";
        $validator= Validator::make($request->all(),[
            "chat_id"=>"required",
            "duration"=>"required"
        ]);
        if($validator->fails()){
            return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
        }
        $user=Auth::user();  
        
        if($request->duration >300 )
        {
          $request->duration =300;
        }

        // return $user;
        $details='';
        if(!empty($user)){
          $check=Order::where('id',$request->chat_id)->first();
          if(!empty($check)){
              $getchatorder=Order::where('id',$request->chat_id)->where('status','3')->first();
              if(!$getchatorder){
                if($user->user_type==1){              
                    $checkwallet=User::where('id',$check->lawyer_id)->first();
                    if(!empty($checkwallet)){   
                        $getfcm=User::select('fcm_token','user_name')->where('id',$check->lawyer_id)->first();
                        if(!empty($getfcm)){
                          $fcm=$getfcm->fcm_token;
                          $name=$getfcm->user_name?$getfcm->user_name:"Anonymous";
                        }else{
                          $fcm='';
                          $name="Anonymous";
                        }                   
                        $title =$user->user_name . "has end the chat";
                        $body =$user->user_name . "has end the chat";
                        $type =6;
                        $msg="Chat end";
                      

                        $data=array(
                          'title' =>$title,
                          'body' =>$body,
                          "profile_img"=>$user->profile_img?imgUrl.'profile/'.$user->profile_img:default_img,
                          "user_name"=>$user->user_name?$user->user_name:"Anonymous",
                          'type' =>$type,
                          "user_id"=>$user->id,
                          'reference_id' => '14',
                          // 'badge' => '1',
                          'android_channel_id' => 1,                
                          "sound" => "default",
                          "status" => "done",
                          "mutable_content"=> true,
                          
                        );
                          $response=send_notification1($fcm,$data,$details='');

                          $dta = json_decode($response);
                          if($dta->success==1){
                            $duration=$request->duration/60;
                            $seconds=$request->duration;
                            $minutes = floor($seconds / 60);
                            $remainingSeconds = $seconds % 60;

                            $formattedTime = sprintf("%d:%02d", $minutes, $remainingSeconds);
                            $total_time=$request->duration?$formattedTime:null;
                            $getrate=LawyerDetail::select('call_charge')->where('user_id',$check->lawyer_id)->first();
                            if(!empty($getrate)){
                                $getrate=$getrate->call_charge;
                            }else{
                                $getrate=0;
                            }
                            $amount=ceil($duration)*$getrate;
                                   
                            $updatequery=Order::where('id',$request->chat_id)->update([
                              "duration"=>$total_time,
                              "call_rate"=>$getrate,
                              "total_amount"=>$amount
                            ]);

                            $updateWalletlawyer=User::where('id',$check->lawyer_id)->update([
                                "wallet_ballance"=>$checkwallet->wallet_ballance+$amount,
                            ]);

                            $updateWalletuser=User::where('id',$check->user_id)->update([
                                "wallet_ballance"=>$user->wallet_ballance-$amount,
                            ]);

                            $updatequery=LawyerDetail::where('user_id',$check->lawyer_id)->update([
                              "is_available"=>'1'
                            ]); 

                            UserStatus::where('chat_id',$request->chat_id)->delete();

 
                            $updaetstatus=Order::where('id',$request->chat_id)->update([
                                  "status"=>'3',
                                  "end_by"=>$user->id
                            ]);
                            // echo $user->id."<br>";
                            // return $updaetstatus; 
                            $order_no=generateOrderNumber();
                            $addtransaction=New Transaction();
                            $addtransaction->order_number=$order_no;
                            $addtransaction->from=$check->user_id;
                            $addtransaction->to=$check->lawyer_id;
                            $addtransaction->sender_type="2";
                            $addtransaction->reciever_type="3";   
                            $addtransaction->status="1";
                            $addtransaction->amount=$amount;
                            // $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i:s');

                            $query1=$addtransaction->save();
                            return response()->json(['status'=>200,'success' => true, 'message' =>$msg,'data'=>$request->chat_id?$request->chat_id:null]);
                          }else{
                            return response()->json(['status'=>400,'success' => false, 'message' => "failed try again"]);

                          }
                    }else{
                      return response()->json(['status'=>200,'success' => true, 'message' =>$msg,'data'=>$request->chat_id?$request->chat_id:null]);
                    }
                }else{              
                    $checkwallet=User::where('id',$check->user_id)->first();
                    if(!empty($checkwallet)){     
                          $getfcm=User::select('fcm_token','user_name')->where('id',$check->user_id)->first();
                          if(!empty($getfcm)){
                            $fcm=$getfcm->fcm_token;
                            $name=$getfcm->user_name?$getfcm->user_name:"Anonymous";
                          }else{
                            $fcm='';
                            $name="Anonymous";
                          }

                             
                              $title =$user->user_name . "has end the chat";
                              $body =$user->user_name . "has end the chat";
                              $type =6;
                              $msg="Chat end";
                            

                            $data=array(
                              'title' =>$title,
                              'body' =>$body,
                              "profile_img"=>$user->profile_img?imgUrl.'profile/'.$user->profile_img:default_img,
                              "user_name"=>$user->user_name?$user->user_name:"Anonymous",
                              'type' =>$type,
                              "user_id"=>$user->id,
                              "order_id"=>$check->id,
                              'reference_id' => '14',
                              // 'badge' => '1',
                              'android_channel_id' => 1,                
                              "sound" => "default",
                              "status" => "done",
                              "mutable_content"=> true,
                              
                            );
                            $response=send_notification1($fcm,$data,$details='');
                            $dta = json_decode($response);
                            if($dta->success==1){
                                $duration=$request->duration/60;
                                $seconds=$request->duration;
                                $minutes = floor($seconds / 60);
                                $remainingSeconds = $seconds % 60;

                                $formattedTime = sprintf("%d:%02d", $minutes, $remainingSeconds);
                                $total_time=$request->duration?$formattedTime:null;
                                $getrate=LawyerDetail::select('call_charge')->where('user_id',$check->lawyer_id)->first();
                                if(!empty($getrate)){
                                    $getrate=$getrate->call_charge;
                                }else{
                                    $getrate=0;
                                }
                                $amount=ceil($duration)*$getrate;
                                        
                                $updatequery=Order::where('id',$request->chat_id)->update([
                                  "duration"=>$total_time,
                                  "call_rate"=>$getrate,
                                  "total_amount"=>$amount
                                ]);
                                $updateWalletuser=User::where('id',$check->user_id)->update([
                                    "wallet_ballance"=>$checkwallet->wallet_ballance-$amount,
                                ]);

                                $updateWalletlawyer=User::where('id',$check->lawyer_id)->update([
                                    "wallet_ballance"=>$user->wallet_ballance+$amount,
                                ]);

                                $updatequery=LawyerDetail::where('user_id',$check->lawyer_id)->update([
                                  "is_available"=>'1'
                                ]);   
                                $updaetstatus=Order::where('id',$check->id)->update([
                                    "status"=>'3',
                                    "end_by"=>$user->id
                                ]);
                                $order_no=generateOrderNumber();
                                $addtransaction=New Transaction();
                                $addtransaction->order_number=$order_no;
                                $addtransaction->from=$check->user_id;
                                $addtransaction->to=$check->lawyer_id;
                                $addtransaction->sender_type="2";
                                $addtransaction->reciever_type="3";   
                                $addtransaction->status="1";
                                $addtransaction->amount=$amount;
                                $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i:s');
                                $query1=$addtransaction->save();
                              return response()->json(['status'=>200,'success' => true, 'message' =>"Chat end",'data'=>$request->chat_id?$request->chat_id:null]);
                            }else{
                              return response()->json(['status'=>400,'success' => false, 'message' => "failed try again"]);

                            }
                    }else{
                      return response()->json(['status'=>200,'success' => true, 'message' =>"Chat end",'data'=>$request->chat_id?$request->chat_id:null]);
                    }
                }
              }else{
                 return response()->json(['status'=>200,'success' => true, 'message' =>"Chat end 1",'data'=>$request->chat_id?$request->chat_id:null]);
              }
              
          }else{
            return response()->json(['status'=>200,'success' => true, 'message' =>"Chat end 2",'data'=>$request->chat_id?$request->chat_id:null]);
          }
         
                    
        }else{
          return response()->json(['status'=>400,'success' => false, 'message' => "Login first to access this"]);
        }

    }

  
 
}
 
