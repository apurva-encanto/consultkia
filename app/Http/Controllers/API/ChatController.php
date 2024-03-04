<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Validator;
use Session;
use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Chat;
use App\Models\Ticketlist;
use App\Models\Notification;
use App\Models\Ticketlistimage;
use App\Models\ChatCategory;
use Haruncpi\LaravelIdGenerator\IdGenerator;
// use App\Models\Notification;



use App\Models\ChatFile;
class ChatController extends ApiController
{
    public function __construct()
    {        
        Auth::shouldUse('api');
    }

    public function createTicket(Request $request)
    {
        // user_type 3-admin
        $user=Auth::user();

        if($user->user_type =="3"){
          $validator = Validator::make($request->all(), [ 
            'subject'=>'',
            'discription' => 'required',                    
            ]);  
        }else{
            $validator = Validator::make($request->all(), [                                      
            'category_id' => 'required',
            'discription' => 'required',
            ]);  
        }
        
        if($validator->fails()){
            return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
        }
            $ticket = new Ticketlist();
            $subject=$request->subject?$request->subject:'';            
            
            $tick=rand(10000,99999);
            $ticket->ticket_id="Ticket-".$tick;
            $ticket->user_id=$user->id;             
            $ticket->subject=$subject;
            $ticket->category_id=$request->category_id;
            $ticket->discription=$request->discription;
            $query=$ticket->save();
            if($query==true){             
                return response()->json(["status" => 200,"success"=>true, "message" =>"Ticket locked"]);
            }else{
                return response()->json(["status" => 400,"success"=>false, "message" =>"failed try again"]);
            }
            
            

    }

    public function addChat(Request $request){
        // user_type 3-admin,5-business partner
        $user=Auth::user();
        if($user->user_type==3){
          $validator = Validator::make($request->all(), [ 
            'ticket_id'=>'required',                            
            'description' => '',                    
            ]);  
        }else{
            $validator = Validator::make($request->all(),[                                     
            'description' =>'' ,
            'ticket_id'=>'required',
            ]);  
        }
        
        if($validator->fails()){
            return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
        } 
        $user=Auth::user();
        if($user->user_type=='3'){
            $getuser_id=Ticketlist::select('user_id')->where('id',$request->ticket_id)->first();
            if(!empty($getuser_id)){
                $user_id=$getuser_id->user_id; 
            }else{
                $user_id=null; 
            }
            $chat =new Chat();
            $chat->ticket_id=$request->ticket_id;
            $chat->sender_id=$user->id;
            $chat->reciver_id= $user_id;                    
            $chat->description=$request->description?$request->description:null;            
            $query=$chat->save();
            if($query==true){ 
                $updatestatus=Ticketlist::where('id',$request->ticket_id)->update(['status'=>'1']);
                return response()->json(["status" => 200,"success"=>true, "message" =>"Message Sent"]);   
            }else{
                 return response()->json(["status" => 400,"success"=>false, "message" =>"Failed try again"]);  
            }         
             
        }else{           
            $chat =new Chat();
            $chat->ticket_id=$request->ticket_id;
            $chat->sender_id=$user->id;           
            $chat->reciver_id='1';           
            $chat->description=$request->description?$request->description:null;            
            $query=$chat->save();
            if($query==true){
                return response()->json(["status" => 200,"success"=>true, "message" =>"Message successfully sent"]);
            }else{
               return response()->json(["status" => 400,"success"=>false, "message" =>"Failed try again"]); 
            }
         
        }
    }


    public function clickAction(Request $request)
    {   // user_type 3-admin
        $user = Auth::user();
        
            $validator = Validator::make($request->all(),[                                      
                'ticket_id' => 'required',                                   
            ]);
            if ($validator->fails()){
                return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
            }

            $chat = Ticketlist::where('id',$request->ticket_id)->first();
            if($chat){
                $chat->status = 1;
                $result = $chat->save();
                if($result==true){
                    return response()->json(["status" => 200,"success"=>true, "message" =>"Ticket has been closed "]);
                }else{
                    return response()->json(["status" => 400,"success"=>false, "message" =>"Something went wrong"]);
                }
            }else{
                    return response()->json(["status" => 400,"success"=>false, "message" =>"No data found"]);
            }
    }

    public function getMsg_old($ticket_id='')
    {
       
        $chatmsg = Chat::select('id','description','ticket_id','created_at as date')->where('ticket_id',$ticket_id)->orderBy('id','desc')->first();
        // return $chatmsg;
        if(!empty($chatmsg)){   
               $date1=Carbon::parse($chatmsg->date)->format('F d,Y');                
               $array['id']=$chatmsg->id;
               $array['ticket_id']=$chatmsg->ticket_id;
               $array['description']=$chatmsg->description;
               $array['status']="1";
               $array['created_at']=$date1;
            return response()->json(["status" => 200,"success"=>true, "message" =>"Ticket closed now",'data'=>$array]);
        }else{
            return response()->json(["status" => 400,"success"=>false, "message" =>"data not found"]);

        }
    
    }

