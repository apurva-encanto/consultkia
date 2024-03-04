<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Auth;
use Validator;
use App\Models\Availability;
use App\Models\AvailabilityTime;
use App\Models\LawyerDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Schedule;
use App\Models\Order;
use App\Models\CallChatHistory;
use App\Models\Slot;
use DateTime;
class AvailabilityController extends ApiController
{
    public function __construct()
    {
        Auth::shouldUse('api');

    }
        /* add availability start*/

        public function addavailability(Request $request){
            $validator = Validator::make($request->all(),[
                'is_chat'=>"required",
                'is_call'=>"required",
                'call_charge'=>"required",
                //'chat_charge'=>"required",
                'availability' => 'required',
            ]);

            if($validator->fails()){
                return response()->json(["status" => 400,"success"=>false, "message" => $validator->messages()->first()]);
            }   
            $user=Auth::user(); 


               


            $checkuser=LawyerDetail::where('user_id',$user->id)->first();
            if(!empty($checkuser)){

                                
                
                $updateDetail=LawyerDetail::find($checkuser->id);
                $updateDetail->is_chat=$request->is_chat;
                $updateDetail->is_call=$request->is_call;
                $updateDetail->call_charge=$request->call_charge;
                $updateDetail->chat_charge=$request->call_charge;
                $updateQuery=$updateDetail->save();
                if($updateQuery==true){
                    if($request->is_call==1 || $request->is_call==1){
                        $callchathistory=new CallChatHistory();
                        if($request->is_call==1){
                            $callchathistory->calls=$request->call_charge;
                        }
                        if($request->is_call==1){
                            $callchathistory->chats=$request->call_charge;
                        }
                        $callchathistory->lawyer_id=$user->id;
                        $querycallchathistory=$callchathistory->save();
                    
                    }
                    

                    $query='';
                    foreach($request->availability as $value){                       
                        $check=Availability::where(['user_id'=>$user->id,'day_id'=>$value['day_id']])->first();
                        if(!empty($check)){
                            if($value['status']==1){  
                                $check1=Availability::find($check->id);
                                $check1->user_id=$user->id;
                                $check1->day_id=$value['day_id'];
                                $check1->status=(string)$value['status'];                        
                                $update=$check1->save();
                                $update=AvailabilityTime::where(['availability_id'=>$check->id])->update(['status'=>'0']);
                                foreach($value['time'] as $row){   
                                    $save= new AvailabilityTime();
                                    $save->availability_id=$check->id;
                                    $fromDatetime = Carbon::createFromFormat('h:i A',$row['from']);
                                    $toDatetime = Carbon::createFromFormat('h:i A',$row['to']);                                    
                                    $fromtime = $fromDatetime->format('H:i:s');
                                    $totime = $toDatetime->format('H:i:s');


                                    $save->from =$fromtime;
                                    $save->to=$totime;
                                    $query= $save->save(); 
                                }
                            }else{
                                $check1=Availability::find($check->id);
                                $check1->user_id=$user->id;
                                $check1->day_id=$value['day_id'];
                                $check1->status=(string)$value['status'];                        
                                $update=$check1->save();
                                $update=AvailabilityTime::where(['availability_id'=>$check->id])->update(['status'=>'0']);
                                
                            }

                        }else{
                            
                            if($value['status']==1){   
                                $check= new Availability();
                                $check->user_id=$user->id;
                                $check->day_id=$value['day_id'];
                                $check->status=(string)$value['status'];                        
                                $query=$check->save();
                                foreach($value['time'] as $row){                            
                                    $checkAvailability=AvailabilityTime::where(['availability_id'=>$check->id,"from" => $row['from'],"to" => $row['to']])->first();   
                                    $fromDatetime = Carbon::createFromFormat('h:i A',$row['from']);
                                    $toDatetime = Carbon::createFromFormat('h:i A',$row['to']);                                    
                                    $fromtime = $fromDatetime->format('H:i:s');
                                    $totime = $toDatetime->format('H:i:s');           
                                    if(!empty($checkAvailability)){
                                            
                                        $query=AvailabilityTime::where(['availability_id'=>$checkAvailability->id])->update([
                                            "from" =>$fromtime,
                                            "to" =>$totime
                                        ]);
                                    }else{
                                        $query=AvailabilityTime::create([
                                        'availability_id'=>$check->id,
                                             "from" =>$fromtime,
                                            "to" =>$totime
                                        ]);
                                    }
                                
                                }
                            }else{
                                $check= new Availability();
                                $check->user_id=$user->id;
                                $check->day_id=$value['day_id'];
                                $check->status=(string)$value['status'];                        
                                $query=$check->save(); 
                            }
                        }                    
                    }
                    if($query){  
                        return response()->json(["status" => 200,"success"=>true, "message" =>"Availability successfully added"]);
                    }else{ 
                        return response()->json(["status" => 400,"success"=>false, "message" =>"Availability could not be updated "]);
                    }
                }else{
                    return response()->json(["status" => 400,"success"=>false, "message" =>"Failed try again"]);
                }                    
            }else{
                return response()->json(["status" => 400,"success"=>false, "message" =>"Failed try again"]);
            }
                
        }
        /* add availability end here */

        /* check availability */
        public function checkavailability($id=''){          
            $user=Auth::user();
            if(!empty($user)){ 
                $checkLawyer=LawyerDetail::where('user_id',$user->id)->first();
                if(!empty($checkLawyer)){


                          
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

                    $avilability=Availability::select('id','day_id','user_id','status')->where(['user_id'=>$user->id])->get();
                    if(sizeof($avilability)){                    
                        foreach($avilability as $row){
                            $chekctime=AvailabilityTime::select('from','to')->where('availability_id', $row->id)->where('status','1')->get();
                            if(count($chekctime)){
                                $data=[];
                                foreach($chekctime as $val){
                                    $val->from=Carbon::parse($val->from)->format('g:iA');
                                    $val->to=Carbon::parse($val->to)->format('g:iA');
                                    $data[]=$val;
                                }
                            }else{
                                $data=[];
                            }                        
                        $row->status=(int)$row->status;
                            $row->time=$data;
                            $data1[]= $row;
                        }
                        $array['is_call']=$checkLawyer->is_call;
                        $array['is_chat']=$checkLawyer->is_chat;
                        $array['call_charge']=$checkLawyer->call_charge;
                        // $array['chat_charge']=$checkLawyer->chat_charge;
                        $array['availability']=$data1;
                        return response()->json(["status" => 200,"success"=>true,"message"=>"Availability list","data"=>$array]);
                    }else{
                        return response()->json(["status" => 400,"success"=>false,"message"=>"Data not found"]);                    
                    }
                }else{
                    return response()->json(["status" => 400,"success"=>false,"message"=>"User not found"]);
                }
                
              
            }else{  
                return response()->json(["status" => 400,"success"=>false,"message"=>"Login first to get access this"]);
            }                
        
        }
}
