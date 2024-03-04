<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Validator;
use Session;
use Auth;
use Carbon\Carbon;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\LawyerDetail;
use App\Models\DefaultCard;
use App\Models\Order;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Ticketlist;
use App\Models\Transaction;
use App\Models\Notification;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use App\Models\Slot;
use App\Models\Rate;
use App\Models\Rvndetail;
use App\Models\WithdrawalRequest;

use App\Models\Product;
use App\Models\Cart;
use Stripe;
use Stripe\StripeClient;

class PaymentController extends ApiController
{
    private $stripes;

    public function __construct()
    {        
       
        Auth::shouldUse('api');
       
            $api_key = '399990CC1EF48CB9D008E585D74FDF';
            $merchant_id = '13186';
    }
   
   
    


    public function addtransaction(Request $request){    
        $validator = Validator::make($request->all(), [
                'order_id'=>"required",                    
                'order_status'=>"required",                    
                'txn_id'=>"required",                    
                'amount'=>"required",                    
                'dateTime'=>"required",                   
                'transaction_method'=>"",                   
               
        ]);        
             
        if($validator->fails()){
            return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
        }
        $user=Auth::user();
                if(!empty($user)){
            $addtransaction=New Transaction();
            $addtransaction->order_number=$request->order_id;
            $addtransaction->txn_id=$request->txn_id;
            $addtransaction->dateTime=Carbon::parse($request->dateTime)->format('Y-m-d h:i');
            $addtransaction->from=$user->id;
            $addtransaction->to=$user->id;
            $addtransaction->sender_type="4";
            $addtransaction->type="1";
            $addtransaction->reciever_type="4";
            $addtransaction->amount=$request->amount;
            if($request->transaction_method!=''){
                $addtransaction->transaction_method=$request->transaction_method;
            }

            $addtransaction->status=$request->order_status;
            // return $addtransaction;

            $query=$addtransaction->save();
            if($query==true){
                // return $query;
                $updateWallet=User::where('id',$user->id)->update([
                    'wallet_ballance'=>$user->wallet_ballance + $request->amount,
                ]);
                return response()->json(["status" => 200,"success"=>true,"message"=>"Your wallet has been successfully topped up"]);
            }else{
                return response()->json(["status" => 400,"success"=>false,"message"=>"Login first to get access this"]);
            }
        }else{
             return response()->json(["status" => 400,"success"=>false,"message"=>"Login first to get access this"]);
        }
        
    
    }


    public function walletBalance(){    
       
        $user=Auth::user();
        if(!empty($user)){     
            
            
            if($user->user_type=='2')
            {
                $orderRunning=Order::where('lawyer_id',$user->id)->where('total_amount',0)->where('end_by',0)->where('status','0')->first();

                if(!empty($orderRunning))
                {
                    $timestamp1 = strtotime(date('Y-m-d H:i:s'));
                    $timestamp2 = strtotime($orderRunning->created_at);
    
                    // Calculate the difference in seconds
                    $timeDifference = abs($timestamp1 - $timestamp2);
    
                    if ($timeDifference > 40) {
                        LawyerDetail::where('user_id',$user->id)->update(['is_available'=>1]);
                        Order::where('id',$orderRunning->id)->delete();
                    }
                   
                }
                          
            }
            return response()->json(["status" => 200,"success"=>true,"message"=>"Wallet balance",'data'=>$user->wallet_ballance]);
            
        }else{
             return response()->json(["status" => 400,"success"=>false,"message"=>"Login first to get access this"]);
        }
        
    
    }


