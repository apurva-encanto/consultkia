<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Validator;
use Session;
use Response;
use Auth;
use Mail;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Blog;
use App\Models\State;
use App\Models\Court;
use App\Models\Language;
use App\Models\PracticeArea;
use App\Models\Transaction;
use App\Models\LawyerDetail;
use App\Models\GuideLine;
use App\Models\WithdrawalRequest;
use App\Models\Document;
use App\Models\Order;
use App\Models\Review;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class AdminController extends ApiController
{
    public function __construct()
    {
        
        Auth::shouldUse('api');
    }

    public function dashboard(){
        $array['total_user']=User::where('user_type','1')->count('id');
        $array['total_user_on_app']=User::whereIn('user_type',['1','2'])->count('id');
        $array['total_lawyer']=User::where('user_type','2')->count('id');
        $array['total_order']=Order::whereIn('status',['1','3'])->count('id');       
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subMonths($i)->startOfMonth();
            $formattedDate = $date->format('M Y');
            $lastThreeMonths[] = [
                'year' => $date->format('y'),
                'month' => $date->format('m'),
                'name' => $formattedDate,
            ];
        }
       
        usort($lastThreeMonths, function ($a, $b) {
            return strcmp($a['month'], $b['month']);
        });

        $data1=[];
        foreach($lastThreeMonths as $row){
            $data['month']=$row['name'];
            $getusercount=User::where('user_type','1')->whereMonth('created_at',$row['month'])->whereYear('created_at','20'.$row['year'])->count('id');                    
            $getLawyercount=User::where('user_type','2')->whereMonth('created_at',$row['month'])->whereYear('created_at','20'.$row['year'])->count('id');

            $data['users']=$getusercount;
            $data['lawyer']=$getLawyercount;
            // $data['totaluser']=$getLawyercount;
            $data1[]=$data;

        }
        $array['chart']=$data1;
        return response()->json(['status'=>200,'success'=>true,'message'=>"Dashboard","data"=>$array]);       

    }

    public function clearOrder(){
        $order=Order::where('call_initiate','!=',null)->delete();
        if($order){
            return response()->json(['status'=>200,'success'=>true,'message'=>"all order has been deleted"]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"failed try again"]);
        }

    }
    // add/edit subadmin
    public function addEditState(Request $request){                
        $validator = Validator::make($request->all(), [
            'state_name' => 'required',
        ]);
        if ($validator->fails()){
            return response()->json(["status" => 400,"success" => false, "message" => $validator->messages()->first()]);
        }
        $user=AUth::user();
        if($user->user_type==3){
            if($request->id!=''){
                $state=State::find($request->id); 
                $msg="State Successfully Updated";
            }else{
                $state=new State ();
                $msg="State Successfully Added";
            }
            $state->state_name=$request->state_name;
            $query=$state->save();
            if($query=true){
                return response()->json(['status'=>200,'success'=>true,'message'=>$msg]);
            }else{
                return response()->json(['status'=>400,'success'=>false,'message'=>"Something went wrong try again"]);
            }
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"You're not authorized"]);
        }

    }
    public function stateList(){
        $state=State::select('id','state_name')->get();
        if(sizeof($state)){
            return response()->json(['status'=>200,'success'=>true,'message'=>"State List","data"=>$state]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"State not found"]);
        }

    }

    public function stateDetail($id=''){
        $state=State::select('id','state_name')->where('id',$id)->first();
        if(!empty($state)){
            return response()->json(['status'=>200,'success'=>true,'message'=>"State Details","data"=>$state]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"State not found"]);
        }

    }


    public function addEditCourt(Request $request){                
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()){
            return response()->json(["status" => 400,"success" => false, "message" => $validator->messages()->first()]);
        }
        $user=Auth::user();
        if($user->user_type==3){
            if($request->id!=''){
                $court=Court::find($request->id); 
                $msg="Court Successfully Updated";
            }else{
                $court=new Court ();
                $msg="Court Successfully Added";
            }
            $court->name=$request->name;
            $query=$court->save();
            if($query=true){
                return response()->json(['status'=>200,'success'=>true,'message'=>$msg]);
            }else{
                return response()->json(['status'=>400,'success'=>false,'message'=>"Something went wrong try again"]);
            }
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"You're not authorized"]);
        }

    }

    public function courtList(){
        $court=Court::select('id','name')->get();
        if(sizeof($court)){
            return response()->json(['status'=>200,'success'=>true,'message'=>"Court List","data"=>$court]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"Court not found"]);
        }

    }


    public function courtDetail($id=''){
        $court=Court::select('id','name')->where('id',$id)->first();
        if(!empty($court)){
            return response()->json(['status'=>200,'success'=>true,'message'=>"Court Details","data"=>$court]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"Court not found"]);
        }

    }

    public function practiceDetail($id=''){
        $practice=PracticeArea::select('id','name','image')->where('id',$id)->first();
        if(!empty($practice)){
            $practice['image']=$practice->image?imgUrl.'practice/'.$practice->image:null;
            return response()->json(['status'=>200,'success'=>true,'message'=>"Practice Area Details","data"=>$practice]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"Practice Area  not found"]);
        }

    }

    public function addEditLanguage(Request $request){ 
        $validator = Validator::make($request->all(), [
            'language_name' => 'required',
        ]);
        if ($validator->fails()){
            return response()->json(["status" => 400,"success" => false, "message" => $validator->messages()->first()]);
        }
        $user=Auth::user();               
        if($user->user_type==3){
            if($request->id!=''){
                $Language=Language::find($request->id); 
                $msg="Language Successfully Updated";
            }else{
                $Language=new Language ();
                $msg="Language Successfully Added";
            }
            $Language->language_name=$request->language_name;
            $query=$Language->save();
            if($query=true){
                return response()->json(['status'=>200,'success'=>true,'message'=>$msg]);
            }else{
                return response()->json(['status'=>400,'success'=>false,'message'=>"Something went wrong try again"]);
            }
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"You're not authorized !"]);
        }
        

    }

    public function languageList(){
        $Language=Language::select('id','language_name')->get();
        if(sizeof($Language)){
            return response()->json(['status'=>200,'success'=>true,'message'=>"Language List","data"=>$Language]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"Language not found"]);
        }

    }
    public function languageDetail($id=''){
        $Language=Language::select('id','language_name')->where('id',$id)->first();
        if(!empty($Language)){
            return response()->json(['status'=>200,'success'=>true,'message'=>"Language Details","data"=>$Language]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"Language not found"]);
        }

    }

    public function addEditPractice(Request $request){                
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()){
            return response()->json(["status" => 400,"success" => false, "message" => $validator->messages()->first()]);
        }
        $user=Auth::user();               
        if($user->user_type==3){
            $check = PracticeArea::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->first();

            if (!$check) {
                if ($request->id != '') {
                    $practice = PracticeArea::find($request->id);
                    $msg = "Practice Area Successfully Updated";
                } else {
                    $practice = new PracticeArea();
                    $getposition = PracticeArea::select('position')->orderByDesc('id')->first();
                    if (!empty($getposition)) {
                        $practice->position = $getposition->position + 1;
                    }else{
                        $practice->position=1;
                    }

                    $msg = "Practice Area Successfully Added";
                }

                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $filename = time() . '.' . $file->extension();
                    $file->move(storage_path('app/public/practice'), $filename);

                    $practice->image = $filename;
                }

                $practice->name = $request->name;
                $saved = $practice->save();

                if ($saved) {
                    return response()->json(['status' => 200, 'success' => true, 'message' => $msg]);
                } else {
                    return response()->json(['status' => 400, 'success' => false, 'message' => "Something went wrong, try again"]);
                }
            } else {
                if ($request->id != '') {
                    return response()->json(['status' => 400, 'success' => false, 'message' => $request->name . ' ' . "is already exist, can't be updated"]);
                } else {
                    return response()->json(['status' => 400, 'success' => false, 'message' => $request->name . ' ' . "is already exist, can't be added"]);
                }
            }

        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"You're not authorized !"]);
        }

    }

    public function practiceList(){
        $practice=PracticeArea::select('id','name','image')->get();
        if(sizeof($practice)){
            foreach($practice as $row){
                $row->image= $row->image?imgUrl.'practice/'.$row->image:default_img;
                $data[]=$row;  
            }
            return response()->json(['status'=>200,'success'=>true,'message'=>"Practice Area List","data"=>$data]);
        }else{
            return response()->json(['status'=>400,'success'=>false,'message'=>"Data not found"]);
        }

    }   
    public function practiceDelete($id){ 
        $user=Auth::user();
        if($user->user_type==3){        
            $check=PracticeArea::where('id',$id)->first();
            if(!empty($check)){
                $query=PracticeArea::where('id',$id)->delete();                 
                return response()->json(['status'=>200,'success'=>true,"message"=>"Practice-Area successfully deleted"]);
            }else{
                return response()->json(['status'=>400,'success'=>false,"message"=>"Invalid data"]);
            }
        }else{
            return response()->json(['status'=>400,'success'=>false,"message"=>"You're not authorized"]);
        } 
    
    }
    public function stateDelete($id){ 
        $user=Auth::user();
        if($user->user_type==3){        
            $check=State::where('id',$id)->first();
            if(!empty($check)){
                $query=State::where('id',$id)->delete();                 
                return response()->json(['status'=>200,'success'=>true,"message"=>"State successfully deleted"]);
            }else{
                return response()->json(['status'=>400,'success'=>false,"message"=>"Invalid data"]);
            }
        }else{
            return response()->json(['status'=>400,'success'=>false,"message"=>"You're not authorized"]);
        } 
    
    }
    public function courtDelete($id){            
        
        $user=Auth::user();
        if($user->user_type==3){        
            $check=Court::where('id',$id)->first();
            if(!empty($check)){
                $query=Court::where('id',$id)->delete();                 
                return response()->json(['status'=>200,'success'=>true,"message"=>"Court successfully deleted"]);
            }else{
                return response()->json(['status'=>400,'success'=>false,"message"=>"Invalid data"]);
            }
        }else{
            return response()->json(['status'=>400,'success'=>false,"message"=>"You're not authorized"]);
        } 
    
    }    
    public function languageDelete($id){            
        
        $user=Auth::user();
        if($user->user_type==3){        
            $check=Language::where('id',$id)->first();
            if(!empty($check)){
                $query=Language::where('id',$id)->delete();                 
                return response()->json(['status'=>200,'success'=>true,"message"=>"Language successfully deleted"]);
            }else{
                return response()->json(['status'=>400,'success'=>false,"message"=>"Invalid data"]);
            }
        }else{
            return response()->json(['status'=>400,'success'=>false,"message"=>"You're not authorized"]);
        } 
    
    }
    
    // guideline section 

    // add/edit terms privacy refund and faq's 
        public function addQuidelines(Request $request){        
            // type 1-terms & condition, 2-Privacy Policy,3-Faq,else Refund policy
            if($request->type==1){
                $validator = Validator::make($request->all(), [
                    'description' => 'required',
                    'type'=>'required'
                ]);
            }elseif($request->type==2){
                $validator = Validator::make($request->all(), [
                    'description' => 'required',
                    'type'=>'required'
                ]);
            }elseif($request->type==3){
                $validator = Validator::make($request->all(), [
                    'title' => 'required',
                    'description' => 'required',
                    'type'=>'required'
                ]);
            }else{
                $validator = Validator::make($request->all(), [
                    'description' => 'required',
                    'type'=>'required'
                ]);
            }

            if($validator->fails()){
                return response()->json(["status" => 400,"success" => false, "message" => $validator->messages()->first()]);
            }

            $user=Auth::user();
            if($user->user_type=='3'){   
                if($request->id!=''){                
                    $quide=GuideLine::find($request->id);
                    if($quide->type=="3"){
                        $quide->title=$request->title;
                        $quide->faq_for=$request->faq_for;
                        $msg="Faq Successfully Updated";
                    }elseif($request->type=="2"){
                        $msg="Privacy Policy Successfully Updated";
                    }elseif($request->type=="1"){
                        $msg="Terms & Condition Successfully Updated";
                    }else{
                        $msg="Refund Policy Successfully Updated";
                    }
                    $quide->description=$request->description;                     
                    $save=$quide->save();
                    if($save==true){
                        return response()->json(['status'=>200,'success'=>true,'message'=>$msg]);
                    }else{
                        return response()->json(['status'=>400,'success'=>false,'message'=>"failed try again"]);
                    }
                }else{                
                    $quide=new GuideLine();
                    if($request->type==3){
                        $quide->title=$request->title;
                        $quide->faq_for=$request->faq_for;
                        $msg="Faq Successfully Added";
                    }elseif($request->type==2){
                        $msg="Privacy Policy Successfully Added";
                    }elseif($request->type==1){
                        $msg="Terms & Condition Successfully Added";
                    }else{
                        $msg="Refund Policy Successfully Added";
                    }
                    $quide->type=$request->type;
                    $quide->description=$request->description;
                    
                    $save=$quide->save();
                    if($save==true){
                        return response()->json(['status'=>200,'success'=>true,'message'=>$msg]);
                    }else{
                        return response()->json(['status'=>400,'success'=>false,'message'=>"failed try again"]);
                    }
                }
            }else{
                return response()->json(['status'=>400,'success'=>false,'message'=>"You're not authorized"]);
            }
        
        }

    //  faq delete here
        public function faqdelete(Request $request){

            $validator = Validator::make($request->all(), [
                'id' => 'required',
                
            ]);
            if($validator->fails()){
                return response()->json(["status" => 400,"success" => false, "message" => $validator->messages()->first()]);
            }
            $user=Auth::user();
            if($user->user_type=="3"){
                $faq=GuideLine::where('id',$request->id)->delete();
                if($faq==true){
                    return response()->json(['status'=>200,'success'=>true,'message'=>"Faq successfully deleted"]);
                }else{
                    return response()->json(['status'=>400,'success'=>false,'message'=>"Faq not deleted try again"]);
                }
            }else{
                return response()->json(['status'=>400,'success'=>false,'message'=>"You're not authorized"]);
            }
        
        }

        public function faqDetail($id=''){

            
            $user=Auth::user();
            if($user->user_type=="3"){
                $faq=GuideLine::where('id',$id)->first();
                if(!empty($faq)){
                    return response()->json(['status'=>200,'success'=>true,'message'=>"Faq Detail",'data'=>$faq]);
                }else{
                    return response()->json(['status'=>400,'success'=>false,'message'=>"Faq not found"]);
                }
            }else{
                return response()->json(['status'=>400,'success'=>false,'message'=>"You're not authorized"]);
            }
        
        }

    //  all guidence list

        public function guidelines($usertype='',$type=''){            

            if($type!=''){
                if($usertype==3){
                    if($type==1){
                        $guideline=GuideLine::select('id','description','created_at','updated_at')->where('type','1')->first();
                        $msg="Terms & Condition";
                    }elseif($type==2){
                        $guideline=GuideLine::select('id','description','created_at','updated_at')->where('type','2')->first();
                        $msg="Privacy Policy";
                    }elseif($type==3){
                        $guideline=[];                        
                        $faq=GuideLine::select('id','title','description','faq_for','created_at','updated_at')->where('type','3')->orderBy('id','asc')->get();
                        if(sizeof($faq)){
                            foreach($faq as $row){
                            $guideline[]=$row;
                            }
                        } 

                        $msg="Faq";                 
                    }else{
                        $guideline=GuideLine::select('id','description','created_at','updated_at')->where('type','4')->first();
                        $msg="Refund policy";
                    }
                }else{

                    if($type==1){
                        $guideline=GuideLine::select('id','description','created_at','updated_at')->where('type','1')->first();
                        $msg="Terms & Condition";
                    }elseif($type==2){
                        $guideline=GuideLine::select('id','description','created_at','updated_at')->where('type','2')->first();
                        $msg="Privacy Policy";
                    }elseif($type==3){
                        $guideline=[];
                        if($usertype=='1'){
                            $faq=GuideLine::select('id','title','description','faq_for','created_at','updated_at')->whereIn('faq_for',['1','0'])->where('type','3')->orderBy('id','asc')->get();
                        }
                        if($usertype=='2'){
                            $faq=GuideLine::select('id','title','description','faq_for','created_at','updated_at')->whereIn('faq_for',['2','0'])->where('type','3')->orderBy('id','asc')->get();
                        } 
                        if(sizeof($faq)){
                            foreach($faq as $row){
                            $guideline[]=$row;
                            }
                        } 

                        $msg="Faq";                 
                    }else{
                        $guideline=GuideLine::select('id','description','created_at','updated_at')->where('type','4')->first();
                        $msg="Refund policy";
                    }
                }
                if($guideline){
                    return response()->json(['status'=>200,'success'=>true,'message'=>$msg,'data'=>$guideline]);
                }else{
                    return response()->json(['status'=>400,'success'=>false,'message'=>$msg .' '.'Data not found']);
                }

            }else{
                
                return response()->json(['status'=>400,'success'=>false,'message'=>"failed try again"]);
            }  
        
        }
        
        public function alluserslist(Request $request){
            $validator = Validator::make($request->all(),[
               "user_type"  => 'required',               
            ]);
            if($validator->fails()){
                return response()->json(["status" => 400,"success" => false, "message" => $validator->messages()->first()]);
            }

            $user=Auth::user();
            if($user->user_type==3){        
                if($request->user_type!='0'){
                    $getuser=User::where('is_verified','1')->where('user_type',$request->user_type)->orderBydesc('id')->get();
                }else{
                    $getuser=User::where('is_verified','1')->whereIn('user_type',['1','2'])->orderBydesc('id')->get();
                }       
                 if(sizeof($getuser)){
                    foreach($getuser as $row){
                        $userInfo['id'] =$row->id;
                        $userInfo['phone'] =$row->phone;                             
                        $userInfo['is_verified'] =(int)$row->is_verified; 
                        $userInfo['is_adminVerified'] =(int)$row->is_adminVerified; 
                        $userInfo['profile_img'] =$row->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','',$row->profile_img)):user_img;
                        $userInfo['first_name'] =$row->name;
                        $userInfo['last_name'] =$row->name;                
                        $userInfo['name'] =$row->name;                
                        $userInfo['user_name'] =$row->user_name;  
                        $data[]=$userInfo;
                    }     
                    return response()->json(["status" => 200,"success" => true, "message" =>"All Users list",'data'=>$data]);
                 }else{
                    return response()->json(["status" => 400,"success" => false, "message" =>"Data not found"]);
                 }
                    
            }else{
                return response()->json(["status" => 400,"success" => false, "message" =>"You're not authorized"]);
            }
        }

        public function withdrawalRequestlist(){            
            $user=Auth::user();
            if($user->user_type==3){
                $getData=WithdrawalRequest::with('userDetails')->where('status','0')->orderBydesc('id')->get();
                
                if(sizeof($getData)){
                    foreach ($getData as  $row) {

                        $row->user_name=$row->userDetails->user_name;
                        $row->profile_img=$row->userDetails->profile_img?imgUrl.'profile/'.$row->userDetails->profile_img:default_img;
                        $row->dateTime=Carbon::parse($row->created_at)->format('Y-m-d g:i a');
                        $data[]=$row;
                        unset($row->userDetails);
                       
                    }
                    return response()->json(["status" => 200,"success" => true, "message" =>"All withdrawal request",'data'=>$data]);
                }else{
                    return response()->json(["status" => 400,"success" => false, "message" =>"No data found"]);
                }
            }else{
                return response()->json(["status" => 400,"success" => false, "message" =>"You're not authorized"]);
            }
        }

        public function UpdateKycStatus(Request $request){
            $user=Auth::user();
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'status' => 'required',
                
            ]);
            if($validator->fails()){
                return response()->json(["status" => 400,"success" => false, "message" => $validator->messages()->first()]);
            }
                if($user->user_type==3){
                    $check=LawyerDetail::where('user_id',$request->id)->first();
                    if(!empty($check)){
                        if($request->status==1){
                            $msg="Lawyer kyc successfully approved";
                        }elseif($request->status==2){
                             $query1=LawyerDetail::where('user_id',$request->id)->update([
                                'isPremium'=>'0',
                                ]);
                            $msg="Lawyer kyc has been rejected";

                        }else{
                            $msg="Lawyer kyc has pending now";
                        }
                        $query=LawyerDetail::where('user_id',$request->id)->update([
                            'is_adminVerified'=>$request->status,
                        ]);
                        if($query==true){
                            return response()->json(["status" => 200,"success" => true, "message" =>$msg]);
                        }else{
                            return response()->json(["status" => 400,"success" => false, "message" =>"failed Something went wrong try again"]);
                        }
                    }else{
                        return response()->json(["status" => 400,"success" => false, "message" =>"User not found"]);
                    }
                }else{
                     return response()->json(["status" => 400,"success" => false, "message" =>"you're not authorized"]);
                }
        }

        public function userDetails($id){
            $user=Auth::user();
            if($user->user_type==3){
                $getuser=User::where('id',$id)->first();
                if(!empty($getuser)){ 

                    $userInfo['id'] = $id;
                    $userInfo['user_name'] = $getuser->user_name;
                    $userInfo['first_name'] = $getuser->name;
                    $userInfo['last_name'] = $getuser->name;
                    $userInfo['user_type'] = (int)$getuser->user_type;                        
                    $userInfo['phone'] = $getuser->phone;               
                    $userInfo['gender'] = $getuser->gender;              
                    $userInfo['profile_img'] = $getuser->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','', $getuser->profile_img)):user_img;
                    $userInfo['name'] = $getuser->name;
                    // $userInfo['user_name'] = $getuser->user_name;
                    $userInfo['city'] = $getuser->city;
                    $userInfo['refrence_code'] = $getuser->refrence_code;
                    $userInfo['referrel_code'] = $getuser->referrel_code;               
                    
                    return response()->json(['status' => 200,"success" => true,'message'=> "User Details",'data'=>$userInfo]);
                }else{
                    return response()->json(['status' => 400,"success" => false,'message'=> "User not exist"]);
                }
            }else{
                return response()->json(['status' => 400,"success" => false,'message'=>"You're not authorized"]);
            }
        }

        public function lawyerDetailforAdmin($id){
            $user=User::where('id',$id)->first();
            if(!empty($user)){ 
                $userInfo['id'] =$user->id;
                $userInfo['phone'] = $user->phone;
                $userInfo['gender'] = $user->gender;                                  
                $userInfo['is_verified'] =(int)$user->is_verified; 
                $userInfo['profile_img'] = $user->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','', $user->profile_img)):user_img;
                $userInfo['first_name'] = $user->first_name;
                $userInfo['last_name'] = $user->last_name;                
                $userInfo['user_name'] = $user->user_name;                
                   
                $getLawyer=lawyerDetail::where('user_id',$id)->first();
                if(!empty($getLawyer)){

                    $getarea=PracticeArea::select('name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                    $practice=[];
                    if(count($getarea)){
                       
                        foreach ($getarea as $value) {
                            array_push($practice,$value->name);
                        }
                    }
                    $getlanguage=Language::select('language_name')->whereIn('id',json_decode($getLawyer->language))->get();
                    $language=[];
                    if(count($getlanguage)){
                       
                        foreach ($getlanguage as $value) {
                            array_push($language,$value->language_name);
                        }
                    }
                    $getstate=State::select('state_name')->whereIn('id',json_decode($getLawyer->practice_state))->get();
                    $state=[];
                    if(count($getstate)){                        
                        foreach ($getstate as $value) {
                            array_push($state,$value->state_name);
                        }
                    }
                    $license=Document::select('file','license_no')->where('user_detail_id',$getLawyer->id)->where('doc_type','1')->first();
                    if(!empty($license)){
                        $userInfo['license']=$license->file?imgUrl.'user/'.$license->file:""; 
                        $userInfo['license_no']=$license->license_no; 
                    }else{
                        $userInfo['license']="";
                        $userInfo['license_no']='';
                    }
                    $pan=Document::select('file','pan_no')->where('user_detail_id',$getLawyer->id)->where('doc_type','2')->first();
                    if(!empty($pan)){
                        $userInfo['pan']=$pan->file?imgUrl.'user/'.$pan->file:"";
                        $userInfo['pan_no']=$pan->pan_no;
                    }else{
                        $userInfo['pan']="";
                        $userInfo['pan_no']='';
                    }   
                    
                    $rating=Review::where('lawyer_id',$id)->avg('rating');
                    $getreview=Review::where('lawyer_id',$id)->count('id');
                    $language=implode(',',$language);
                    $userInfo['education_details']=$getLawyer->education_details;
                    $userInfo['experience']=$getLawyer->experience;                    
                    $userInfo['description']=$getLawyer->description;
                    $userInfo['ac_no']=$getLawyer->ac_no;
                    $userInfo['bank_name']=$getLawyer->bank_name;
                    $userInfo['ifsc']=$getLawyer->ifsc;
                    $userInfo['account_name']=$getLawyer->account_name;
                    $userInfo['chat_charge']=$getLawyer->chat_charge;
                    $userInfo['is_chat']=$getLawyer->is_chat;
                    $userInfo['call_charge']=$getLawyer->call_charge;
                    $userInfo['is_call']=$getLawyer->is_call;
                    $userInfo['language']=$language;
                    $userInfo['practice_area']=$practice;
                    $userInfo['practice_state']=$state;
                    $userInfo['rating']=round($rating,2);
                    $userInfo['total_review']=$getreview;
                    // $res=$this->checkavailability($id);
                    // $userInfo['availability']=$res;                    
                    
                }
                // return $userInfo;
                return response()->json(['status' => 200,"success" => true,'message'=> "User Details",'data'=>$userInfo]);
            }else{
                return response()->json(['status' => 400,"success" => false,'message'=> "User not exist"]);
            }
        }


        public function lawyerListforAdmin($type=''){
            
                      
            if($type==1){
                 $getuser=User::select('users.id','users.first_name','users.last_name','users.user_name','users.phone','users.profile_img','lawyer_details.is_adminVerified','lawyer_details.isPremium')
                 ->leftjoin('lawyer_details','lawyer_details.user_id','users.id')
                 ->where('lawyer_details.is_adminVerified','1')
                  ->orderBydesc('users.id')
                 ->get();
                 $msg="All approved lawyer list";

            }elseif($type==2){
                $getuser=User::select('users.id','users.first_name','users.last_name','users.user_name','users.phone','users.profile_img','lawyer_details.is_adminVerified','lawyer_details.isPremium')
                 ->leftjoin('lawyer_details','lawyer_details.user_id','users.id')
                 ->where('lawyer_details.is_adminVerified','2')
                  ->orderBydesc('users.id')
                 ->get();
                 $msg="All rejected lawyer list";
            }elseif($type==0){
                $getuser=User::select('users.id','users.first_name','users.last_name','users.user_name','users.phone','users.profile_img','lawyer_details.is_adminVerified','lawyer_details.isPremium')
                ->leftjoin('lawyer_details','lawyer_details.user_id','users.id')
                 ->where('lawyer_details.is_adminVerified','0')
                  ->orderBydesc('users.id')
                 ->get();
                 $msg="All pending lawyer list";
            }elseif($type==4){
                $getuser=User::select('users.id','users.first_name','users.last_name','users.user_name','users.phone','users.profile_img','lawyer_details.is_adminVerified','lawyer_details.isPremium')
                ->leftjoin('lawyer_details','lawyer_details.user_id','users.id')
                 ->where('lawyer_details.is_adminVerified','1')
                 ->where('lawyer_details.isPremium','1')
                  ->orderBydesc('users.id')
                 ->get();
                 $msg="All premium lawyer list";
            }else{
                $getuser=User::select('users.id','users.first_name','users.last_name','users.user_name','users.phone','users.profile_img','lawyer_details.is_adminVerified','lawyer_details.isPremium')
                ->leftjoin('lawyer_details','lawyer_details.user_id','users.id')
                ->where('users.user_type','2')
                ->orderBydesc('users.id')
                ->get();
                $msg="All lawyer list";
            }
            // return $getuser;
            if(sizeof($getuser)){ 
                foreach ($getuser as $row) {

               $rating= Review::where('lawyer_id',$row->id)->avg('rating');
               if($rating ==null)
               {
                $rating=0;
               }
                 
                $userInfo['id'] =$row->id;
                $userInfo['rating'] =$rating;
                $userInfo['phone'] =$row->phone;                             
                $userInfo['is_verified'] =(int)$row->is_verified; 
                $userInfo['isPremium'] =(int)$row->isPremium; 
                $userInfo['is_adminVerified'] =(int)$row->is_adminVerified; 
                $userInfo['profile_img'] =$row->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','',$row->profile_img)):user_img;
                $userInfo['first_name'] =$row->first_name;
                $userInfo['last_name'] =$row->last_name;                
                $userInfo['user_name'] =$row->user_name;                
                   
                $data[]=$userInfo;               
                    
                }  
                return response()->json(['status' => 200,"success" => true,'message'=>$msg,'data'=>$data]);

            }else{
                return response()->json(['status' => 400,"success" => false,'message'=> "Data not found"]);
            }
        }

        public function addEditPremium($id){
            $user=Auth::user();
            if($user->user_type==3){
                $check=LawyerDetail::where('user_id',$id)->first();
                if(!empty($check)){
                    if($check->isPremium=='1'){                       
                        $msg="Lawyer removed from premium"; 
                        $query=LawyerDetail::where('id',$check->id)->update([
                            "isPremium"=>'0'
                        ]);                      
                    }else{                                                
                        $msg="Lawyer added as premium"; 
                        $query=LawyerDetail::where('id',$check->id)->update([
                            "isPremium"=>'1'
                        ]);                       
                    }
                    
                    if($query==true){
                        return response()->json(['status' => 200,"success" => true,'message'=> $msg]);
                    }else{
                        return response()->json(['status' => 400,"success" => false,'message'=> "Failed try again"]);
                    }
                }else{
                    return response()->json(['status' => 400,"success" => false,'message'=> "Lawyer not found"]);
                }
            }else{
                return response()->json(['status' => 400,"success" => false,'message'=> "You're not authorized"]);
            }

        }

        public function changeActiveStatus($id)
        {

          $users=  User::where('user_name',$id)->first();
          if($users)
          {

            LawyerDetail::where('user_id',$users->id)->update(['is_available'=>1]);
            return response()->json(['status' => 200,"success" => true,'message'=> "Lawyer Available Successfully"]);
          }
            return $id;
        }


        
}