    public function getMsg($ticket_id='')
    {


       $chatmsg=TicketList::where('id',$ticket_id)->first();

        $tickect = Chat::select('chats.id','discription','chats.ticket_id','status','description')->leftjoin('ticketlists','ticketlists.ticket_id','chats.id')->where('chats.ticket_id',$chatmsg->id)->first();
       
        if(!empty($chatmsg)){   
               $date1=Carbon::parse($chatmsg->date)->format('F d,Y');                
               $array['id']=$chatmsg->id;
               $array['ticket_id']=$chatmsg->ticket_id;
               $array['description']=$tickect->description == null ? '' : $tickect->description ;
               $array['status']="1";
               $array['created_at']=$date1;
            return response()->json(["status" => 200,"success"=>true, "message" =>"Ticket closed now",'data'=>$array]);
        }else{
            return response()->json(["status" => 400,"success"=>false, "message" =>"Your query is under process"]);

        }
    
    }

    public function getMsgforadmin(Request $request)
    {       
       
        $validator = Validator::make($request->all(),[                                    
            'ticket_id' => 'required',                    
            ]); 
        
        if($validator->fails()){
            return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
        }
        $user=Auth::user();      
            
        $chatmsg = Chat::where('ticket_id',$request->ticket_id)->first();
        if(!empty($chatmsg)){  
            $tickects=Ticketlist::where('id',$request->ticket_id)->first();            
            if(!empty($tickects)){                    
                $arraydescription['question']  = $tickects->discription?$tickects->discription:'';
                $arraydescription['answer']  = $chatmsg->description?$chatmsg->description:'';
            }
                                   
            return response()->json(["status" => 200,"success"=>true, "message" =>"You've not any msg on this ticket",'data'=>$arraydescription]);
                
        
        }else{                              
            return response()->json(["status" => 400,"success"=>false, "message" =>"data not found"]);           
        }
        
    }


    public function ticketstatus_update(Request $request)
    {
        $validator=Validator::make($request->all(),[
            "id"=>"required",
            "status"=>"required",
        ]);
        if($validator->fails()){
            return response()->json(["status" => 400,"success"=>false, "message" => $validator->messages()->first()]);
        }

        $ticketstatus =  Ticketlist::where('id',$request->id)->update([
                'status'=>$request->status
        ]);
     return response()->json(['status'=>200,'success'=>true,'message'=>"Ticket has been closed successfully"]);   

    }


    public function userTicketlist($id=''){        
        $user = Auth::User();   

        if(!empty($user)){

            if($user->user_type==1||$user->user_type==2){         
            
                $list = Ticketlist::select('id','category_id','ticket_id','discription','status','created_at')->where('user_id',$user->id)->orderBy('id','desc')->get();
 
                // $list = Ticketlist::where('user_id',$user->id)->where('order_id','!=',null)->where('status','0')->orderBy('id','desc')->get();
                if(sizeof($list)){
                    foreach($list as $data){
                        // return $data;
                        $date=Carbon::parse($data->created_at)->format('F d,Y');        
                        // return $date;                
                        $data1['id']=$data->id;
                        $data1['ticket_id']=$data->ticket_id;
                        $data1['category_id']=$data->category_id;
                        $data1['discription']=$data->discription;
                        $data1['status']=$data->status;
                       
                        $data1['created_at']=$date;
                        
                        $getsubcat=ChatCategory::where('id',$data->category_id)->first();
                        if(!empty($getsubcat)){
                            $data1['category_name'] = $getsubcat->category_name;
                        }
                        $array[]= $data1;
                        unset($data->reciver_id);
                        // unset($data->date);
                    }   
                          
                          // return  $array;        
                    return response()->json(["status" => 200,"success"=>true, "message" =>"Ticket List",'data'=>$array]);
                }else{
                    return response()->json(["status" => 400,"success"=>false, "message" =>"No tickets found !!"]);
                }
            
        }else{    
           
        
        }

        }else{

            $list =Ticketlist::select('ticketlists.id','category_id','ticket_id','discription','ticketlists.status','ticketlists.created_at','user_type')->leftjoin('users','ticketlists.user_id','users.id')->orderBy('ticketlists.id','desc')->get();
            if(sizeof($list)){
                foreach($list as $row){
                    $array['id']= $row->id;
                    $array['ticket_id']= $row->ticket_id;
                    $array['subject']= $row->subject == null ? "" : $row->subject;
                    $array['discription']= $row->discription;
                    $array['status']= $row->status;
                    $array['user_type']= $row->user_type;
                    $date=Carbon::parse($row->created_at)->setTimezone('Asia/Kolkata')->format('Y-m-d g:i A');                    
                    $array['created_at']=$date;
                    $data []= $array;
                    unset($row->updated_at);
                    unset($row->date);
                   
                }  
                   // return $data;                
                return response()->json(["status" => 200,"success"=>true, "message" =>"Ticket List",'data'=>$data]);
            }else{
                return response()->json(["status" => 200,"success"=>true, "message" =>"No tickets found !!",'data'=>[]]);
            }

        }

      
    
    }

