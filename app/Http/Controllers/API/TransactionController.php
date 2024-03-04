<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Validator;
use Session;
use Auth;
use Twilio\Rest\Client;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Rate;
use App\Models\Pet;
use App\Models\Review;
use App\Models\LawyerDetail;
use App\Models\Transaction;
use App\Models\WithdrawalRequest;

class TransactionController extends ApiController
{
    public function __construct(){
        Auth::shouldUse('api');
    }

    public function transactionList($type=''){
       
        $user=Auth::user();
        if(!empty($user)){
            if($user->user_type==1){
                // type 1-paid,2-added to wallet,3-refrence earn,else all
                if($type==1){
                    $getAllTransaction=Transaction::with('lawyerDetails')->where('from',$user->id)->whereIn('sender_type',['2','5'])->orderbydesc('id')->paginate(10);
                    $msg="All Pay Transactions for user";
                }elseif($type==2){
                    $getAllTransaction=Transaction::with('lawyerDetails')->where('to',$user->id)->whereIn('reciever_type',['4'])->orderbydesc('id')->paginate(10);
                    $msg="All Added Transactions for user";
                }elseif($type==3){
                    $getAllTransaction=Transaction::with('lawyerDetails')->where('to',$user->id)->where('reciever_type','1')->orWhere('from',$user->id)->where('reciever_type','1')->orderbydesc('id')->paginate(10);
                    $msg="All Earning Transactions for user";
                    // return  $getAllTransaction;
                }else{
                    $getAllTransaction=Transaction::with('lawyerDetails')->where('to',$user->id)->orWhere('from',$user->id)->orderbydesc('id')->paginate(10);
                    $msg="All Transactions for user";
                }



                if(sizeof($getAllTransaction)){
                    foreach ($getAllTransaction as $row) {
                        // return $row->sender_type;
                        if($row->sender_type == "2" || $row->sender_type == "5"){
                            if($row->sender_type == "2"){
                                $row->title="Paid For Order";
                                $row->transaction_type=1;
                            }else{                               
                                $row->title="Add To Wallet";
                            }
                            
                            $row->type=0;
                        }else{
                            if($row->reciever_type=="1"){
                                $row->title="Refrence earning";
                                $row->transaction_type=3;
                                
                            }else{
                                $row->title="Add To Wallet";
                                $row->transaction_type=2;
                            }
                           $row->type=1;
                        }
                        if($type!="1" || $type!="2"){
                           if($row->reciever_type=="1"){
                                $getuser=User::where('id',$row->from)->first();
                                if(!empty($getuser)){
                                    $row->user_name=$getuser->user_name;
                                    $row->phone=$getuser->phone;
                                }
                           }else{
                                $row->user_name=null;
                                $row->phone=null;
                                
                           }
                            
                            
                         }
                        $asiaKolkataDateTime = $row->created_at;
                        $carbonAsiaKolkata = Carbon::createFromFormat('Y-m-d H:i:s', $asiaKolkataDateTime, 'Asia/Kolkata');
                        $carbonUtc = $carbonAsiaKolkata->setTimezone('UTC');
                        $carbonUtc = Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at);
                        $carbonAsiaKolkata = $carbonUtc->setTimezone('Asia/Kolkata');

                        $date = Carbon::parse($carbonAsiaKolkata)->format('M-d g:i A');
                        // return $date;
                        $row->time=$date;
                        $row->transaction_method=$row->transaction_method;
                        $data[]=$row;
                        unset($row->lawyerDetails);
                        unset($row->updated_at);
                        unset($row->from);
                        unset($row->to);
                        unset($row->sender_type);
                        unset($row->created_at);
                        unset($row->reciever_type);

                    }
                            $finalData['status'] = 200;
                            $finalData['success'] = true;
                            $finalData['message'] =$msg;
                            $finalData['data'] =!empty($data)?$data:array();
                            $finalData['currentPage'] = $getAllTransaction->currentPage();
                            $finalData['last_page'] = $getAllTransaction->lastPage();
                            $finalData['total_record'] = $getAllTransaction->total();
                            $finalData['per_page'] = $getAllTransaction->perPage();
                        return response($finalData);
                    // return response()->json(["status" => 200,"success"=>true,"message"=>$msg,'data'=>$data]);
                }else{
                    $finalData['status'] = 400;
                    $finalData['success'] = false;
                    $finalData['message'] ="Transactions not found";
                    $finalData['data'] =array();
                    $finalData['currentPage'] = 1;
                    $finalData['last_page'] = 1;
                    $finalData['total_record'] = 0;
                    $finalData['per_page'] = 10;
                    return response($finalData);
                    
                }
                
            }else{
                // type 1-received,2-withdrawal to wallet,3-refrence earn,else all
                if($type==1){
                    $getAllTransaction=Transaction::with('userDetails')->where('to',$user->id)->whereIn('reciever_type',['3'])->orderbydesc('id')->paginate(10);
                    $msg="All Received Transactions for lawyer";
                }elseif($type==2){
                    $getAllTransaction=Transaction::with('userDetails')->where('from',$user->id)->where('sender_type','5')->where('reciever_type','5')->orderbydesc('id')->paginate(10);
                    // return $getAllTransaction;
                    $msg="All Withdrawal Transactions for lawyer";
                }elseif($type==3){
                    $getAllTransaction=Transaction::with('userDetails')->where('to',$user->id)->whereIn('reciever_type',['1'])->orderbydesc('id')->paginate(10);
                    $msg="All Earning Transactions for lawyer";
                }else{
                    $getAllTransaction=Transaction::with('userDetails')->where('to',$user->id)->orWhere('from',$user->id)->orderbydesc('id')->paginate(10);
                    $msg="All Transactions for lawyer";
                }  
              
                if(sizeof($getAllTransaction)){
                    foreach ($getAllTransaction as $row) {                        
                        if($row->sender_type == "5"){                            
                            $row->title="Wallet Withdrawal";     
                            $row->transaction_type=2;  
                            $getdetails=LawyerDetail::where('user_id',$user->id)->first();   
                            if(!empty($getdetails)){
                                $row->banck_name=$getdetails->bank_name;  
                                $last4Characters = substr($getdetails->ac_no, -4);                     
                                $row->ac_no="xxxxxxxx".$last4Characters;
                            }else{

                                $row->banck_name=2;                       
                                $row->transaction_type=2;                       
                            }              
                            $row->type=0;
                        }
                        if($row->reciever_type == "1" || $row->reciever_type == "3"){                            
                            if($row->reciever_type == "1"){
                                $row->title="Refrence Earning";
                                $row->transaction_type=3;
                                
                            }else{
                                $row->title="Received For Order";
                                $row->transaction_type=1; 
                            }
                            $row->type=1;
                        }
                           
                            $getuser=User::where('id',$row->from)->first();
                            if(!empty($getuser)){
                                $row->user_name=$getuser->user_name;
                                $row->phone=$getuser->phone;
                            }
                        $asiaKolkataDateTime = $row->created_at;
                        $carbonAsiaKolkata = Carbon::createFromFormat('Y-m-d H:i:s', $asiaKolkataDateTime, 'Asia/Kolkata');
                        $carbonUtc = $carbonAsiaKolkata->setTimezone('UTC');
                        $carbonUtc = Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at);
                        $carbonAsiaKolkata = $carbonUtc->setTimezone('Asia/Kolkata');

                        $date = Carbon::parse($carbonAsiaKolkata)->format('M-d g:i A');
                        // return $date;
                        $row->time=$date;
                        $data[]=$row;
                        unset($row->userDetails);
                        unset($row->updated_at);
                        unset($row->from);
                        unset($row->to);
                        unset($row->sender_type);
                        unset($row->created_at);
                        unset($row->reciever_type);

                    }
                        $gettotalearning=Transaction::where('to',$user->id)->sum('amount');
                        $finalData['status'] = 200;
                        $finalData['success'] = true;
                        $finalData['total_earning'] = $gettotalearning;
                        $finalData['current_ballance'] = $user->wallet_ballance;
                        $finalData['message'] =$msg;
                        $finalData['data'] =!empty($data)?$data:array();
                        $finalData['currentPage'] = $getAllTransaction->currentPage();
                        $finalData['last_page'] = $getAllTransaction->lastPage();
                        $finalData['total_record'] = $getAllTransaction->total();
                        $finalData['per_page'] = $getAllTransaction->perPage();
                       return response($finalData);
                    // return response()->json(["status" => 200,"success"=>true,"message"=>$msg,'data'=>$data]);
                }else{
                    $gettotalearning=Transaction::where('to',$user->id)->sum('amount');
                    $finalData['status'] = 400;
                    $finalData['success'] = false;
                    $finalData['total_earning'] = $gettotalearning;
                    $finalData['current_ballance'] = $user->wallet_ballance;
                    $finalData['message'] ="Transactions not found";
                    $finalData['data'] =array();
                    $finalData['currentPage'] = 1;
                    $finalData['last_page'] = 1;
                    $finalData['total_record'] = 0;
                    $finalData['per_page'] = 10;
                    return response($finalData);

                }
            }

        }else{
            return response()->json(["status" => 400,"success"=>false,"message"=>"You're not authorized"]);
        }
    }

    public  function withdrawalRequest(Request $request){
        $validator = Validator::make($request->all(), [ 
           'amount' => 'required',         
        ]);
        if($validator->fails()){
            return response()->json(["status" => 400,"success" => false,"message" => $validator->errors()->first()]);
        }   
        $user=Auth::user();
        if($user->user_type==2){

            $withdraw=WithdrawalRequest::where('user_id',$user->id)->where('status','0')->first();
            if(empty($withdraw))
            {
                $withdrawal=new WithdrawalRequest();
                $withdrawal->amount=$request->amount;
                $withdrawal->user_id=$user->id;
                $query=$withdrawal->save();
                if($query==true){

                    $addtransaction=New Transaction();
                    $addtransaction->order_number=generateOrderNumber();
                    $addtransaction->from=$user->id;
                    $addtransaction->to=$user->id;
                    $addtransaction->sender_type="5";
                    $addtransaction->reciever_type="5";
                    $addtransaction->txn_id=null;
                    $addtransaction->amount=$request->amount;
                    $addtransaction->status="0";
                    $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i');
                    $addtransaction->save();   

                    // $updatewallet=User::where('id',$user->id)->update([
                    //     'wallet_ballance'=>$user->wallet_ballance-$request->amount
                    // ]);
                    return response()->json(["status" => 200,"success" =>true,"message" =>"Your withdrawal request has been send to Admin. It will take 3-5 working days to credit in your account"]);
    
                }else{
                    return response()->json(["status" => 400,"success" => false,"message" =>"failed try again"]);
                }

            }else{
            return response()->json(["status" => 201,"success" => false,"message" =>"Request Already Send"]);

            }
        
        }else{
            return response()->json(["status" => 400,"success" => false,"message" =>"You're not authorized"]);
        }
    }

    public  function approvedWithdrawal(Request $request){
        $validator = Validator::make($request->all(), [ 
           'id' => 'required',         
        ]);
        if($validator->fails()){
            return response()->json(["status" => 400,"success" => false,"message" => $validator->errors()->first()]);
        }   
        $user=Auth::user();
        if($user->user_type==3){
            // return   $order=generateOrderNumber();
            $check= WithdrawalRequest::where(['id'=>$request->id,'status'=>'0'])->first();
            if(!empty($check)){
                $withdrawal=WithdrawalRequest::find($request->id);               
                $withdrawal->status='1';               
                $query=$withdrawal->save();           
                if($query==true){      
                    $addtransaction=New Transaction();
                    $addtransaction->order_number=generateOrderNumber();
                    $addtransaction->from=$check->user_id;
                    $addtransaction->to=$check->user_id;
                    $addtransaction->sender_type="5";
                    $addtransaction->reciever_type="5";
                    $addtransaction->txn_id=$request->txn_id;
                    $addtransaction->amount=$check->amount;

                    $addtransaction->status="1";
                    $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i');
                    $query=$addtransaction->save();         
                    return response()->json(["status" => 200,"success" =>true,"message" =>"Withdrawal request has successfully approved"]);

                }else{
                    return response()->json(["status" => 400,"success" => false,"message" =>"failed try again"]);
                }
            }else{
                return response()->json(["status" => 400,"success" => false,"message" =>"Invalid request ID"]);
            }
        }else{
            return response()->json(["status" => 400,"success" => false,"message" =>"You're not authorized"]);
        }
    }
   
}