    public function testApi(Request $request){
          $user=Auth::user();
          if(!empty($user)){
            $api_key="399990CC1EF48CB9D008E585D74FDF";
            $key='consultkiya';  

            // $currentDateTime = Carbon::now();
            // $epochTimestamp = $currentDateTime->timestamp;
            // // return $epochTimestamp;
            // $order="order_number-".$epochTimestamp;
            $order="order".rand(1111,9999);
            $price=$request->amount;
            $data = [
                "order_id" =>$order,
                "amount" => $price,
                "customer_id" =>(string)$user->id,
                "customer_email" => "encantodeveloper@gmail.com",
                "customer_phone" => $user->phone,
                "payment_page_client_id" => "consultkiya",
                "action" => "paymentPage",
                "return_url" =>url('/api/peymenthandle'),
                "description" => "Complete your payment",
                "first_name" =>$user->name,
                "last_name" => ""
            ];     
            // return $data;
            $data =json_encode($data);

            $ch = curl_init();
            // $data=json_encode($request->all());
            curl_setopt($ch, CURLOPT_URL, 'https://api.juspay.in/session');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            $headers = array();
            $headers[] = 'Authorization: Basic ' . base64_encode("399990CC1EF48CB9D008E585D74FDF:");
            $headers[] = 'X-Merchantid: consultkiya';
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'wbhook_url: '.url('/api/peymenthandle');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);
            return $result;
          }else{
            return response()->json(['status'=>400,"success"=>false,'message'=>"you're not authorized"]);
          }
            
    }

    public function checkstatus($id=''){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.juspay.in/orders/' .$id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $headers = array();
        $headers[] = 'version: 2023-06-30';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Authorization: Basic ' . base64_encode("399990CC1EF48CB9D008E585D74FDF:");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }


    public function peymenthandle(Request $request)
    {
        // echo "<pre>>";
        // print_r($_POST);
        // print_r($request['order_id']);

        if($request['status']=="PENDING_VBV"){
            echo "pending";
        }else{
            echo "success";
        }
        // $res=$this->checkstatus($)
        die;

    }

    public function adminWithdrawalRequest()
    {
       $withdrawal= WithdrawalRequest::select('withdrawal_requests.*','users.first_name','users.last_name','wallet_ballance','phone','user_name')->leftjoin('users','users.id','withdrawal_requests.user_id')->get();
       return response()->json(["status" => 200,"success"=>true,"message"=>"Withdrawal Request",'data'=>$withdrawal]);
    }

    public function approveWithdrawalRequest($id,$status)
    {

        $withdrawal= WithdrawalRequest::find($id);
        if($withdrawal)
        {
            if($withdrawal->status==1){
          return response()->json(["status" => 400,"success"=>true,"message"=>"Request Already Approved",'data'=>[]]);

            }else{
           $userDetails=User::select('wallet_ballance')->find($withdrawal->user_id);

           if($userDetails->wallet_ballance >= $withdrawal->amount)
           {
           
            
                $withdrawal->status=(string)$status;               
                $withdrawal->save();  

                if($status==1)
                {
                    $updatewallet=User::where('id',$withdrawal->user_id)->update([
                        'wallet_ballance'=>$userDetails->wallet_ballance-$withdrawal->amount
                    ]);
                }

           
         

                Transaction::where('from',$withdrawal->user_id)->where('to',$withdrawal->user_id)->where('status',"0")->update(['status'=>(string)$status,"dateTime"=>Carbon::now()->format('Y-m-d h:i')]);
                // $addtransaction=New Transaction();
                // $addtransaction->order_number=generateOrderNumber();
                // $addtransaction->from=$withdrawal->user_id;
                // $addtransaction->to=$withdrawal->user_id;
                // $addtransaction->sender_type="5";
                // $addtransaction->reciever_type="5";
                // $addtransaction->txn_id=null;
                // $addtransaction->amount=$withdrawal->amount;
                // $addtransaction->status="1";
                // $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i');
                // $addtransaction->save();   
                
                return response()->json(["status" => 200,"success" =>true,"message" =>"Withdrawal request has successfully approved"]);
             

          }else{
          return response()->json(["status" => 400,"success"=>true,"message"=>"Lawyer won't have sufficient Balance",'data'=>[]]);

           }
        }
        }else{
          return response()->json(["status" => 400,"success"=>true,"message"=>"No request found",'data'=>[]]);

        }

       
    }

    
}