       public function userTicketlists($id=''){  
        
        $user=Auth::user();

        if(!empty($user))
        {
            $list =Ticketlist::select('ticketlists.id','category_id','ticket_id','discription','ticketlists.status','ticketlists.created_at','user_type')->where('ticketlists.user_id',$user->id)->leftjoin('users','ticketlists.user_id','users.id')->orderBy('ticketlists.id','desc')->get();
            if(sizeof($list)){
                foreach($list as $row){
                    $array['id']= $row->id;
                    $array['ticket_id']= $row->ticket_id;
                    $array['subject']= $row->subject == null ? "" : $row->subject;
                    $array['discription']= $row->discription;
                    $array['status']= $row->status;
                    $array['category_id']= 0;
                    $array['category_name']= '';
                    $array['user_type']= $row->user_type;
                    $date=Carbon::parse($row->created_at)->setTimezone('Asia/Kolkata')->format('Y-m-d g:i A');                    
                    $array['created_at']=$date;
                    $data []= $array;
                    unset($row->updated_at);
                    unset($row->date);
                   
                }  
                   // return $data;                
                return response()->json(["status" => 200,"success"=>true, "message" =>"Ticket List",'data'=>$data]);
            }else{
                return response()->json(["status" => 200,"success"=>true, "message" =>"No tickets found !!",'data'=>[]]);
            }
        }else{
            $list =Ticketlist::select('ticketlists.id','category_id','ticket_id','discription','ticketlists.status','ticketlists.created_at','user_type')->where('ticketlists.user_id','!=',1)->leftjoin('users','ticketlists.user_id','users.id')->orderBy('ticketlists.id','desc')->get();
            if(sizeof($list)){
                foreach($list as $row){
                    $array['id']= $row->id;
                    $array['ticket_id']= $row->ticket_id;
                    $array['subject']= $row->subject == null ? "" : $row->subject;
                    $array['discription']= $row->discription;
                    $array['status']= $row->status;
                    $array['category_id']= 0;
                    $array['category_name']= '';
                    $array['user_type']= $row->user_type;
                    $date=Carbon::parse($row->created_at)->setTimezone('Asia/Kolkata')->format('Y-m-d g:i A');                    
                    $array['created_at']=$date;
                    $data []= $array;
                    unset($row->updated_at);
                    unset($row->date);                   
                }  
                   // return $data;                
                return response()->json(["status" => 200,"success"=>true, "message" =>"Ticket List",'data'=>$data]);
            }else{
                return response()->json(["status" => 200,"success"=>true, "message" =>"No tickets found !!",'data'=>[]]);
            }

        }
           
  
      
    
    }

    public function addChatCategory(Request $request){
        $validator=Validator::make($request->all(),[
            "category_name"=>"required"
        ]);
        if($validator->fails()){
            return response()->json(["status" => 400,"success"=>false, "message" => $validator->messages()->first()]);
        }
        $user=Auth::user();
        if($user->user_type==3){
            if($request->id!=''){
               $add=ChatCategory::find($request->id);
               $msg="Chat category successfully updated";
            }else{
               $add=new ChatCategory(); 
               $msg="Chat category successfully added";
            }
            $add->category_name=$request->category_name;
            $query=$add->save();
            if($query==true){
                 return response()->json(["status" => 200,"success"=>true, "message" =>$msg]);
            }else{
               return response()->json(["status" => 400,"success"=>false, "message" =>"You're not authorized"]);  
            }
            
        }else{
            return response()->json(["status" => 400,"success"=>false, "message" =>"You're not authorized"]);
        }
    }


    public function getChatCategory(){       
        $user=Auth::user();
        if(!empty($user)){            
               $getchat=ChatCategory::orderBydesc('id')->get();
               if(sizeof($getchat)){
                    $data=$getchat;
                    return response()->json(["status" => 200,"success"=>true, "message"=>"Chat categoty",'date'=>$data]);
               }else{                    
                    return response()->json(["status" => 400,"success"=>false, "message" =>"Data not found"]);  
               }
              
            
            
        }else{
            return response()->json(["status" => 400,"success"=>false, "message" =>"Please login first to access this"]);
        }
    }
}
