<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\EmailVerification;
use App\Notifications\emailVerificationRequest;
use App\Notifications\emailVerificationSucess;
use Illuminate\Http\JsonResponse;
use App\Models\PasswordReset;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use Validator;
use Session;
use Auth;
use Storage;
use Twilio\Rest\Client;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Payment;
use App\Models\LawyerDetail;
use App\Models\PracticeArea;
use App\Models\Availability;
use App\Models\AvailabilityTime;
use App\Models\Transaction;
use App\Models\Document;
use App\Models\Language;
use App\Models\Review;
use App\Models\Appointment;
use App\Models\CallChatHistory;
use App\Models\AgoraTokenLawyer;
use App\Models\OrderReview;
use App\Models\Favorite;
use App\Models\Splash;
use App\Models\Order;
use App\Notifications\SignupNotification;
use Illuminate\Support\Facades\Cache;


class OrderController extends ApiController
{
    public function __construct()
    {
        Auth::shouldUse('api');

    }
   
    public function addCallRecord(Request $request){

        $user=Auth::user();

        if($user->user_type==1 || $user->user_type=="1")
        {            
            $validator = Validator::make($request->all(),[
                'lawyer_id'=>'required',
                'id'=>'required',
                'duration'=>'required',
                
            ]);   
        
            if($validator->fails()){
                return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
            }
            $getrate=LawyerDetail::select('call_charge')->where('user_id',$request->lawyer_id)->first();            
            if(!empty($getrate)){
                $getrate=$getrate->call_charge;
            }else{
                $getrate=0;
            }
            $maxtime=0;
            $maxtime=round($user->wallet_ballance/$getrate);  
            $order=Order::find($request->id);

            if(!empty($order))
            {                

                if (strtotime($order->call_end) !== strtotime("00:00:00")) {
                    return response()->json(["status" => 201, "success"=> false, "message" =>"Call already Ended"]);  
                }
                else{

                    $cacheKey = 'call_processed:' . $request->id;

                     // Check if the call has already been processed
                    if (Cache::has($cacheKey)) {
                        return response()->json(["status" => 201, "success"=> false, "message" => "Call has already been processed"]);
                    }

                   
                    $time=Carbon::parse($request->call_end)->format('Y-m-d h:i:s');      
                    
                    if($request->duration > 300)
                    {
                        $request->duration=300;
                    }

                    
                    if($request->duration > 0){    
                        
                        $duration=$request->duration/60;
                        $seconds=$request->duration;
                        $minutes = floor($seconds / 60);
                        $remainingSeconds = $seconds % 60;
                        $formattedTime = sprintf("%d:%02d", $minutes, $remainingSeconds);
                        $order->duration=$request->duration?$formattedTime:null;

                    }  

                    $amount=ceil($duration)*$getrate;
                    try {
                    DB::beginTransaction();
                    // Use optimistic locking
                    $order->lockForUpdate()->first();
                    
                    $order->call_rate=$getrate?$getrate:0;                        
                    $order->total_amount=$amount;                        
                    $order->status="3";
                    $order->end_by=$user->id;
                    $order->call_end=Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d h:i:s');                                            
                    $query=$order->save();
                    $msg="Order Successfully updated";
                    $status=201;

                    $array['id']=$request->id;
                    $array['max_time']=(int)$maxtime;

                    if($query==true){  

                       
                        $array=[];

                        if($request->duration>0){
                            
                            $checktransaction=Transaction::where('txn_id',$request->id)->where('status',0)->first(); 

                           

                            if(!empty($checktransaction))
                            {

                               $addtransaction= Transaction::find($checktransaction->id);

                                $addtransaction->lockForUpdate()->first();

                               $addtransaction->amount=$amount;
                               $addtransaction->status=1;
                               $addtransaction->save();
                                Cache::put($cacheKey, true, 60); 
                                DB::commit();
                                $checkwallet=User::where('id',$order->user_id)->first();
                                if(!empty($checkwallet)){
                                    $updateWallet=User::where('id',$order->user_id)->update([
                                        "wallet_ballance"=>$checkwallet->wallet_ballance-$amount,
                                    ]);
                                }
                                $updatequery=LawyerDetail::where('user_id',$request->lawyer_id)->update([
                                "is_available"=>'1'
                                ]);
                                $getlawyerballance=User::select('wallet_ballance')->where('id',$request->lawyer_id)->first();
                                if(!empty($getlawyerballance)){
                                    $updateLawyerBallance=User::where('id',$request->lawyer_id)->update([
                                        "wallet_ballance"=>$getlawyerballance->wallet_ballance+$amount,
                                    ]);
                                }            

                            }                          
                            
                                               

                            return response()->json(["status" => $status, "success"=> true, "message" =>$msg,'data'=>$array]);

                        }

                        return response()->json(["status" => $status, "success"=> true, "message" =>$msg,'data'=>$array]);
                     
                    }
                }
                catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(["status" => 500, "success" => false, "message" => "Internal Server Error"]);
                }

                    
                }

            }else{
                return response()->json(["status" => 404, "success" => false, "message" => "Order not found"]);
            }


        }else{
          

            $getrate=LawyerDetail::select('call_charge')->where('user_id',$request->lawyer_id)->first();

            
            if(!empty($getrate)){
                $getrate=$getrate->call_charge;
            }else{
                $getrate=0;
            }

            $maxtime=0;
            $maxtime=round($user->wallet_ballance/$getrate);  
            $order=Order::find($request->id);
            if(!empty($order))
            {
                if($request->duration > 300)
                {
                    $request->duration=300;
                }
                $cacheKey = 'call_processed:' . $request->id;

                 // Check if the call has already been processed
                if (Cache::has($cacheKey)) {
                    return response()->json(["status" => 201, "success"=> false, "message" => "Call has already been processed"]);
                }
                
                $getrate=LawyerDetail::select('call_charge')->where('user_id',$user->id)->first();          

                $user_details=User::where('id',$order->user_id)->first();
                $totalMinutes = ceil($request->duration / 60);
                $calculated_amt= $getrate->call_charge * $totalMinutes ;

                if($calculated_amt > $user_details->wallet_ballance)
                {
                    $calculated_amt=$user_details->wallet_ballance;
                }

                if (strtotime($order->call_end) === strtotime("00:00:00")) {

                    $order_update=Order::find($order->id);

            try {
                    DB::beginTransaction();
                    $order_update->lockForUpdate()->first();

                    $order_update->duration=  $this->convert_to_minutes_seconds($request->duration);    
                    $order_update->call_end=Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
                    $order_update->total_amount=$calculated_amt;
                    $order_update->status="3";
                    $order_update->end_by=123;

                    $query=$order_update->save();
                    $msg="lawyer Successfully updated";
                    $status=201;

                    
                    if($query==true){  

                          
                        $array=[];

                        if($request->duration>0){
                            
                            $checktransaction=Transaction::where('txn_id',$request->id)->where('status',0)->first(); 
                            $amount=$calculated_amt;
                           

                            if(!empty($checktransaction))
                            {

                               $addtransaction= Transaction::find($checktransaction->id);
                               $addtransaction->lockForUpdate()->first();

                               $addtransaction->amount=$amount;
                               $addtransaction->status=1;
                               $addtransaction->save();
                               Cache::put($cacheKey, true, 60);
                                DB::commit();
                                $checkwallet=User::where('id',$order->user_id)->first();
                                if(!empty($checkwallet)){
                                    $updateWallet=User::where('id',$order->user_id)->update([
                                        "wallet_ballance"=>$checkwallet->wallet_ballance-$amount,
                                    ]);
                                }
                                $updatequery=LawyerDetail::where('user_id',$request->lawyer_id)->update([
                                "is_available"=>'1'
                                ]);
                                $getlawyerballance=User::select('wallet_ballance')->where('id',$request->lawyer_id)->first();
                                if(!empty($getlawyerballance)){
                                    $updateLawyerBallance=User::where('id',$request->lawyer_id)->update([
                                        "wallet_ballance"=>$getlawyerballance->wallet_ballance+$amount,
                                    ]);
                                }            

                            }      
                            else{
                                 DB::rollBack();
                                 return response()->json(["status" => 400, "success" => false, "message" => "Transaction not found"]);
                            }
                                                
                            
                                               

                            return response()->json(["status" => $status, "success"=> true, "message" =>$msg,'data'=>$array]);

                        }

                        return response()->json(["status" => $status, "success"=> true, "message" =>$msg,'data'=>$array]);

                    }
                }catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(["status" => 500, "success" => false, "message" => "Internal Server Error"]);
                }


                }else{
                    return response()->json(["status" => 201, "success"=> true, "message" =>'Call already ended','data'=>[]]);
                }


                return  $calculated_amt;

            }

        }

    }

    // public function addCallRecords(Request $request){

        
    //     $user=Auth::user();

    //     if($user->user_type==1 || $user->user_type=="1")
    //     {
    //         if($request->id!=''){
    //                         $validator = Validator::make($request->all(),[
    //                         'id' => 'required',  
    //                         'duration'=>'',
    //                         'call_start'=>'',
    //                         'call_end'=>'',
                            
    //                         ]);
    //                 }else{
    //                     $validator = Validator::make($request->all(),[
    //                         'lawyer_id'=>'required',
    //                         'call_initiate'=>''
                            
    //                     ]); 
    //                 }
                    
    //                 if($validator->fails()){
    //                     return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
    //                 }
    //                 }
      


    //     // return $user;
    //     if(!empty($user)){

    //         if($user->user_type==1 || $user->user_type=="1"){
    //         $getrate=LawyerDetail::select('call_charge')->where('user_id',$request->lawyer_id)->first();
    //             if(!empty($getrate)){
    //                 $getrate=$getrate->call_charge;
    //             }else{
    //                 $getrate=0;
    //             }
    //         $maxtime=0;
    //         $maxtime=round($user->wallet_ballance/$getrate);  
    //         if($request->id!=''){
    //             $order=Order::find($request->id);
    //             if(!empty($order))
    //              {
    //                 if (strtotime($order->call_end) !== strtotime("00:00:00")) {
    //                     return response()->json(["status" => 201, "success"=> false, "message" =>"Call already Ended"]);  
    //                 }
    //                 else{
                             
    //                     $time=Carbon::parse($request->call_end)->format('Y-m-d h:i:s');                
    //                     if($request->duration > 0){                   
    //                         if($request->duration>0){
    //                             $duration=$request->duration/60;
    //                             $seconds=$request->duration;
    //                             $minutes = floor($seconds / 60);
    //                             $remainingSeconds = $seconds % 60;

    //                             $formattedTime = sprintf("%d:%02d", $minutes, $remainingSeconds);
    //                             $order->duration=$request->duration?$formattedTime:null;
    //                         }
    //                         $amount=ceil($duration)*$getrate;
    //                         $order->total_amount=$amount?$amount:0;                  
                        
    //                     }                
    //                     // return $order;
    //                     $order->status="3";
    //                     $order->end_by=$user->id;
    //                     // $order->call_start=Carbon::now('Y-m-d h:i:s');
    //                     $order->call_end=Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d h:i:s');                                            
    //                     $query=$order->save();
    //                     $msg="Order Successfully updated";
    //                     $status=201;
    //                     if($query==true){   
    //                         if($request->duration>0){
    //                             $order_no=generateOrderNumber();
    //                             $checktransaction=Transaction::where('order_number',$order_no)->where('from',$user->id)->where('to',$request->lawyer_id)->first();
    //                             if(!$checktransaction){
    //                                 $addtransaction=New Transaction();
    //                                 $addtransaction->order_number=$order_no;
    //                                 $addtransaction->from=$user->id;
    //                                 $addtransaction->to=$request->lawyer_id;
    //                                 $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i:s');
    //                                 $addtransaction->sender_type="2";
    //                                 $addtransaction->reciever_type="3";   
    //                                 $addtransaction->status="1";
    //                                 $addtransaction->amount=$amount;
    //                                 // $addtransaction->dateTime=$request->dateTime;
    //                                 // return $addtransaction;
    //                                 $query1=$addtransaction->save();
    //                                 if($query1==true){                            
    //                                     $array['id']=$request->id;
    //                                     $array['max_time']=(int)$maxtime;
    //                                     $checkwallet=User::where('id',$order->user_id)->first();
    //                                     if(!empty($checkwallet)){
    //                                         $updateWallet=User::where('id',$order->user_id)->update([
    //                                             "wallet_ballance"=>$checkwallet->wallet_ballance-$amount,
    //                                         ]);

    //                                     }
    //                                     $updatequery=LawyerDetail::where('user_id',$request->lawyer_id)->update([
    //                                     "is_available"=>'1'
    //                                     ]);
    //                                     $getlawyerballance=User::select('wallet_ballance')->where('id',$request->lawyer_id)->first();
    //                                     if(!empty($getlawyerballance)){
    //                                         $updateLawyerBallance=User::where('id',$request->lawyer_id)->update([
    //                                             "wallet_ballance"=>$getlawyerballance->wallet_ballance+$amount,
    //                                         ]);
    //                                     }
    //                                 }
    //                             }
                            
    //                         }

    //                         return response()->json(["status" => $status, "success"=> true, "message" =>$msg,'data'=>$array]);
    //                     }

    //                 }
    //             }

    //         }else{
    //             // return $user;
    //             $time=Carbon::parse($request->call_start)->format('H:i:s');
    //             $date=Carbon::parse($request->call_start)->format('Y-m-d');
    //             $dated=date('y-m-d h:i:s');  
               
    //             $check=Order::where('user_id',$user->id)->where('lawyer_id',$request->lawyer_id)->where('call_type','1')->where('end_by',0)->where('duration',0)->where('status','0')->first();
    //              // return $check;


    //             if(!empty($check)){
    //                     $array['id']=$check->id;
    //                     $array['max_time']=(int)$maxtime;
    //                     $msg="Order Successfully created 1" ;
    //                     return response()->json(["status" => 200, "success"=> true, "message" =>$msg,'data'=>$array]);
    //             }else{
    //                 $orderRunning=LawyerDetail::where('user_id',$request->lawyer_id)->where([
    //                     "is_available"=>'0'
    //                     ])->first();

    //                 // return $orderRunning;
    //                 if($orderRunning)
    //                 {
    //                     return response()->json(["status" => 400, "success"=> true, "message" =>"Dubara Chali "]);                     
                       
    //                 }else{

    //                     $orderRunning=Order::where('user_id',$user->id)->where('lawyer_id',$request->lawyer_id)->where('call_type','1')->where('duration',0)->where('total_amount',0)->where('end_by',0)->where('status','0')->first();
    //                         if(empty($orderRunning) )
    //                         {
    //                             $order=new Order();
    //                             $order->user_id=$user->id;
    //                             $order->lawyer_id=$request->lawyer_id;                   
    //                             $order->call_rate=$getrate;
    //                             $order->call_start=$time;
    //                             $order->date=$date;
    //                             $order->status='0';
    //                             $order->call_end='00:00:00';
    //                             $order->duration=$request->duration?$request->duration:0;
    //                             $order->end_by=0;
    //                             $query=$order->save();
    //                             $msg="Order Successfully created 123";
    //                             $status=200;
    //                             $array['id']=$order->id;
    //                             $array['max_time']=(int)$maxtime;
    //                             $array['my_date_apurva']=$orderRunning;
    //                             if($query==true){
                                    
    //                                 $updatequery=LawyerDetail::where('user_id',$request->lawyer_id)->update([
    //                                 "is_available"=>'0'
    //                                 ]);

    //                                 return response()->json(["status" => $status, "success"=> true, "message" =>$msg,'data'=>$array]);
    //                             }   
    //                         }else{

    //                             $array['id']=$orderRunning->id;
    //                             $array['max_time']=(int)$maxtime;
    //                             $array['my_date_apurva']=$orderRunning;

    //                             $msg="Order Successfully again created";
    //                             return response()->json(["status" => 200, "success"=> true, "message" =>$msg,'data'=>$array]);

    //                         }
    //                 }
    //             }   
               
    //         }
    //         // if($query==true){
    //             return response()->json(["status" => 200, "success"=> true, "message" =>'apurva final','data'=>$array]);
    //         // }else{
    //         //     return response()->json(["status" => 400, "success"=> false, "message" =>"failed try again"]);  
    //         // }

    //         }
    //         else
    //         {
    //             $calculated_amt=0; 
    //             $order= Order::where('id',$request->id)->first();
            
    //             if(!empty($order))
    //             {
    //             if($request->duration > 300)
    //             {
    //                 $request->duration=300;
    //             }

    //             $getrate=LawyerDetail::select('call_charge')->where('user_id',$request->lawyer_id)->first();          

    //             $user_details=User::where('id',$order->user_id)->first();
    //             $totalMinutes = ceil($request->duration / 60);
    //             $calculated_amt= $getrate->call_charge * $totalMinutes ;

            
    //             if($calculated_amt > $user_details->wallet_ballance)
    //             {
    //                 $calculated_amt=$user_details->wallet_ballance;
    //             }

    //             if (strtotime($order->call_end) === strtotime("00:00:00")) {

    //                 $order_update=Order::find($order->id);
    //                 $order_update->duration=  $this->convert_to_minutes_seconds($request->duration);    
    //                 $order_update->call_end=Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
    //                 $order_update->total_amount=$calculated_amt;
    //                 $order_update->status="3";
    //                 $order_update->end_by=123;

    //                 $query=$order_update->save();
    //                 $msg="lawyer Successfully updated";
    //                 $status=201;

    //                 // exit;
    //                 if($query==true){   
    //                     // return $query;
    //                     if($request->duration>0){
    //                         $order_no=generateOrderNumber();
    //                         $checktransaction=Transaction::where('order_number',$order_no)->where('from',$order_update->user_id)->where('to',$order_update->lawyer_id)->first();
    //                         if(!$checktransaction){
    //                             $addtransaction=New Transaction();
    //                             $addtransaction->order_number=$order_no;
    //                             $addtransaction->from=$order_update->user_id;
    //                             $addtransaction->to=$order_update->lawyer_id;
    //                             $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i:s');
    //                             $addtransaction->sender_type="2";
    //                             $addtransaction->reciever_type="3";   
    //                             $addtransaction->status="1";
    //                             $addtransaction->amount=$calculated_amt;
    //                             // $addtransaction->dateTime=$request->dateTime;
    //                             // return $addtransaction;
    //                             $query1=$addtransaction->save();
    //                             if($query1==true){                            
    //                                 // $array['id']=$request->id;
    //                                 // $array['max_time']=(int)$maxtime;
    //                                 $checkwallet=User::where('id',$order_update->user_id)->first();
    //                                 if(!empty($checkwallet)){
    //                                     $updateWallet=User::where('id',$order_update->user_id)->update([
    //                                         "wallet_ballance"=>$checkwallet->wallet_ballance-$calculated_amt,
    //                                     ]);

    //                                 }
    //                                 $updatequery=LawyerDetail::where('user_id',$order_update->lawyer_id)->update([
    //                                 "is_available"=>'1'
    //                                 ]);
    //                                 $getlawyerballance=User::select('wallet_ballance')->where('id',$order_update->lawyer_id)->first();
    //                                 if(!empty($getlawyerballance)){
    //                                     $updateLawyerBallance=User::where('id',$order_update->lawyer_id)->update([
    //                                         "wallet_ballance"=>$getlawyerballance->wallet_ballance+$calculated_amt,
    //                                     ]);
    //                                 }
    //                             }
    //                         }
                        
    //                     }
    //                 }

    //                 return response()->json(["status" => $status, "success"=> true, "message" =>'my test message','data'=>$request->all()]);

    //             }
    //         //     $maxtime=0;
    //         }else{
    //             return response()->json(["status" => 400, "success"=> false, "message" =>"No Record Found"]);

    //         }


    //         }
    //     }else{
    //         return response()->json(["status" => 400, "success"=> false, "message" =>"Need to login first"]);
    //     }
    // }


    function convert_to_minutes_seconds($duration) {
    $minutes = floor($duration / 60);
    $seconds = $duration % 60;
    return sprintf("%d:%02d", $minutes, $seconds);
    }

    public function getCallhistory(){
        $user=Auth::user();
        if($user->user_type==1){
            $orders=Order::with('lawyerDetails')->where('user_id',$user->id)->where('call_type','1')->whereIn('status',['1','3'])->orderBydesc('id')->get();
            if(sizeof($orders)){
                foreach ($orders as $row) {
                    $chekrating=Review::where(['lawyer_id'=>$row->lawyer_id])->avg('rating');
                    $chekfav=Favorite::where(['user_id'=>$user->id,'lawyer_id'=>$row->lawyer_id,'status'=>'1'])->first();
                    $is_fav=0;
                    if(!empty($chekfav)){
                        $is_fav=1;
                    }
                    $row->user_name=$row->lawyerDetails->user_name?$row->lawyerDetails->user_name:"Anonymous";
                    if($row->call_type==1){
                        $row->call_type="Call";
                    }else{
                        $row->call_type="Chat";
                    }
                    $callcharge=CallChatHistory::where('lawyer_id',$row->lawyer_id)->orderBydesc('id')->first();
                    if(!empty($callcharge)){
                        $row->current_call_rate=$callcharge->calls;
                    }else{
                        $row->current_call_rate=00;
                    }
                    $checkagora=AgoraTokenLawyer::where('user_id',$row->lawyer_id)->first();
                    if(!empty($checkagora)){
                    $row->agoraToken=$checkagora->token;
                    }else{
                        $row->agoraToken="";

                    }
                    $currentday=Carbon::now()->format('l');
                    if($currentday=="Monday"){
                        $today=1;
                    }elseif($currentday=="Tuesday"){
                        $today=2;
                    }elseif($currentday=="Wednesday"){
                        $today=3;
                    }elseif($currentday=="Thursday"){
                        $today=4;
                    }elseif($currentday=="Friday"){
                        $today=5;
                    }elseif($currentday=="Saturday"){
                        $today=6;
                    }else{
                        $today=7;
                    }               
                   
                    $currentTime=Carbon::now()->format('h:i:s');
                    $utcTime = Carbon::createFromFormat('h:i:s', $currentTime, 'UTC');                    
                    $istTime = $utcTime->setTimezone('Asia/Kolkata')->format('H:i:s');  
                    $checklawyer=Availability::where('user_id',$row->lawyer_id)->where('day_id',$today)->where('status','1')->first();
                    $row->online = "Offline"; 
                    if(!empty($checklawyer)){
                        $checkavailability=AvailabilityTime::where('availability_id',$checklawyer->id)->where('from','<',$istTime)->where('to','>',$istTime)->where('status','1')->first();                        
                        if(!empty($checkavailability)){
                           $row->is_active=1;
                           $row->online="Online";
                        }else{
                            $checktime=AvailabilityTime::where('availability_id',$checklawyer->id)->where('from','>',$istTime)->where('status','1')->first();
                            if(!empty($checktime)){
                            
                            $currentTime = Carbon::now();
                            $istTime = $currentTime->setTimezone('Asia/Kolkata');
                            $timeDifference = $istTime->diff($checktime->from);


                                if($timeDifference->h < 1){                                    
                                    $row->online = 'online in ' . $timeDifference->i . ' min';
                                }else{
                                    if($timeDifference->i<10){
                                        $row->online ='Online in ' .$timeDifference->h.' :' .'0'.$timeDifference->i . ' min';
                                    }else{
                                       $row->online ='Online in ' .$timeDifference->h.' :' .$timeDifference->i . ' min'; 
                                    }
                                }
                                
                            }  
                            $row->is_active=0;
                        }

                                          
                    }else{
                        $row->is_active=0;
                                              
                    }      

                    $checkstatus=LawyerDetail::where('user_id',$row->lawyer_id)->first();  
                    if(!empty($checkstatus)){
                        $row->is_call=$checkstatus->is_call;
                        $row->is_chat=$checkstatus->is_chat;
                    }else{
                        $row->is_chat=0;
                        $row->is_call=0;
                    }
                    $row->deduction=$row->total_amount;
                    $row->duration=$row->duration;
                    $row->call_rate=$row->call_rate;
                    $row->is_favorite=$is_fav;
                    
                    $row->rating=(string)round($chekrating,2);
                    $time=Carbon::parse($row->call_start)->format('g:i:s A');
                    $row->date=Carbon::parse($row->created_at)->format('M-d-Y').', '.$time;
                    $row->profile_img=$row->lawyerDetails->profile_img?imgUrl.'profile/'.$row->lawyerDetails->profile_img:user_img;
                    $data[]=$row;
                    unset($row->lawyerDetails);
                    unset($row->created_at);
                    unset($row->total_amount);
                    unset($row->user_id);
                    unset($row->call_initiate);
                    unset($row->call_start);
                    unset($row->call_end);
                    unset($row->file);
                }

                return response()->json(["status" => 200, "success"=> true, "message" =>"All call history",'data'=>$data]);

            }else{
                return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
            }
        }elseif($user->user_type==2){
            // return $user;
            $orders=Order::with('userDetails')->where('lawyer_id',$user->id)->where('call_type','1')->whereIn('status',['1','3'])->orderBydesc('id')->get();
            // return $orders;
            if(sizeof($orders)){
                foreach ($orders as $row) {
                    $chekrating=Review::where(['lawyer_id'=>$user->id])->avg('rating');
                    // $isreviewcheck=Review::where(['lawyer_id'=>$user->id])->first();
                    $isreviewcheck=Review::where(['order_id'=>$row->id])->first();
                    if(!empty($isreviewcheck)){
                        $row->is_review=1;
                    }else{
                        $row->is_review=0;
                    }
                    $chekfav=Favorite::where(['user_id'=>$user->id,'lawyer_id'=>$row->lawyer_id,'status'=>'1'])->first();
                    $is_fav=0;
                    if(!empty($chekfav)){
                        $is_fav=1;
                    }
                    $row->user_name=$row->userDetails->user_name?$row->userDetails->user_name:null;
                    if($row->call_type==1){
                        $row->call_type="Call";
                    }else{
                        $row->call_type="Chat";
                    }
                    $callcharge=CallChatHistory::where('lawyer_id',$row->lawyer_id)->orderBydesc('id')->first();
                    if(!empty($callcharge)){
                        $row->current_call_rate=$callcharge->calls;
                    }else{
                        $row->current_call_rate=00;
                    }
                    $row->deduction=$row->total_amount;
                    if($row->userDetails->gender==1){
                        $row->gender="Male";        
                    }elseif($row->userDetails->gender==2){
                        $row->gender="Female";        
                    }else{
                        $row->gender="others";                        
                    }
                    $row->duration=$row->duration;
                    $row->call_rate=$row->call_rate;
                    $row->rating=round($chekrating,2);
                    $time=Carbon::parse($row->call_start)->format('g:i:s A');
                    $row->date=Carbon::parse($row->created_at)->format('M-d-Y').', '.$time;
                    $row->profile_img=$row->userDetails->profile_img?imgUrl.'profile/'.$row->userDetails->profile_img:user_img;
                    $data[]=$row;
                    unset($row->lawyerDetails);
                    unset($row->created_at);
                    unset($row->total_amount);
                    unset($row->user_id);
                    unset($row->lawyer_id);
                    unset($row->call_initiate);
                    unset($row->call_start);
                    unset($row->call_end);
                    unset($row->file);
                    unset($row->userDetails->profile_img);
                }

                return response()->json(["status" => 200, "success"=> true, "message" =>"All call history",'data'=>$data]);

            }else{
                return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
            }


        }else{
            return response()->json(["status" => 400, "success"=> false, "message" =>"You're not authorized!"]);

        }

    }

    public function callDetail($id=''){
        $user=Auth::user();
        if($user->user_type==1){
            $orders=DB::table('orders')->select('orders.call_rate','users.city','users.user_name','users.last_name','users.user_name','users.gender','lawyer_details.practice_area','lawyer_details.experience','users.gender','orders.lawyer_id','lawyer_details.language','lawyer_details.education_details')
            ->join('users','users.id','orders.lawyer_id')
            ->join('lawyer_details','lawyer_details.user_id','orders.lawyer_id')
            
            ->where('orders.id',$id)
            ->first();
            // return $orders;
            if(!empty($orders)){
                $getarea=PracticeArea::select('name')->whereIn('id',json_decode($orders->practice_area))->get();
                    $practice=[];
                    if(count($getarea)){
                        // $practice=[];
                        foreach ($getarea as $value) {
                            array_push($practice,$value->name);
                        }
                    }
                    $getlanguage=Language::select('language_name')->whereIn('id',json_decode($orders->language))->get();
                    $language=[];
                    if(count($getlanguage)){
                       
                        foreach ($getlanguage as $value) {
                            array_push($language,$value->language_name);
                        }
                    }
                $chekfav=Favorite::where(['user_id'=>$user->id,'lawyer_id'=>$orders->lawyer_id,'status'=>'1'])->first();
                $is_fav=0;
                if(!empty($chekfav)){
                    $is_fav=1;
                }
               
                $array['lawyer_id']=$orders->lawyer_id;
                // $array['user_name']=$orders->lawyer_id;
                $array['user_name']=$orders->user_name;
               
                if($orders->gender==1){
                $array['gender']="Male";
                }elseif($orders->gender==2){
                $array['gender']="Female";
                }else{
                $array['gender']="Others";

                }
                    // $checkagora=AgoraTokenLawyer::where('user_id',$row->user_id)->first();
                    // if(!empty($checkagora)){
                    //     $array['agoraToken']=$checkagora->token;
                    // }else{
                    //    $array['agoraToken']="";

                    // }
                $callcharge=CallChatHistory::where('lawyer_id',$orders->lawyer_id)->orderBydesc('id')->first();
                if(!empty($callcharge)){
                    $array['current_call_rate']=$callcharge->calls;
                }else{
                    $array['current_call_rate']=00;
                }
                $array['education_details']=$orders->education_details;
                $array['practice_area']=implode(',',$practice);
                $array['experience']=$orders->experience;
                $array['call_rate']=$orders->call_rate;
                
                $array['rating']=5;
                $array['user_name']="Anonymous";
                // $array['city']=$orders->city;
                // $array['language']=implode(',',$language);
                // $array=[];
                return response()->json(["status" => 200, "success"=> true, "message" =>"Call Details",'data'=>$array]);

            }else{
                return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
            }
        }else{
            return response()->json(["status" => 400, "success"=> false, "message" =>"You're not authorized!"]);

        }

    }

    public function getChathistory(){
        $user=Auth::user();
        if($user->user_type==1){
            $orders=Order::with('lawyerDetails')->where('user_id',$user->id)->where('call_type','0')->whereIn('status',['1','3'])->orderBydesc('id')->get();
            if(sizeof($orders)){
                foreach ($orders as $row) {
                    $chekrating=Review::where(['lawyer_id'=>$row->lawyer_id])->avg('rating');
                    $chekfav=Favorite::where(['user_id'=>$user->id,'lawyer_id'=>$row->lawyer_id,'status'=>'1'])->first();
                    $is_fav=0;
                    if(!empty($chekfav)){
                        $is_fav=1;
                    }
                    $row->user_name=$row->lawyerDetails->user_name?$row->lawyerDetails->user_name:"Anonymous";                    
                    if($row->call_type==1){
                        $row->call_type="Call";
                    }else{
                        $row->call_type="Chat";
                    }
                    $callcharge=CallChatHistory::where('lawyer_id',$row->lawyer_id)->orderBydesc('id')->first();
                    if(!empty($callcharge)){
                        $row->current_call_rate=$callcharge->calls;
                    }else{
                        $row->current_call_rate=00;
                    }
                    if($row->lawyerDetails->gender==1){
                        $row->gender="Male";
                        }elseif($row->lawyerDetails->gender==2){
                        $row->gender="Female";
                        }else{
                        $row->gender="Others";

                        }
                        $checkagora=AgoraTokenLawyer::where('user_id',$row->lawyer_id)->first();
                        if(!empty($checkagora)){
                        $row->agoraToken=$checkagora->token;
                        }else{
                            $row->agoraToken="";

                        }
                    $currentday=Carbon::now()->format('l');
                    if($currentday=="Monday"){
                        $today=1;
                    }elseif($currentday=="Tuesday"){
                        $today=2;
                    }elseif($currentday=="Wednesday"){
                        $today=3;
                    }elseif($currentday=="Thursday"){
                        $today=4;
                    }elseif($currentday=="Friday"){
                        $today=5;
                    }elseif($currentday=="Saturday"){
                        $today=6;
                    }else{
                        $today=7;
                    }               
                   
                    $currentTime=Carbon::now()->format('h:i:s');
                    $utcTime = Carbon::createFromFormat('h:i:s', $currentTime, 'UTC');                    
                    $istTime = $utcTime->setTimezone('Asia/Kolkata')->format('H:i:s');  
                    $checklawyer=Availability::where('user_id',$row->lawyer_id)->where('day_id',$today)->where('status','1')->first();
                    $row->online = "Offline";  
                    if(!empty($checklawyer)){
                        $checkavailability=AvailabilityTime::where('availability_id',$checklawyer->id)->where('from','<',$istTime)->where('to','>',$istTime)->where('status','1')->first();                        
                        if(!empty($checkavailability)){
                           $row->is_active=1;
                           $row->online="Online";
                        }else{
                            $checktime=AvailabilityTime::where('availability_id',$checklawyer->id)->where('from','>',$istTime)->where('status','1')->first();
                            if(!empty($checktime)){
                            
                            $currentTime = Carbon::now();
                            $istTime = $currentTime->setTimezone('Asia/Kolkata');
                            $timeDifference = $istTime->diff($checktime->from);


                                if($timeDifference->h < 1){                                    
                                    $row->online = 'online in ' . $timeDifference->i . ' min';
                                }else{
                                    if($timeDifference->i<10){
                                        $row->online ='Online in ' .$timeDifference->h.' :' .'0'.$timeDifference->i . ' min';
                                    }else{
                                       $row->online ='Online in ' .$timeDifference->h.' :' .$timeDifference->i . ' min'; 
                                    }
                                }
                                
                            }  
                            $row->is_active=0;
                        }

                                          
                    }else{
                        $row->is_active=0;
                                              
                    }      

                    $checkstatus=LawyerDetail::where('user_id',$row->lawyer_id)->first();  
                    if(!empty($checkstatus)){
                        $row->is_call=$checkstatus->is_call;
                        $row->is_chat=$checkstatus->is_chat;
                    }else{
                        $row->is_chat=0;
                        $row->is_call=0;
                    }

                    $row->deduction=$row->total_amount;
                    $row->duration=$row->duration;
                    $row->call_rate=$row->call_rate;
                    $row->is_favorite=$is_fav;
                    $row->rating=(string)round($chekrating,2);
                    // $time=Carbon::parse($row->call_start)->format('g:i:s A');
                    // $row->date=Carbon::parse($row->created_at)->format('M-d-Y').', '.$time;
                    $utcTime=Carbon::parse($row->created_at)->format('Y-m-d h:i:s');
                    $istTime = Carbon::parse($utcTime, 'UTC')->setTimezone('Asia/Kolkata');
                    $row->date=$istTime->format('M-d-Y h:i:s A');
                    $row->profile_img=$row->lawyerDetails->profile_img?imgUrl.'profile/'.$row->lawyerDetails->profile_img:user_img;
                    $data[]=$row;
                    unset($row->lawyerDetails);
                    unset($row->created_at);
                    unset($row->total_amount);
                    unset($row->user_id);
                    unset($row->call_initiate);
                    unset($row->call_start);
                    unset($row->call_end);
                    unset($row->file);
                }
                return response()->json(["status" => 200, "success"=> true, "message" =>"All chat history",'data'=>$data]);

            }else{
                return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
            }
        }elseif($user->user_type==2){
            $orders=Order::with('userDetails')->where('lawyer_id',$user->id)->where('call_type','0')->whereIn('status',['1','3'])->orderBydesc('id')->get();
            if(sizeof($orders)){
                foreach ($orders as $row) {
                    $chekrating=Review::where(['lawyer_id'=>$user->id])->avg('rating');                    
                    $isreviewcheck=Review::where(['order_id'=>$row->id])->first();
                    if(!empty($isreviewcheck)){
                        $row->is_review=1;
                    }else{
                        $row->is_review=0;
                    }
                    $chekfav=Favorite::where(['user_id'=>$user->id,'lawyer_id'=>$row->lawyer_id,'status'=>'1'])->first();
                    $is_fav=0;
                    if(!empty($chekfav)){
                        $is_fav=1;
                    }
                    $row->user_name=$row->userDetails->user_name?$row->userDetails->user_name:null;
                    if($row->call_type==1){
                        $row->call_type="Call";
                    }else{
                        $row->call_type="Chat";
                    }
                    $callcharge=CallChatHistory::where('lawyer_id',$row->lawyer_id)->orderBydesc('id')->first();
                    if(!empty($callcharge)){
                        $row->current_call_rate=$callcharge->calls;
                    }else{
                        $row->current_call_rate=00;
                    }
                    $row->deduction=$row->total_amount;
                    $row->duration=$row->duration;
                    $row->call_rate=$row->call_rate;
                   
                    if($row->userDetails->gender==1){
                        $row->gender="Male";
                        }elseif($row->userDetails->gender==2){
                        $row->gender="Female";
                        }else{
                        $row->gender="Others";

                        }
                    $row->rating=round($chekrating,2);
                    $utcTime=Carbon::parse($row->created_at)->format('Y-m-d h:i:s');
                    $istTime = Carbon::parse($utcTime, 'UTC')->setTimezone('Asia/Kolkata');
                    $row->date=$istTime->format('M-d-Y h:i:s A');
                    $row->profile_img=$row->userDetails->profile_img?imgUrl.'profile/'.$row->userDetails->profile_img:user_img;
                    $data[]=$row;
                    unset($row->lawyerDetails);
                    unset($row->created_at);
                    unset($row->total_amount);
                    unset($row->user_id);
                    unset($row->lawyer_id);
                    unset($row->call_initiate);
                    unset($row->call_start);
                    unset($row->call_end);
                    unset($row->file);
                    unset($row->userDetails->profile_img);
                }                

                return response()->json(["status" => 200, "success"=> true, "message" =>"All Chat history",'data'=>$data]);

            }else{
                return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
            }

        }else{
            return response()->json(["status" => 400, "success"=> false, "message" =>"You're not authorized!"]);
        }

    }

    public function getAllOrders($id=''){
        $user=Auth::user();        
        if($user->user_type==1){
            $orders=Order::with('lawyerDetails')->where('user_id',$user->id)->whereIn('status',['1','0','3'])->orderBydesc('id')->get();
            if(sizeof($orders)){
                foreach ($orders as $row) {
                    $chekrating=Review::where(['lawyer_id'=>$row->lawyer_id])->avg('rating');
                    
                    $chekfav=Favorite::where(['user_id'=>$user->id,'lawyer_id'=>$row->lawyer_id,'status'=>'1'])->first();
                    $is_fav=0;
                    if(!empty($chekfav)){
                        $is_fav=1;
                    }
                    $row->user_name=$row->lawyerDetails->user_name?$row->lawyerDetails->user_name:null;
                    // $row->first_name=$row->lawyerDetails->first_name?$row->lawyerDetails->first_name:null;
                    // $row->last_name=$row->lawyerDetails->last_name?$row->lawyerDetails->last_name:null;
                    if($row->call_type==1){
                        $row->call_type="Call";
                    }else{
                        $row->call_type="Chat";
                    }
                    $callcharge=CallChatHistory::where('lawyer_id',$row->lawyer_id)->orderBydesc('id')->first();
                    if(!empty($callcharge)){
                        $row->current_call_rate=$callcharge->calls;
                    }else{
                        $row->current_call_rate=00;
                    }
                    if($row->lawyerDetails->gender==1){
                        $row->gender="Male";
                        }elseif($row->lawyerDetails->gender==2){
                        $row->gender="Female";
                        }else{
                        $row->gender="Others";

                        }
                    $checkagora=AgoraTokenLawyer::where('user_id',$row->lawyer_id)->first();
                    if(!empty($checkagora)){
                    $row->agoraToken=$checkagora->token;
                    }else{
                        $row->agoraToken="";

                    }
                    $getorderreview="0.00";
                    
                    $row->deduction=$row->total_amount;
                    $row->duration=$row->duration;
                    $row->call_rate=$row->call_rate;
                    $row->is_favorite=$is_fav;
                    $row->online="Online in 20 mins";
                    $row->rating=(string)round($chekrating,2);
                    $time=Carbon::parse($row->call_start)->format('g:i:s A');
                    $row->date=Carbon::parse($row->created_at)->format('M-d-Y').', '.$time;
                    $row->profile_img=$row->lawyerDetails->profile_img?imgUrl.'profile/'.$row->lawyerDetails->profile_img:user_img;
                    $data[]=$row;
                    unset($row->lawyerDetails);
                    unset($row->created_at);
                    unset($row->total_amount);
                    unset($row->user_id);
                    unset($row->call_initiate);
                    unset($row->call_start);
                    unset($row->call_end);
                    unset($row->file);
                }

                return response()->json(["status" => 200, "success"=> true, "message" =>"All Order history",'data'=>$data]);

            }else{
                return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
            }
        }elseif($user->user_type==2){
            $orders=Order::with('userDetails')->where('lawyer_id',$user->id)->whereIn('status',['1','0','3'])->orderBydesc('id')->get();     
            if(sizeof($orders)){
                foreach ($orders as $row) {
                    $chekrating=OrderReview::where('order_id',$row->id)->first(); 

                    $isreviewcheck=Review::where('order_id',$row->id)->first();
                    if(!empty($isreviewcheck)){
                        $row->is_review=1;
                    }else{
                        $row->is_review=0;
                    } 
                    $chekfav=Favorite::where(['user_id'=>$user->id,'lawyer_id'=>$row->lawyer_id,'status'=>'1'])->first();
                    $is_fav=0;
                    if(!empty($chekfav)){
                        $is_fav=1;
                    }
                    $row->user_name=$row->userDetails->user_name?$row->userDetails->user_name:null;
                    if($row->call_type==1){
                        $row->call_type="Call";
                    }else{
                        $row->call_type="Chat";
                    }
                    $callcharge=CallChatHistory::where('lawyer_id',$row->lawyer_id)->orderBydesc('id')->first();
                    if(!empty($callcharge)){
                        $row->current_call_rate=$callcharge->calls;
                    }else{
                        $row->current_call_rate=00;
                    }
                    $row->deduction=$row->total_amount;
                    $row->duration=$row->duration;
                    $row->call_rate=$row->call_rate;
                    // $row->is_favorite=$is_fav;
                    // $row->online="Online in 20 mins";
                    if($row->userDetails->gender==1){
                        $row->gender="Male";
                    }elseif($row->userDetails->gender==2){
                        $row->gender="Female";
                    }else{
                        $row->gender="Others";

                    }
                    $getorderreview=OrderReview::where('order_id',$row->id)->sum('rating');

                    $row->lawyer_rating=(string)$getorderreview;
                    $rating=0;
                    $getrating=Review::select('rating')->where('order_id',$row->id)->first();
                    if(!empty($getrating)){
                        $rating=$getrating->rating;
                    }
                    $row->rating=(string)$rating;
                    $time=Carbon::parse($row->call_start)->format('g:i:s A');
                    $row->date=Carbon::parse($row->created_at)->format('M-d-Y').', '.$time;
                    $row->profile_img=$row->userDetails->profile_img?imgUrl.'profile/'.$row->userDetails->profile_img:user_img;
                    $data[]=$row;
                    unset($row->lawyerDetails);
                    unset($row->created_at);
                    unset($row->total_amount);
                    unset($row->user_id);
                    unset($row->lawyer_id);
                    unset($row->call_initiate);
                    unset($row->call_start);
                    unset($row->call_end);
                    unset($row->file);
                    // unset($row->userDetails->profile_img);
                }                

                return response()->json(["status" => 200, "success"=> true, "message" =>"All Order list",'data'=>$data]);

            }else{
                return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
            }
        }else{
            if($id!=''){
                $checkuser=User::where('id',$id)->first();
                if(!empty($checkuser)){
                    if($checkuser->user_type==2){
                        $orders=Order::with('userDetails')->whereIn('status',['1','0','3'])->where('lawyer_id',$id)->orderBydesc('id')->get();  

                        $msg="Lawyer's call/chat history";
                    }else{
                        $orders=Order::with('lawyerDetails')->whereIn('status',['1','0','3'])->where('user_id',$id)->orderBydesc('id')->get();
                        $msg="User's call/chat history";
                    }
                }else{
                    return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
                }
            }else{
                $orders=Order::with('userDetails','lawyerDetails')->whereIn('status',['1','0','3'])->orderBydesc('id')->get();
                $msg="All call/chat history";
            }
                       
                if(sizeof($orders)){
                    foreach ($orders as $row){                        
                        $array['user_name']=$row->userDetails->user_name?$row->userDetails->user_name:"Anonymous";
                        $array['lawyer_name']=$row->lawyerDetails->user_name?$row->lawyerDetails->user_name:"Anonymous";
                        if($row->call_type==1){
                            $array['call_type']="Call";
                        }else{
                            $array['call_type']="Chat";
                        }                    
                        $array['id']=$row->id;
                        $array['amount']=$row->total_amount;
                        $array['duration']=$row->duration;
                        $array['call_rate']=$row->call_rate;
                        $array['date']=$row->date?$row->date:Carbon::parse($row->created_at)->format('Y-m-d');
                        
                        $data[]=$array;
                        unset($row->userDetails);
                        unset($row->lawyerDetails);
                    }   
                    return response()->json(["status" => 200, "success"=> true, "message" =>$msg,'data'=>$data]);

                }else{
                    return response()->json(["status" => 400, "success"=> false, "message" =>"Data not found !"]);
                }
            

        }        

    }


    public function sendCallRequest(Request $request)
    {
        
        $user=Auth::user();


        if(!$request->lawyer_id)
        {
            return response()->json(["status" => 400, "success"=> false, "message" =>"Lawyer Id is required!"]);

        }

        $getrate=LawyerDetail::select('call_charge')->where('user_id',$request->lawyer_id)->first();
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


                        if($user->user_type == '1')
                        {
                        
                           $orderRunning=Order::where('user_id',$user->id)->where('lawyer_id',$request->lawyer_id)->where('total_amount',0)->where('end_by',0)->where('status','0')->first();
                            if(empty($orderRunning) )
                            {
                                $order=new Order();
                                $order->user_id=$user->id;
                                $order->lawyer_id=$request->lawyer_id;                   
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
                                $array['my_date_apurva']=$orderRunning;
                                if($query==true){
                                    
                                    $updatequery=LawyerDetail::where('user_id',$request->lawyer_id)->update([
                                    "is_available"=>'0'
                                    ]);


                                    $getfcm=User::select('fcm_token','first_name','last_name','user_name')->where('id',$request->lawyer_id)->first();

                
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
                                          }else{
                                            $title ="You have call notification from ". $user->user_name;
                                            $body ="You have a new call notification " . $user->user_name;
                                            $type =1;
                                            $msg="Notification successfully sent";                                      
                                          }
                    
                                        $data=array(
                                          'title' =>$title,
                                          'body' =>$body,
                                          'type' =>$type,
                                          'profile_img' =>$user->profile_img?imgUrl.'profile/'.$user->profile_img:default_img,
                                          "user_name"=>$user->user_name?$user->user_name:"Anonymous",
                                          "user_id"=>$user->id,
                                          "order_id"=>$order->id,
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
                                        // return $dta;
                                        if($dta->success==1){                                   
                                          return response()->json(['status'=>200,'success' => true, 'message' =>$array]);
                                        }else{
                                          return response()->json(['status'=>400,'success' => false, 'message' => "failed try again"]);
                    
                                        }
                                        

                                    return response()->json(["status" => $status, "success"=> true, "message" =>$msg,'data'=>$array]);
                                }   
                            }else{

                                $array['id']=$orderRunning->id;
                                $array['max_time']=(int)$maxtime;
                                $array['my_date_apurva']=$orderRunning;

                                $msg="Order Successfully again created";
                                return response()->json(["status" => 200, "success"=> true, "message" =>$msg,'data'=>$array]);

                            }

        }else{
            return response()->json(["status" => 400, "success"=> false, "message" =>"User Login !"]);
        }

    }



}
