<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use App\Models\PasswordReset;
use App\Notifications\emailVerificationRequest;
use App\Notifications\emailVerificationSucess;
use App\Notifications\ForgetPwdLinkNotification;
use App\Models\EmailVerification;
use Validator;
use Session;
use Auth;
use Http;
use Storage;
use Carbon\Carbon;
use App\Models\User;
use App\Models\State;
use App\Models\Payment;
use App\Models\LawyerDetail;
use App\Models\PracticeArea;
use App\Models\Document;
use App\Models\Language;
use App\Models\Review;
use App\Models\Appointment;
use App\Models\Favorite;
use App\Models\Availability;
use App\Models\Transaction;
use App\Models\Offer;
use App\Models\Order;
use App\Models\UserStatus;
use App\Models\AvailabilityTime;
use App\Models\Splash;
use App\Models\ProfileViewed;
use App\Models\AgoraTokenLawyer;
use App\helpers\Logics;
use App\Classes\AgoraDynamicKey\RtcTokenBuilder;
use App\Classes\AgoraDynamicKey\RtmTokenBuilder;
use App\Classes\AgoraDynamicKey\ChatTokenBuilder2;
use App\Classes\AgoraDynamicKey\AccessToken2;
use App\Classes\AgoraDynamicKey\AccessToken;
use App\Classes\AgoraDynamicKey\ServiceChat;



use App\Notifications\SignupNotification;

class UserController extends ApiController

{

    public function __construct()
    {
        Auth::shouldUse('api');

    }
   

   
    public function register(Request $request){ 
        if($request->user_type==1){  
            $validator = Validator::make($request->all(), [
                // 'user_name' => 'required',
                'name' => 'required',
                'phone' => 'required',                 
                'fcm_token' => '',
                'city' => 'required',
                'refrence_code' => '',
                'gender' => 'required',
                'profile_img' => '',
                'user_type'=>"required",
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'user_type'=>"required",                    
                'phone' => 'required',                 
                'gender' => 'required',
                'profile_img' => '',
                'fcm_token' => '',
                'first_name' => 'required',
                'last_name' => 'required',
                'practice_area' => 'required',
                'practice_state' => 'required',
                'description' => 'required',
                'experience' => 'required',
                'education_details' => 'required',
                'language' => 'required',
                "license"=>'',
                "pan"=>'',
                'ac_no'=>'required',
                "ifsc"=>"required",
                "account_name"=>"required",

            ]);
        }
        
        if($validator->fails()){
            return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
        }
        $randomString = Str::random(8);

        $length = 10;
        $charset = 'consult1230987654kiya';

        $randomString1 = generateRandomString(8);
       
        $checkusers=User::where('phone',$request->phone)->where('is_delete','0')->first();
        if(!empty($checkusers)){
            if($checkusers->is_verified=='1'){               
                return response()->json(['status'=>400,'success'=>false,'message'=>"Phone no. already has been taken !"]);
            }else{

                $user=User::find($checkusers->id); 
                if($request->user_type==1){
                    $user->name = $request->name;
                    $user->user_type = '1';
                    $user->city = $request->city;
                }else{
                    $user->first_name = $request->first_name;
                    $user->last_name = $request->last_name;
                    $user->user_type = '2';
                }
                
                $user->badge_no="";
                $user->phone = $request->phone;
                $user->user_name = $randomString1;
                $user->referrel_code = $randomString;
                $user->user_type = $request->user_type;
                $user->login_type = '1';
                $user->is_loggedin = '1';
                $user->is_verified = '1';
                
                $user->referrel_code = $randomString;
                $user->fcm_token =$request->fcm_token?$request->fcm_token:null;                    
                $user->refrence_code = $request->refrence_code?$request->refrence_code:null;
                $user->gender = $request->gender?$request->gender:1;
                if($request->hasFile('profile_img')) {
                    $file = $request->file('profile_img');
                    $filename = time() . '_' . $file->extension();
                    $file->move(storage_path('app/public/profile'), $filename); 
                    $filename = trim(preg_replace('/\s+/','', $filename));
                    $user->profile_img=$filename;
                }     
                $query = $user->save();

                if($query==true){
                    if($request->user_type==2){
                        
                        $checkDetail=LawyerDetail::where('user_id',$checkusers->id)->first();
                        if(!empty($checkDetail)){
                            $saveDetail=LawyerDetail::find($checkDetail->id);
                        }else{
                            $saveDetail=new LawyerDetail();                                
                        }
                        $saveDetail->user_id=$user->id;
                        $saveDetail->practice_area=$request->practice_area;
                        $saveDetail->description=$request->description;
                        $saveDetail->practice_state=$request->practice_state;
                        $saveDetail->education_details=$request->education_details;
                        $saveDetail->experience=$request->experience;
                        $saveDetail->language=$request->language;
                        $saveDetail->description=$request->description?$request->description:null;
                        
                        $saveDetail->ac_no=$request->ac_no;
                        $saveDetail->ifsc=$request->ifsc;
                        $saveDetail->bank_name=$request->bank_name?$request->bank_name:null;
                        $saveDetail->account_name=$request->account_name;
                        $detailQuery=$saveDetail->save();
                        if($detailQuery==true){                                
                            if($request->hasFile('license')) {                                    
                                    $check=Document::where('user_detail_id',$saveDetail->id)->where('doc_type','1')->first();
                                    if(!empty($check)){
                                        $path=storage_path("app/public/user/".$check->file);
                                        if (file_exists($path)) {
                                            unlink($path);
                                        }
                                        $delete=Document::where('user_detail_id',$saveDetail->id)->delete();
                                    }
                                    
                                    $filename = time() . '_' . $request->license->extension();
                                    $request->license->move(storage_path('app/public/user/'), $filename); 
                                    $filename = trim(preg_replace('/\s+/','', $filename));

                                    $document=new Document();                                    
                                    $document->user_detail_id=$saveDetail->id;
                                    $document->file=$filename;
                                    $document->license_no=$request->license_no;
                                    $document->doc_type="1";
                                    $documentquery=$document->save();
                                
                            }
                            if($request->hasFile('pan')) {                                    
                                $check=Document::where('user_detail_id',$saveDetail->id)->where('doc_type','2')->first();
                                if(!empty($check)){
                                    $path=storage_path("app/public/user/".$check->file);
                                        if (file_exists($path)) {
                                            unlink($path);
                                        }
                                    $delete=Document::where('user_detail_id',$saveDetail->id)->delete();
                                }
                                
                                $filename = time() . '_' . $request->pan->extension();
                                $request->pan->move(storage_path('app/public/user/'), $filename); 
                                $filename = trim(preg_replace('/\s+/','', $filename));

                                $document=new Document();                                    
                                $document->user_detail_id=$saveDetail->id;
                                $document->file=$filename;
                                $document->pan_no=$request->pan_no;
                                $document->doc_type="2";
                                $documentquery=$document->save();
                            }  
                            
                        }
                    }
                    $token = $user->createToken($request->phone)->accessToken;
                    $userInfo['id'] = $user->id;
                    $userInfo['user_type'] = (int)$user->user_type;                        
                    $userInfo['phone'] = $user->phone;
                    $userInfo['referrel_code'] = $user->referrel_code;
                    $userInfo['gender'] = $user->gender;                        
                    $userInfo['fcm_token'] = $user->fcm_token;
                    $userInfo['token'] = $token;                    
                    $userInfo['is_verified'] =(int)$user->is_verified; 
                    $userInfo['profile_img'] = $user->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','', $user->profile_img)):user_img;                       
                    if($request->user_type==2){
                        $getLawyer=LawyerDetail::where('user_id',$user->id)->first();
                        if(!empty($getLawyer)){                            
                            $getarea=PracticeArea::select('name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                            $practice=[];
                            if(count($getarea)){                                            
                                foreach ($getarea as $value) {
                                    array_push($practice,$value->name);
                                }
                            }
                            $getlanguage=Language::select('language_name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                            $language=[];
                            if(count($getlanguage)){
                                foreach ($getlanguage as $value) {
                                    array_push($language,$value->language_name);
                                }
                            }
                            $getstate=State::select('state_name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                            $state=[];
                            if(count($getstate)){                                            
                                foreach ($getstate as $value) {
                                    array_push($state,$value->state_name);
                                }
                            }
                            $language=implode(',',$language);
                        }
                        $userInfo['first_name'] = $user->first_name;
                        $userInfo['last_name'] = $user->last_name;
                        $userInfo['practice_area']=$practice;
                        $userInfo['practice_state']=$state;
                        $userInfo['description']=$request->description;
                        $userInfo['education_details']=$request->education_details;
                        $userInfo['experience']=$request->experience;
                        $userInfo['education_details']=$request->education_details;
                        $userInfo['language']=$language;
                        $userInfo['description']=$request->description?$request->description:null;
                        $userInfo['ac_no']=$request->ac_no;
                        $userInfo['bank_name']=$request->banK_name;
                        $userInfo['ifsc']=$request->ifsc;
                        $userInfo['account_name']=$request->account_name;
                        $license=Document::select('id','file','doc_type')->where('user_detail_id',$saveDetail->id)->where('doc_type','1')->first();
                        $pan=Document::select('id','file','doc_type')->where('user_detail_id',$saveDetail->id)->where('doc_type','2')->first();
                        
                        
                        $userInfo['license']=$license->file?imgUrl.'user/'.$license->file:null;                                   
                        $userInfo['pan']=$pan->file?imgUrl.'user/'.$pan->file:null;
                    }else{
                        $userInfo['name'] = $user->name;
                        $userInfo['user_name'] = $user->user_name;
                        $userInfo['city'] = $user->city;
                        $userInfo['refrence_code'] = $user->refrence_code;
                    } 
                    if($request->user_type==2){
                        $data['lawyer']=$userInfo;
                    }else{
                        $data['user']=$userInfo;
                    }
                    return response()->json(["status"=>200, "success" =>true, "message" => "User Registered Successfully ", "data" => $data]);        
                
                }else{
                    return response()->json(["status" => 400, "success" =>false, "message" => "Registration Failed try Again"]);
                }
            }            
        }else{
                $user=new User(); 
                if($request->user_type==1){
                    $user->name = $request->name;                    
                    $user->city = $request->city;
                    $user->user_type = '1';
                }else{
                    $user->first_name = $request->first_name;
                    $user->last_name = $request->last_name;
                    $user->user_type = '2';
                }
                
                
                $user->user_name = $randomString1;
                $user->referrel_code = $randomString;
                $user->phone = $request->phone;
                $user->user_type = $request->user_type;
                $user->is_verified = '1';
                $user->login_type = '1';
                $user->is_loggedin = '1';
                // $user->user_type = 1;
                $user->fcm_token =$request->fcm_token?$request->fcm_token:null;
                $randomNumericString = rand('11111111', '999999999');
                $user->badge_no=$randomNumericString;
                $user->refrence_code = $request->refrence_code?$request->refrence_code:null;
                $user->gender = $request->gender?$request->gender:1;
                if($request->hasFile('profile_img')) {
                    $file = $request->file('profile_img');
                    $filename = time() . '.' . $file->extension();
                    $file->move(storage_path('app/public/profile'), $filename); 
                    $user->profile_img=$filename;
                } 
               
                $query = $user->save();

                if($query==true){

                   
                    if($request->user_type==2){
                        $checkDetail=LawyerDetail::where('user_id',$user->id)->first();
                        if(!empty($checkDetail)){
                            $saveDetail=LawyerDetail::find($checkDetail->id);
                        }else{
                            $saveDetail=new LawyerDetail();                                
                        }
                        $saveDetail->user_id=$user->id;
                        $saveDetail->practice_area=$request->practice_area;
                        $saveDetail->practice_state=$request->practice_state;
                        $saveDetail->description=$request->description;
                        $saveDetail->education_details=$request->education_details;
                        $saveDetail->experience=$request->experience;
                        $saveDetail->language=$request->language;                            
                        $saveDetail->ac_no=$request->ac_no;
                        $saveDetail->ifsc=$request->ifsc;
                        $saveDetail->bank_name=$request->bank_name?$request->bank_name:null;
                        $saveDetail->account_name=$request->account_name;
                        $detailQuery=$saveDetail->save();
                        if($detailQuery==true){                                
                            
                             if($request->hasFile('license')) {                                    
                                    
                                    
                                    $filename = time() . '_' . $request->license->extension();
                                    $request->license->move(storage_path('app/public/user/'), $filename); 
                                    $filename = trim(preg_replace('/\s+/','', $filename));

                                    $document=new Document();                                    
                                    $document->user_detail_id=$saveDetail->id;
                                    $document->file=$filename;
                                    $document->license_no=$request->license_no;
                                    $document->doc_type="1";
                                    $documentquery=$document->save();
                                
                            }
                            if($request->hasFile('pan')) {                                    
                                
                                
                                $filename = time() . '_' . $request->pan->extension();
                                $request->pan->move(storage_path('app/public/user/'), $filename); 
                                $filename = trim(preg_replace('/\s+/','', $filename));

                                $document=new Document();                                    
                                $document->user_detail_id=$saveDetail->id;
                                $document->file=$filename;
                                $document->pan_no=$request->pan_no;
                                $document->doc_type="2";
                                $documentquery=$document->save();
                            }  
                            
                        }
                    }
                    $token = $user->createToken($request->phone)->accessToken;
                    $userInfo['id'] = $user->id;
                    $userInfo['user_type'] = (int)$user->user_type;                        
                    $userInfo['phone'] = $user->phone;
                    $userInfo['referrel_code'] = $user->referrel_code;
                    
                    $userInfo['gender'] = $user->gender;
                    $userInfo['fcm_token'] = $user->fcm_token;
                    $userInfo['token'] = $token;                    
                    $userInfo['is_verified'] =1; 
                    $userInfo['profile_img'] = $user->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','', $user->profile_img)):user_img;                       
                    if($request->user_type==2){
                        $getLawyer=LawyerDetail::where('user_id',$user->id)->first();
                        if(!empty($getLawyer)){                            
                            $getarea=PracticeArea::select('name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                            $practice=[];
                            if(count($getarea)){                                            
                                foreach ($getarea as $value) {
                                    array_push($practice,$value->name);
                                }
                            }
                            $getlanguage=Language::select('language_name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                            $language=[];
                            if(count($getlanguage)){
                                foreach ($getlanguage as $value) {
                                    array_push($language,$value->language_name);
                                }
                            }
                            $getstate=State::select('state_name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                            $state=[];
                            if(count($getstate)){                                            
                                foreach ($getstate as $value) {
                                    array_push($state,$value->state_name);
                                }
                            }
                            $language=implode(',',$language);
                        }
                        $userInfo['first_name'] = $user->first_name;
                        $userInfo['last_name'] = $user->last_name;
                        $userInfo['practice_area']=$practice;
                        $userInfo['practice_state']=$state;
                        $userInfo['description']=$request->description;
                        $userInfo['education_details']=$request->education_details;
                        $userInfo['experience']=$request->experience;
                        $userInfo['language']=$language;
                        $userInfo['ac_no']=$request->ac_no;
                        $userInfo['ifsc']=$request->ifsc;
                        $userInfo['bank_name']=$request->bank_name?$request->bank_name:null;
                        $userInfo['account_name']=$request->account_name;

                        
                    }else{
                        if($request->refrence_code!=''){
                            $updaterfrenceWallet=User::where('referrel_code',$request->refrence_code)->first();
                            if(!empty($updaterfrenceWallet)){
                                $updaterfrenceWallet->wallet_ballance=$updaterfrenceWallet->wallet_ballance+50;
                                $updatewalletquery=$updaterfrenceWallet->save();
                            
                                $getewallet=User::where('id',$user->id)->first();
                                if(!empty($getewallet)){
                                    $getewallet->wallet_ballance=50;
                                    $updatewallet=$getewallet->save();
                                    if($updatewallet==true){
                                        $order_no=generateOrderNumber();
                                        $addtransaction=New Transaction();
                                        $addtransaction->order_number=$order_no;
                                        $addtransaction->from=$user->id;
                                        $addtransaction->to=$updaterfrenceWallet->id;
                                        $addtransaction->sender_type="1";
                                        $addtransaction->reciever_type="1";   
                                        $addtransaction->status="1";
                                        $addtransaction->amount=50;
                                        $addtransaction->dateTime=Carbon::now()->format('Y-m-d h:i:s');
                                        $transactionQuery=$addtransaction->save();
                                    }
                                }
                            }
                        }
                        $userInfo['name'] = $user->name;
                        $userInfo['user_name'] = $user->user_name;
                        $userInfo['city'] = $user->city;
                        $userInfo['refrence_code'] = $user->refrence_code;
                        $userInfo['referrel_code'] = $user->referrel_code;
                    }
                                         
                    if($request->user_type==2){
                        $data['lawyer']=$userInfo;
                    }else{
                        $data['user']=$userInfo;
                    }
                    return response()->json(["status"=>200, "success" =>true, "message" => "User Registered Successfully ", "data" => $data]);        
                
                }else{
                    return response()->json(["status" => 400, "success" =>false, "message" => "Registration Failed try Again"]);
                }
        }
    
    }

    // user login

    public function login(Request $request){    
    // return $request->all(); 
        if($request->user_type==3)  {
            $validator = Validator::make($request->all(),[
            'phone' => 'required',                
            'fcm_token' => '',
            'user_type'=>"required",
            "password"=>'required',
            ]);
        }else{
            $validator = Validator::make($request->all(),[
            'phone' => 'required',                
            'fcm_token' => '',
            'user_type'=>"required"
             ]);
        }
        
        if($validator->fails()){
            return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
        }
       

        $is_phone = $request->input('phone');
        // return $is_phone;
        if(!empty($is_phone)){
            $user=User::where('phone',$is_phone)->first();
            if(!empty($user)){    
                if($user->is_verified==1){ 
                    if($request->user_type==3){
                    	// return "aDMIN";
                        if($user->user_type == $request->user_type){      
                        	// return "true";                  
                            $statusCheck = User::where([['phone', $is_phone], ['user_type', $request->user_type]])->first();
                            // return $user;
							if(!empty($statusCheck)){
								// Hash::check($request->post('password'),$user->password);
							    if($request->password==$user->original_password){
							        // Update user information
							        $update = User::where('id', $statusCheck->id)->update([
							            // 'fcm_token' => $request->fcm_token ? $request->fcm_token : null,
							            'is_loggedin' => '1'
							        ]);

							        // Generate access token
							        $token = $statusCheck->createToken($request->phone)->accessToken;

							        // Prepare user information
							        $userInfo['id'] = $statusCheck->id;
							        $userInfo['user_type'] = (int) $statusCheck->user_type;
							        $userInfo['phone'] = $statusCheck->phone;
							        $userInfo['referrel_code'] = $statusCheck->referrel_code;
							        $userInfo['gender'] = $statusCheck->gender;
							        $userInfo['fcm_token'] = $statusCheck->fcm_token;
							        $userInfo['token'] = $token;
							        $userInfo['is_verified'] = 1;
							        $userInfo['profile_img'] = $statusCheck->profile_img ? imgUrl . 'profile/' . trim(preg_replace('/\s+/', '', $statusCheck->profile_img)) : user_img;

							        $userInfo['name'] = $statusCheck->name;
							        $userInfo['user_name'] = $statusCheck->user_name;
							        $userInfo['city'] = $statusCheck->city;
							        $userInfo['refrence_code'] = $statusCheck->refrence_code;

							        // Return success response
							        return response()->json([
							            'status' => 200,
							            'success' => true,
							            'message' => "You've Successfully logged In",
							            'data' => $userInfo
							        ]);
							    } else {
							        // Return incorrect password response
							        return response()->json([
							            'status' => 400,
							            'success' => false,
							            'message' => "Email and password don't match",
							            'data' => null
							        ]);
							    }
							} else {
							    // Return account disabled response
							    return response()->json([
							        'status' => 400,
							        'success' => false,
							        'message' => "Account is disabled from admin",
							        'data' => null
							    ]);
							}
							        
                        }else{                        
                            return response()->json(['status'=>400, 'success'=>false,'massage' =>"Couldn't find your account"]);
                        }
                    }else{
                        if($user->user_type == $request->user_type){                        
                            $statusCheck = User::where([['phone', $is_phone],['user_type',$request->user_type],['status','1']])->first();
                            if(!empty($statusCheck)){  

                                $update=User::where('id', $user->id)->update(['fcm_token'=>$request->fcm_token?$request->fcm_token:null, 'is_loggedin'=>'1']);
                                $token = $user->createToken($request->phone)->accessToken;
                                $userInfo['id'] = $user->id;
                                $userInfo['user_type'] = (int)$user->user_type;                        
                                $userInfo['phone'] = $user->phone;
                                $userInfo['referrel_code'] = $user->referrel_code;
                                $userInfo['gender'] = $user->gender;
                                $userInfo['fcm_token'] = $user->fcm_token;
                                $userInfo['token'] = $token;                    
                                $userInfo['is_verified'] =1; 
                                $userInfo['profile_img'] = $user->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','', $user->profile_img)):user_img;                       
                                if($request->user_type==2){
                                    $getLawyer=LawyerDetail::where('user_id',$user->id)->first();
                                    $userInfo['first_name'] = $user->first_name;
                                    $userInfo['last_name'] = $user->last_name;
                                    if(!empty($getLawyer)){
                                        $getarea=PracticeArea::select('name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                                        $practice=[];
                                        if(count($getarea)){                                            
                                            foreach ($getarea as $value) {
                                                array_push($practice,$value->name);
                                            }
                                        }
                                        $getlanguage=Language::select('language_name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                                        $language=[];
                                        if(count($getlanguage)){
                                            foreach ($getlanguage as $value) {
                                                array_push($language,$value->language_name);
                                            }
                                        }
                                        $getstate=State::select('state_name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                                        $state=[];
                                        if(count($getstate)){                                            
                                            foreach ($getstate as $value) {
                                                array_push($state,$value->state_name);
                                            }
                                        }
                                        $language=implode(',',$language);
                                        
                                        $userInfo['education_details']=$getLawyer->education_details;
                                        $userInfo['experience']=$getLawyer->experience;
                                        $userInfo['description']=$getLawyer->description;
                                        $userInfo['ac_no']=$getLawyer->ac_no;
                                        $userInfo['ifsc']=$getLawyer->ifsc;
                                        $userInfo['ifsc']=$getLawyer->bank_name;
                                        $userInfo['account_name']=$getLawyer->account_name;
                                        $userInfo['language']=$language;
                                        $userInfo['practice_area']=$practice;
                                        $userInfo['practice_state']=$state;
                                    }
                                    
                                    

                                   
                                }else{
                                    $userInfo['name'] = $user->name;
                                    $userInfo['user_name'] = $user->user_name;
                                    $userInfo['city'] = $user->city;
                                    $userInfo['refrence_code'] = $user->refrence_code;
                                }
                                // if($request->user_type==2){
                                //     $data['lawyer']=$userInfo;
                                // }else{
                                //     $data['user']=$userInfo;
                                // }
                    
                    
                    
                                return response()->json(['status' => 200, 'success'=>true, 'message' =>"You've Successfully logged In", 'data' => $userInfo]);                                
                                
                            }else{
                                return response()->json(['status'=>400,'success'=>false, 'massage' =>"Account is disable from admin"]);
                            }                        
                        }else{                        
                            return response()->json(['status'=>400, 'success'=>false,'massage' =>"Couldn't find your account"]);
                        }
                    }
                }else{
                    return response()->json(['status'=>400, 'success'=>false,'massage' =>"Couldn't find your account"]);
                }
            }else{
                return response()->json(["status" => 400, "success" => false, 'message' =>"User is not registered"]);
            }                
        }else{
            return response()->json(["status" => 400, "success" => false, 'message' =>"User is not registered"]);
        }           
    
    }

    public function checkUser(Request $request){       
        $validator = Validator::make($request->all(),[
            'phone' => 'required',           
            
        ]);
      
        if($validator->fails()){
            return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
        }            

        if($request->is_signin =='1')
        {
            $user=User::where(['phone'=>$request->phone,'is_verified'=>'1','is_delete'=>'0'])->first();
                if(!empty($user)){   
                    if($user->user_type == $request->user_type)
                    {

                        return response()->json(['status'=>200, 'success'=>true,'massage' =>"user Exists redirect for Otp"]);
                    }else{

                        if($user->user_type=='1')
                        {                    
                            return response()->json(['status'=>400, 'success'=>false,'massage' =>"Already registered. Please log in with your User Application."]);

                        }else{

                            return response()->json(['status'=>400, 'success'=>false,'massage' =>"Already registered. Please log in with your Lawyer Application."]);
                        }
                        
                    }             
                    
                }else{
                                       
                        return response()->json(['status'=>400, 'success'=>false,'massage' =>"User not Exists."]);                
                
                }  
        }else{

            $user=User::where(['phone'=>$request->phone,'is_verified'=>'1','is_delete'=>'0'])->first();
            if(!empty($user)){   
                if($user->user_type == $request->user_type)
                {

                    return response()->json(['status'=>400, 'success'=>false,'massage' =>"User Already Register"]);
                }else{

                    if($user->user_type=='1')
                    {                    
                        return response()->json(['status'=>400, 'success'=>false,'massage' =>"Already registered. Please log in with your User Application."]);

                    }else{

                        return response()->json(['status'=>400, 'success'=>false,'massage' =>"Already registered. Please log in with your Lawyer Application."]);
                    }
                    
                }             
                
            }else{

                
                // if($user->user_type=='1')
                // {                    
                    return response()->json(['status'=>200, 'success'=>false,'massage' =>"User not Exists.Register"]);

                // }else{

                //     return response()->json(['status'=>200, 'success'=>false,'massage' =>"Lawyer not Exists.Register"]);
                // }
            
            }  


        }
        
                      
                  
    
    }

    public function otpVerified(Request $request){
        // type for 1 emaiverification
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, [
            'email' => 'required',
            'otp' => 'required|min:4|max:4',
            'type'=>""
            
        ]);
        if($validator->fails()){
            return response()->json(["status" => 400,'success'=>false, "message" => $validator->errors()->first()]);
        }

        $EmailVerification=EmailVerification::where('token', $request->otp)->where('email', $request->email)->first();
        
        if(empty($EmailVerification)) {
            return response()->json([ 'status'=>400,'success'=>false, 'message' =>"Invalid OTP"]);
        }

        if(Carbon::parse($EmailVerification->updated_at)
                ->addMinutes(1)
                ->isPast()){
            
            $EmailVerification->delete();
            return response()->json([ 'status' => 400,'success'=>false , 'message' =>"Otp Expired"]);
        }
        if($EmailVerification){
            $user = User::where('phone', $EmailVerification->email)->update(['is_verified'=>'1']);
            if (!$user) {
                return response()->json([ 'status' => 400,'success'=>false, "message"=>"Please Enter Valid Email"]);
            }else{
                if($request->type=='1'){
                    $userDetail =User::where('phone', $EmailVerification->email)->first();
                    $token = $userDetail->createToken($request->email)->accessToken;
                    $EmailVerification->delete();          
                    $array['token']=$token;
                    return response()->json([ 'status' => 200, 'success'=> true, 'message' =>"OTP Verified",'data'=>$array]);
                }else{
                    $userDetail =User::where('phone', $EmailVerification->email)->first();
                    $token = $userDetail->createToken($request->email)->accessToken;
                    $EmailVerification->delete();                    
                    return response()->json([ 'status' => 200, 'success'=> true, 'message' =>"OTP Verified"]);            
                }
            }
        }
    
    }

   
    public function resetPassword(Request $request){ 
             
            $data = json_decode($request->getContent(), true);
            $validator = Validator::make($data, [
                'email' =>  'required|email',
                'password' => 'required|regex:/\A(?!.*[:;]-\))[ -~]+\z/|max:32|min:8',
                
            ],
            [        
              'password.regex'=>'Emoji is not allow in password',
              
             
            ]);
            if ($validator->fails()){
                return response()->json(["status"=> 400,"success" =>false,"message" => $validator->errors()->first()]);
            }
            $user=User::where('phone',$request->email)->first();
            // return $user;
            if(!empty($user)){                
                 
                $password = Hash::make($request->password);
                $original_password =$request->password;
                $data=User::where("phone",$request->email)->update(
                    [
                        "password" =>$password,
                        "original_password" =>$original_password,

                    ]);                           

                return response()->json(["status"=>200,'success'=>true,"message"=>"You're  password has successfully been reset"]);
                
            }else{
                return response()->json(['status' => 400, 'success' => false, 'message' =>"User doesn't exist"]);
            }
            
    }

    public function changePassword(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:8|max:32',            
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $hashedPassword = Auth::user()->password;
        if(\Hash::check($request->old_password, $hashedPassword)) {
            if(!\Hash::check($request->new_password, $hashedPassword)) {
                $user = User::find(Auth::user()->id);
                $user->password = Hash::make($request->new_password);                
                User::where('id', Auth::user()->id)->update([
                	'password' => $user->password,
                	'original_password'=>$request->new_password
                ]);

                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Password successfully changed',
                ]);
            } else {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => "New password should be different from the current password",
                ]);
            }
        } else {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => "The current password doesn't match.",
            ]);
        }
    }


    public function resendotp(Request $request){
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data,[
            'email' => 'required|email'                
        ],
        [
            'email'=>trans("customMessages.emailformat"),
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => 400, "success"=>false, "message" => $validator->messages()->first()]);
        }
        $checkMail = User::where(['phone'=> $request->email])->first();
        if(!empty($checkMail)){
            $emailActive = User::where(['phone'=>$request->email])->first();
            if (!empty($emailActive)){
                $token = Logics::SendCode();
                $emailVerify= EmailVerification::where('email',$request->email)->first();
                if(!empty($emailVerify)){
                    $result = EmailVerification::where('email',$request->email)
                                                ->update(['token'=>$token]);
                }else{
                    $emailVerify = new EmailVerification;
                    $emailVerify->email = $request->email;
                    $emailVerify->token= $token;
                    $result = $emailVerify->save();
                    
                }                   
                
                if($result==true){
                    $checkMail->notify(new ForgetPwdLinkNotification($token)); 
                    return response()->json(["status" => 200, "success"=>true, "message"=> "Otp successfully resent on your mail"]);
                }else{
                    return response()->json(["status" => 400, "success"=>false, "message"=>"failed_try_again"]);
                }
            }else{
                return response()->json(["status" => 400, "success"=>false, "message" =>"User doesn't exist"]);
            }
        }else{                
            return response()->json(["status" => 400, "success"=>false, "message" =>"User doesn't exist"]);
        }

    }

    public function forgetPassword(Request $request){
    

        $validator = Validator::make($request->all(),[
            'email' => 'required' // Added email validation
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 400,
                "success" => false,
                "message" => $validator->messages()->first(),
            ]);
        }

       
        $checkMail = User::where('phone', $request->email)->first();
        if (!empty($checkMail)) {     
            $token = Logics::SendCode();
            $emailVerify = EmailVerification::where('email', $request->email)->first();

            if (!empty($emailVerify)) {
                $result = EmailVerification::where('email', $request->email)->update(['token' => $token]);
            } else {
                $emailVerify = new EmailVerification;
                $emailVerify->email = $request->email;
                $emailVerify->token = $token;
                $result = $emailVerify->save();
            }

            if($result == true) {
                $checkMail->email=$checkMail->phone;
                // return $checkMail;
                $checkMail->notify(new ForgetPwdLinkNotification($token));
                return response()->json([
                    "status" => 200,"success" => true,"message" => "Otp Successfully sent"]);
            } else {
                return response()->json([
                    "status" => 400,
                    "success" => false,
                    "message" => "Failed try again",
                ]);
            }
        } else {
            return response()->json([
                "status" => 400,
                "success" => false,
                "message" => "Email doesn't exist",
            ]);
        }
    }



    public function logout(){ 
        $user = Auth::user()->token(); 
        $user_id = Auth::user()->id;
        $update['fcm_token'] = null;
        $update['is_loggedin'] = '0';
        $query = User::where('id',$user_id)->update($update);
        $user->revoke();
        return response()->json(['status' => 200,'success' => true, 'message' =>"Successfully Logged out"]);

    }

    public function updateFcm(Request $request){
        $validator= Validator::make($request->all(),[
            "fcm_token"=>"required"
        ]);
        if($validator->fails()){
            return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
        }
        $user=Auth::user();
        if(!empty($user)){
            $user->fcm_token=$request->fcm_token;
            $query=$user->save();
            if($query==true){
                return response()->json(['status'=>200,'success' => true, 'message' =>"Fcm token successfully updated"]);
            }else{
                return response()->json(['status'=>400,'success' => false, 'message' => "Failed try again"]);
            }
        }else{
            return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
        }
        
    }


    public function updateProfile(Request $request){
        $user=Auth::user();  
        if($request->user_type==1){  
            $validator = Validator::make($request->all(), [
                'user_name' => 'required',
                'name' => 'required',
                'city' => 'required',                    
                'gender' => 'required',
                'profile_img' => '',
                'user_type'=>"required",
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'user_type'=>"required",
                'gender' => 'required',
                'profile_img' => '',
                'first_name' => 'required',
                'last_name' => 'required',
                'practice_area' => 'required',
                'practice_state' => 'required',
                'description' => 'required',
                'experience' => 'required',
                'language' => 'required',
                "license"=>'',
                "pan"=>'',
                'ac_no'=>'required',
                "ifsc"=>"required",
                "account_name"=>"required",

            ]);
        }
                 
        if($validator->fails()){
            return response()->json(['status'=>400,'success' => false, 'message' => $validator->errors()->first()]);
        }
                  
        if(!empty($user)){
            if($request->user_type==2){
                // $user->user_name=$request->user_name;
                $user->name=$request->name;
                $user->gender=$request->gender;
                if($request->hasFile('profile_img')) {
                    $file = $request->file('profile_img');
                    $filename = time() . '_' . $file->extension();
                    $file->move(storage_path('app/public/profile'), $filename); 
                    $user->profile_img=$filename;
                }    
                $query=$user->save();
                $checkDetail=LawyerDetail::where('user_id',$user->id)->first();
                if(!empty($checkDetail)){
                    $saveDetail=LawyerDetail::find($checkDetail->id);
                }else{
                    $saveDetail=new LawyerDetail();                                
                }
                $saveDetail->user_id=$user->id;
                $saveDetail->practice_area=$request->practice_area;
                $saveDetail->practice_state=$request->practice_state;
                $saveDetail->description=$request->description;
                $saveDetail->education_details=$request->education_details;
                $saveDetail->experience=$request->experience;
                $saveDetail->language=$request->language;
                $saveDetail->description=$request->description?$request->description:null;
                
                $saveDetail->ac_no=$request->ac_no;
                $saveDetail->ifsc=$request->ifsc;
                $saveDetail->bank_name=$request->bank_name;
                $saveDetail->account_name=$request->account_name;
                $detailQuery=$saveDetail->save();
                if($detailQuery==true){                                
                    if($request->hasFile('license')) {                                    
                            $check=Document::where('user_detail_id',$saveDetail->id)->where('doc_type','1')->first();
                            if(!empty($check)){
                                $delete=Document::where('id',$check->id)->where('doc_type','1')->delete();
                            }
                            
                            $filename = 'license' . time() . '.' . $request->license->extension();
                            $request->license->move(storage_path('app/public/user/'), $filename); 
                            $filename = trim(preg_replace('/\s+/','', $filename));

                            $document=new Document();                                    
                            $document->user_detail_id=$saveDetail->id;
                            $document->file=$filename;
                            $document->license_no=$request->license_no;
                            $document->doc_type="1";
                            $documentquery=$document->save();
                            $updatekyc=LawyerDetail::where('user_id',$user->id)->update([
                                "is_adminVerified"=>'0'
                            ]);
                    }
                    if($request->hasFile('pan')) {                                    
                        $check=Document::where('user_detail_id',$saveDetail->id)->where('doc_type','2')->first();
                        if(!empty($check)){
                            $delete=Document::where('id',$check->id)->where('doc_type','2')->delete();
                        }
                        
                        $filename ='pan' . time() . '.' . $request->pan->extension();
                        $request->pan->move(storage_path('app/public/user/'), $filename); 
                        $filename = trim(preg_replace('/\s+/','', $filename));

                        $document=new Document();                                    
                        $document->user_detail_id=$saveDetail->id;
                        $document->file=$filename;
                        $document->pan_no=$request->pan_no;
                        $document->doc_type="2";
                        $documentquery=$document->save();
                       
                        $updatekyc=LawyerDetail::where('user_id',$user->id)->update([
                                "is_adminVerified"=>'0'
                            ]);
                    }  
                    $getpan=Document::where('user_detail_id',$saveDetail->id)->where('doc_type','2')->first();
                    if(!empty($getpan)){
                        if($getpan->pan_no !=$request->pan_no){
                                $document=Document::find($getpan->id);                                    
                                $document->user_detail_id=$saveDetail->id;                               
                                $document->pan_no=$request->pan_no;
                                $document->doc_type="2";
                                $documentquery=$document->save();
                                
                                $updatekyc=LawyerDetail::where('user_id',$user->id)->update([
                                    "is_adminVerified"=>'0'
                                ]);
                        }
                    }
                    $getlicense=Document::where('user_detail_id',$saveDetail->id)->where('doc_type','1')->first();
                    if(!empty($getlicense)){
                        if($getlicense->license_no !=$request->license_no){
                            $document=Document::find($getlicense->id);                                    
                            $document->user_detail_id=$saveDetail->id;                               
                            $document->license_no=$request->license_no;
                            $document->doc_type="1";
                            $documentquery=$document->save();
                            
                            $updatekyc=LawyerDetail::where('user_id',$user->id)->update([
                                "is_adminVerified"=>'0'
                            ]);
                        }
                    }
                    
                   return response()->json(["status" => 200,"success" => true, "message" => "Profile successfully update"]);    
                }
            }else{                    
                // $user->user_name=$request->user_name;
                $user->name=$request->name;                   
                $user->city=$request->city;
                $user->gender=$request->gender;
                 if($request->hasFile('profile_img')) {
                    $file = $request->file('profile_img');
                    $filename = time() . '_' . $file->extension();
                    $file->move(storage_path('app/public/profile'), $filename); 
                    $user->profile_img=$filename;
                }    
                $query=$user->save();
                if($query==true){
                   return response()->json(["status" => 200,"success" => true, "message" => "Profile successfully update"]);  
                }else{
                    return response()->json(["status" =>400,"success" => false, "message" => "Profile not update"]); 
                }                
            } 
        
        }else{
            return response()->json(["status" => 400, 'success'=>false, "message" =>"User doesn't exist"]);
        }

    }

    public function getLawyerDeatils($id=''){  
        if($id!=''){
            $getLawyer=LawyerDetail::where('lawyer_details.user_id',$id)
                ->join('users','users.id','lawyer_details.user_id')
                ->where('users.is_verified','1')->where('users.user_type','2')->first();   
                   
            if(!empty($getLawyer)){ 
                // json_decode(json)
                $getarea=PracticeArea::select('name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                $practice=[];
                if(count($getarea)){
                    // $practice=[];
                    foreach ($getarea as $value) {
                        array_push($practice,$value->name);
                    }
                }
                $getlanguage=Language::select('language_name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                $language=[];
                if(count($getlanguage)){
                    // $practice=[];
                    foreach ($getlanguage as $value) {
                        array_push($language,$value->language_name);
                    }
                }
                $getstate=State::select('state_name')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                $state=[];
                if(count($getstate)){
                    // $practice=[];
                    foreach ($getstate as $value) {
                        array_push($state,$value->state_name);
                    }
                }
                
                $language=implode(',',$language);
                $userInfo['is_active']=0;
                $user=Auth::user();
                if(!empty($user)){                
                    $fav=Favorite::where('status','1')->where('lawyer_id',$id)->where('user_id',$user->id)->first();
                   
                    if(!empty($fav)){
                        $userInfo['is_favorite']=1;
                    }else{
                        $userInfo['is_favorite']=0;
                    }
                    $utcTime = Carbon::now()->format('Y-m-d H:i:s');

                    $indiaTime = Carbon::createFromFormat('Y-m-d H:i:s', $utcTime, 'UTC')->setTimezone('Asia/Kolkata');
                    $today = Carbon::now();
                    $todaytime = Carbon::parse($indiaTime)->format('H:i:s');
                    
                    $dayOfWeek = $today->dayOfWeek;
                    $checklawyer=Availability::where(['user_id'=>$id,'day_id'=>$dayOfWeek,'status'=>'1'])->first();
                    if(!empty($checklawyer)){
                       
                        $gettime=AvailabilityTime::where('availability_id',$checklawyer->id)->where('status','1')->get();
                        if(sizeof($gettime)){
                            foreach ($gettime as $value) {
                                $fromtimearray=explode(":", $value->from);
                                $totimearray=explode(":", $value->to);

                                $mytimeArray = explode(":", $todaytime);
                                
                                $toTime = Carbon::createFromTime($totimearray[0], $totimearray[1], $totimearray[2]);
                                $fromTime = Carbon::createFromTime($fromtimearray[0], $fromtimearray[1], $fromtimearray[2]);

                                $myTime = Carbon::createFromTime($mytimeArray[0], $mytimeArray[1], $mytimeArray[2]);
                                
                                if ($myTime->between($fromTime, $toTime)){
                                    $dataactive=1;
                                }else{
                                    $dataactive=0;
                                } 
                            }
                            $userInfo['is_active']=$dataactive;
                        }
                        
                    }
                    
                }else{
                     $userInfo['is_favorite']=0;
                }
                $rating=Review::where('lawyer_id',$id)->avg('rating');
                $getreview=Review::where('lawyer_id',$id)->count('id');
                $userInfo['id'] = $id;
                $userInfo['user_type'] = (int)$getLawyer->user_type;                        
                $userInfo['phone'] = $getLawyer->phone;                    
                $userInfo['gender'] = $getLawyer->gender;                                                     
                $userInfo['is_verified'] =(int)$getLawyer->is_verified; 
                $userInfo['profile_img'] = $getLawyer->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','', $getLawyer->profile_img)):user_img;
                $userInfo['first_name'] = $getLawyer->first_name;
                $userInfo['last_name'] = $getLawyer->last_name;
                $userInfo['practice_area']=$practice;
                $userInfo['practice_state']=$state;
                $userInfo['description']=$getLawyer->description;
                $userInfo['education_details']=$getLawyer->education_details;
                $userInfo['experience']=$getLawyer->experience;
                $userInfo['language']=$language;
                $userInfo['ac_no']=$getLawyer->ac_no;
                $userInfo['ifsc']=$getLawyer->ifsc;
                $userInfo['bank_name']=$getLawyer->bank_name;
                $userInfo['account_name']=$getLawyer->account_name;
                $userInfo['rating']=round($rating,2);
                $userInfo['total_review']=$getreview;
                $userInfo['is_active']=0;
                   
                // $userInfo['docs']=$docs;
                
                
                return response()->json(['status' => 200,"success" => true,'message'=> "Lawyer Details",'data'=>$userInfo]);
            }else{
                return response()->json(['status' => 400,"success" => false,'message'=> "User not exist"]);
            }
        }else{
            return response()->json(['status' => 400,"success" => false,'message'=> "User not found"]);
        }
        
    }


    public function getprofile(){  
        $users=Auth::user();
        if(!empty($users)){
            if($users->user_type==2){
            $user=User::where('id',$users->id)->first();

            }else{
            $user=User::where('id',$users->id)->where('is_verified','1')->first();

            }
            if(!empty($user)){ 
                $userInfo['id'] = $user->id;
                $userInfo['user_name'] = $user->user_name;
                $userInfo['user_type'] = (int)$user->user_type;                        
                $userInfo['phone'] = $user->phone;               
                $userInfo['gender'] = $user->gender;
                $userInfo['fcm_token'] = $user->fcm_token;
                $userInfo['profile_img'] = $user->profile_img?imgUrl.'profile/'.$user->profile_img:user_img;                       
                // $userInfo['profile_img'] = $user->profile_img ? imgUrl.'profile/'.trim(preg_replace('/\s+/','', $user->profile_img)):user_img;                       
                if($user->user_type==2){
                    $getLawyer=LawyerDetail::where('user_id',$user->id)->first();
                    $userInfo['first_name'] = $user->first_name;
                    $userInfo['last_name'] = $user->last_name;
                    
                   
                    if(!empty($getLawyer)){
                        $getarea=PracticeArea::select('id','name','image')->whereIn('id',json_decode($getLawyer->practice_area))->get();
                        $practice=[];
                        if(count($getarea)){
                            foreach ($getarea as $value) {
                                $value->image=$value->image?imgUrl.'/practice/'.$value->image:default_img;
                                array_push($practice,$value);
                            }
                        }
                        $getlanguage=Language::select('id','language_name')->whereIn('id',json_decode($getLawyer->language))->get();
                        $language=[];
                        if(count($getlanguage)){
                            foreach ($getlanguage as $value) {
                                array_push($language,$value);
                            }
                        }
                        
                        $getstate=State::select('id','state_name')->whereIn('id',json_decode($getLawyer->practice_state))->get();
                        $state=[];
                        if(count($getstate)){
                            foreach ($getstate as $value) {
                                array_push($state,$value);
                            }
                        }
                        $checkagora=AgoraTokenLawyer::where('user_id',$users->id)->first();
                        if(!empty($checkagora)){

                            $userInfo['agoraToken']=$checkagora->token;
                             $userInfo['agora_token'] = $checkagora->token;
                            $userInfo['channel_name'] = $user->user_name;
                        }else{
                            $userInfo['agora_token'] = "";
                            $userInfo['channel_name'] = $user->user_name;
                            $userInfo['agoraToken']="";

                        }
                        
                        $userInfo['education_details']=$getLawyer->education_details;
                        $userInfo['experience']=$getLawyer->experience;
                        $userInfo['ac_no']=$getLawyer->ac_no;
                        $userInfo['ifsc']=$getLawyer->ifsc;
                        $userInfo['account_name']=$getLawyer->account_name;
                        $userInfo['bank_name']=$getLawyer->bank_name?$getLawyer->bank_name:null;
                        $userInfo['language']=$language;
                        $userInfo['practice_area']=$practice;
                        $userInfo['practice_state']=$state;
                        $userInfo['description']=$getLawyer->description;
                        $userInfo['referrel_code']=$user->referrel_code;
                        $userInfo['is_adminVerified'] =$getLawyer->is_adminVerified;
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

                            
                }else{
                    $userInfo['name'] = $user->name;
                    $userInfo['user_name'] = $user->user_name;
                    $userInfo['city'] = $user->city;
                    $userInfo['refrence_code'] = $user->refrence_code;
                    $userInfo['referrel_code'] = $user->referrel_code;
                }
                if($user->user_type==2){
                    return response()->json(['status' => 200,"success" => true,'message'=> "Profile Details For Lawyer",'data'=>$userInfo]);
                    
                }else{
                    return response()->json(['status' => 200,"success" => true,'message'=> "Profile Details User",'data'=>$userInfo]);
                   
                }
                
            }else{
                return response()->json(['status' => 400,"success" => false,'message'=> "User not exist"]);
            }
        }else{
            return response()->json(['status' => 401,"success" => false,'message'=> "You're not logged in"]);
        }
        
    }

    public function removeProfileImge(Request $request){
        $rvn=Auth::user();            
         $path=storage_path("app/public/user_profile/".$rvn->profile_img);
            if (file_exists($path)) {
                unlink($path);    
            }            
            $query=User::where(['id'=>$rvn->id,'status'=>'1'])->update([
                'profile_img'=>''
            ]); 
        if($query){                        
            return response()->json(["status"=>200,"success"=>true,"message" =>"Profile Image successfully remove"]);
        }else{    
        return response()->json(["status" => 400,"success"=>false,"message"=>"Profile remove failed try again"]);

        } 
         
    }
    
    public function favoriteUnfavorite(Request $request){
        $validator=Validator::make($request->all(),[
            "lawyer_id"=>"required",
            
        ]);
        if($validator->fails()){
            return response()->json(["status" => 400, "success"=> false, "message" => $validator->messages()->first()]);
        }
        $user=Auth::user();
        if($user->user_type==1){
            $check=Favorite::where('user_id',$user->id)->where('lawyer_id',$request->lawyer_id)->first();
            if(!empty($check)){
                $favorite=Favorite::find($check->id);
                if($favorite->status==1){
                    $favorite->status=0;
                }else{
                    $favorite->status=1;
                }
            }else{
                $favorite=new Favorite();
                $favorite->status=1;
            }
                    
            $favorite->user_id=$user->id;
            $favorite->lawyer_id=$request->lawyer_id;
            
            $query=$favorite->save();
            if($query==true){
                if($favorite->status==1){
                    $msg="lawyer added as favorite";
                }else{
                    $msg="lawyer remove from favorite list";
                }
                return response()->json(["status" => 200,"success"=>true,"message"=>$msg]);
            }else{
                return response()->json(["status" => 400,"success"=>false,"message"=>"Something went wrong try again"]); 
            }
        }else{
            return response()->json(["status" => 400,"success"=>false,"message"=>"You're not auhtorized"]);
        }
        
    }


    public function favoriteUnfavoriteList(){
       
        $user=Auth::user(); 
        if($user->user_type==1){
           $favorite= DB::table('lawyer_details')
            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.experience','lawyer_details.education_details','lawyer_details.language')
            ->join('users', 'users.id', '=', 'lawyer_details.user_id')
            ->join('favorites', 'favorites.lawyer_id', '=', 'lawyer_details.user_id')
            ->where('users.is_verified', 1)
            ->where('users.user_type',2)
            ->where('favorites.user_id',$user->id)
            ->where('favorites.status','1')
            ->get();                
            if(sizeof($favorite)){
                foreach($favorite as $row){
                    $lawyerDetails=User::select('first_name','last_name','user_name','profile_img','city')->where('id',$row->user_id)->first();
                    
                    if(!empty($lawyerDetails)){
                        $practice_area=json_decode($row->practice_area);                            
                        foreach($practice_area as $area){
                            $getarea=PracticeArea::select('name')->where('id',$area)->first();                               
                            if(!empty($getarea)){
                                $area=$getarea->name;
                                $areadata[]=$area;
                            }
                        }
                        $languages=json_decode($row->language);  
                        $language_name=[];
                        foreach($languages as $language){
                            $getlanguage=Language::select('language_name')->where('id',$language)->first();
                            if(!empty($getlanguage)){
                                $language=$getlanguage->language_name;
                                $language_name[]=$language;
                            }
                        }
                    
                        $user_name=$lawyerDetails->user_name;
                        $city=$lawyerDetails->city;
                        $language=implode(',',$language_name);
                        $language=$language;
                        $area=$areadata;
                        $profile_img=$lawyerDetails->profile_img?imgUrl.'profile/'.$lawyerDetails->profile_img:user_img;
                    }
                    $checkagora=AgoraTokenLawyer::where('user_id',$row->user_id)->first();
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
                    $checklawyer=Availability::where('user_id',$row->user_id)->where('day_id',$today)->where('status','1')->first();
                    
                    if(!empty($checklawyer)){
                        $checkavailability=AvailabilityTime::where('availability_id',$checklawyer->id)->where('from','<',$istTime)->where('to','>',$istTime)->where('status','1')->first();                        
                        if(!empty($checkavailability)){
                           $row->is_active=1;
                        }else{
                            $row->is_active=0;
                        }

                        $checktime=AvailabilityTime::where('availability_id',$checklawyer->id)->where('from','>',$istTime)->where('status','1')->first();
                        if(!empty($checktime)){
                            $currentTime = Carbon::now();
                            $istTime = $currentTime->setTimezone('Asia/Kolkata');
                            if(!empty($checktime)){
                                $timeDifference = $istTime->diff($checktime->from);
                                if($istTime->lessThan($checktime->from)) {
                                    $difference = $timeDifference->format('%h hours, %i minutes');
                                    $row->online = 'Available in ' . $difference;
                                }else{
                                    $row->online = 'Unavailable';
                                }
                            }else{
                                $row->online = "Unavailable";
                            }
                        }                       
                    }else{
                        $row->is_active=0;
                        $row->online = "Unavailable";                        
                    }
                    $rating=Review::where('lawyer_id',$row->user_id)->avg('rating');
                    $getreview=Review::where('lawyer_id',$row->user_id)->count('id');
                    $row->id=$row->user_id;
                    $row->user_name=$user_name;
                    $row->profile_img=$profile_img;
                    $row->education_details=$row->education_details;
                    $row->experience=$row->experience;
                    $row->language=$language;
                    // $row->practice_area=$area;
                    $row->practice_area=implode(',', $area);
                    $row->rating=(string)round($rating,2);
                    $row->reviews=$getreview;
                    $data[]=$row;
                    unset($row->user_id);
                    unset($row->average_rating);
                }
                
                return response()->json(["status" => 200,"success"=>true,"message"=>"All Favorite lawyer list",'data'=>$data]); 
    
            }else{
                return response()->json(["status" => 200,"success"=>true,"message"=>"favoritet",'data'=>array()]); 
            }
        }else{
            return response()->json(["status" => 400,"success"=>false,"message"=>"You're not auhtorized"]);
        }
       
    }

    public function lawyersList($type=''){  
        if($type==1){
            $lawyers = DB::table('lawyer_details')
            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.education_details','lawyer_details.language')
            ->join('users', 'users.id', '=', 'lawyer_details.user_id')
            ->where('lawyer_details.is_adminVerified', 1)
            ->where('lawyer_details.isPremium', 1)
            ->where('users.user_type',2)
            ->orderByDesc('lawyer_details.id')
            // ->orderByDesc('lawyer_details.is_available')
            ->get();
            $msg="Premium Lawyer list";
        
        }elseif($type==2){            
            $lawyers = DB::table('lawyer_details')
            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.education_details','lawyer_details.language')
            ->join('users', 'users.id', '=', 'lawyer_details.user_id')
            ->where('users.user_type',2)
            ->where('lawyer_details.is_adminVerified',1)
            ->orderByDesc('lawyer_details.id')
            // ->orderByDesc('lawyer_details.is_available')
            ->get();
            // return $lawyers;
         $msg="Active Lawyer list";
        
        }elseif($type==3){
            $area=1;
            $lawyers = DB::table('lawyer_details')
            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.education_details','lawyer_details.language')
            ->join('users', 'users.id', '=', 'lawyer_details.user_id')
            ->where('lawyer_details.is_adminVerified',1)
            // ->where('lawyer_details.is_available', 1)
            ->where('users.user_type',2)
            ->where('lawyer_details.practice_area','like','%'.$area.'%')
            ->orderByDesc('lawyer_details.id')
            // ->orderByDesc('lawyer_details.is_available')
            ->get();
    
         $msg="Family Lawyer list";
        }else{
            $lawyers = DB::table('lawyer_details')
            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.education_details','lawyer_details.language')
            ->join('users', 'users.id', '=', 'lawyer_details.user_id')
            ->where('lawyer_details.is_adminVerified',1)
            // ->where('lawyer_details.is_available', 1)
            ->where('users.user_type',2)
            ->orderByDesc('lawyer_details.id')
            ->orderByDesc('lawyer_details.is_available')
            ->get();
             $msg="All Lawyer list";
        }
        // return$lawyers; 
       
        if(sizeof($lawyers)){
            $data=[];
            
                if($type==2 || $type==1){
                    foreach($lawyers as $row){


                        $year1=Carbon::now()->format("Y-m-d");
                        $checkagora=AgoraTokenLawyer::where('user_id',$row->user_id)->first();    
                        
                        $myLawyer=User::select('user_name')->where('id',$row->user_id)->first();
            
                  
                        
                        if(!empty($checkagora)){
                            if($checkagora->date!=$year1){
                                $res=$this->createAgoraToken($myLawyer->user_name);    
                                if($res) {
                                    $updatetokenquery=AgoraTokenLawyer::where('user_id',$row->user_id)->update([
                                        "token"=>$res,
                                        'date'=>$year1
                                    ]);
                                }
                            }
                        }else{
                            $res=$this->createAgoraToken($myLawyer->user_name);
                            if($res){                    
                                $updateagora=new AgoraTokenLawyer();
                                $updateagora->user_id=$row->user_id;
                                $updateagora->channel=$myLawyer->user_name;
                                $updateagora->date=$year1;
                                $updateagora->token=$res;
                                $updatetokenquery=$updateagora->save();
                            }
            
            
                        }



                   


                    $checkavailabilityforlawyer=Availability::where('user_id',$row->user_id)->first();

                        if(!empty($checkavailabilityforlawyer)){
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
                                $row->is_favorite=0;
                                $row->is_active=0;
                                $currentTime=Carbon::now()->format('H:i:s');
                                $utcTime = Carbon::createFromFormat('H:i:s', $currentTime, 'UTC');      
                                // return $utcTime;              
                                $istTime = $utcTime->setTimezone('Asia/Kolkata')->format('H:i:s');  
                                // return $istTime;
                                $checklawyer=Availability::where('user_id',$row->user_id)->where('day_id',$today)->where('status','1')->first();
                                // echo $istTime."<br>";
                                // echo $istTime."<br>";
                                // echo $checklawyer->id."<br>";
                                // die;
                                if(!empty($checklawyer)){
                                    $checkavailability=AvailabilityTime::where('availability_id',$checklawyer->id)->where('from','<',$istTime)->where('to','>',$istTime)->where('status','1')->first();
                                    // return $checkavailability;
                                    if(!empty($checkavailability)){
                                        $lawyerDetails=User::select('user_name','profile_img','city')->where('id',$row->user_id)->first();                        
                                        if(!empty($lawyerDetails)){
                                            $practice_area=json_decode($row->practice_area);
                                            $areadata=[]; 
                                            foreach($practice_area as $area){
                                                $getarea=PracticeArea::select('name')->where('id',$area)->first();
                                               
                                                if(!empty($getarea)){
                                                    $area=$getarea->name;
                                                    $areadata[]=$area;
                                                }
                                            }
                                            $practice_state=json_decode($row->practice_state);
                                            $state=[]; 
                                            foreach($practice_state as $area){
                                                $getarea=State::select('state_name')->where('id',$area)->first();
                                               
                                                if(!empty($getarea)){
                                                    $area=$getarea->state_name;
                                                    $state[]=$area;
                                                }
                                            }
                                            $languages=json_decode($row->language); 
                                            $language_name=[]; 
                                            foreach($languages as $language){
                                                $getlanguage=Language::select('language_name')->where('id',$language)->first();
                                                if(!empty($getlanguage)){
                                                    $language=$getlanguage->language_name;
                                                    $language_name[]=$language;
                                                }
                                                
                                            }
                                        
                                            $user_name=$lawyerDetails->user_name;
                                            $city=$lawyerDetails->city;
                                            $language1=implode(',',$language_name);
                                            $language=$language1;
                                            $area=$areadata;
                                            $profile_img=$lawyerDetails->profile_img?imgUrl.'profile/'.$lawyerDetails->profile_img:user_img;
                                        }
                                        $user=Auth::user();
                                        if(!empty($user)){
                                            $fav=Favorite::where('status','1')->where('lawyer_id',$row->user_id)->where('user_id',$user->id)->first();                  
                                            if(!empty($fav)){
                                                $row->is_favorite=1;
                                            }
                                        }
                                        $checkagora=AgoraTokenLawyer::select('token')->where('user_id',$row->user_id)->first();
                                        
                                        if(!empty($checkagora)){
                                            $row->agoraToken=$checkagora->token;
                                        }else{
                                            $row->agoraToken="";

                                        }
                                        $row->is_active=1;
                                        $getreview=Review::where('lawyer_id',$row->user_id)->count('id');
                                        $rating=Review::where('lawyer_id',$row->user_id)->avg('rating');                   
                                        $row->id=$row->user_id;
                                        $row->user_name=$user_name;
                                        $row->profile_img=$profile_img;
                                        $row->is_call=$row->is_call;
                                        $row->is_chat=$row->is_chat;
                                        $row->call_charge=$row->call_charge;
                                        $row->chat_charge=$row->chat_charge;
                                        $row->experience=$row->experience;
                                        $row->education_details=$row->education_details?$row->education_details:null;
                                        $row->language=$language;
                                        $row->practice_area=implode(',', $area);
                                        $row->practice_state=$state;
                                        $row->rating=round($rating,2);
                                        $row->total_review=$getreview;                                        
                                        $data[]=$row;
                                        unset($row->user_id);    
                                       
                                    }

                                }
                                  
                        } 
                    }
                    // return $data;

                }else{
                    foreach($lawyers as $row){
                        $row->is_favorite=0;
                        $row->is_active=0; 
                        $checkavailabilityforlawyer=Availability::where('user_id',$row->user_id)->first();
                        if(!empty($checkavailabilityforlawyer)){
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
                                $row->is_favorite=0;
                                $row->is_active=0;
                                $currentTime=Carbon::now()->format('h:i:s');
                                $utcTime = Carbon::createFromFormat('h:i:s', $currentTime, 'UTC');                    
                                $istTime = $utcTime->setTimezone('Asia/Kolkata')->format('H:i:s');  
                                $checklawyer=Availability::where('user_id',$row->user_id)->where('day_id',$today)->where('status','1')->first();
                                if(!empty($checklawyer)){
                                    $checkavailability=AvailabilityTime::where('availability_id',$checklawyer->id)->where('from','<',$istTime)->where('to','>',$istTime)->where('status','1')->first();

                                    if(!empty($checkavailability)){
                                        $row->is_active=1;
                                    }
                                }
                            $lawyerDetails=User::select('user_name','profile_img','city')->where('id',$row->user_id)->first();                            
                            if(!empty($lawyerDetails)){
                                $practice_area=json_decode($row->practice_area);
                                $areadata=[]; 
                                foreach($practice_area as $area){
                                    $getarea=PracticeArea::select('name')->where('id',$area)->first();
                                   
                                    if(!empty($getarea)){
                                        $area=$getarea->name;
                                        $areadata[]=$area;
                                    }
                                }
                                $practice_state=json_decode($row->practice_state);
                                $state=[]; 
                                foreach($practice_state as $area){
                                    $getarea=State::select('state_name')->where('id',$area)->first();
                                   
                                    if(!empty($getarea)){
                                        $area=$getarea->state_name;
                                        $state[]=$area;
                                    }
                                }
                                $languages=json_decode($row->language); 
                                $language_name=[]; 
                                foreach($languages as $language){
                                    $getlanguage=Language::select('language_name')->where('id',$language)->first();
                                    if(!empty($getlanguage)){
                                        $language=$getlanguage->language_name;
                                        $language_name[]=$language;
                                    }
                                    
                                }
                            
                                $user_name=$lawyerDetails->user_name;
                                $city=$lawyerDetails->city;
                                $language1=implode(',',$language_name);
                                $language=$language1;
                                $area=$areadata;
                                $profile_img=$lawyerDetails->profile_img?imgUrl.'profile/'.$lawyerDetails->profile_img:user_img;
                            }
                            $user=Auth::user();
                            if(!empty($user)){
                                $fav=Favorite::where('status','1')->where('lawyer_id',$row->user_id)->where('user_id',$user->id)->first();                   
                                if(!empty($fav)){
                                    $row->is_favorite=1;
                                }else{
                                    $row->is_favorite=0;
                                }
                                $utcTime = Carbon::now()->format('Y-m-d H:i:s');

                                $indiaTime = Carbon::createFromFormat('Y-m-d H:i:s', $utcTime, 'UTC')->setTimezone('Asia/Kolkata');
                                $today = Carbon::now();
                                $todaytime = Carbon::parse($indiaTime)->format('H:i:s');
                                
                                $dayOfWeek = $today->dayOfWeek;
                                $checklawyer=Availability::where(['user_id'=>$row->user_id,'day_id'=>$dayOfWeek,'status'=>'1'])->first();
                                if(!empty($checklawyer)){
                                    $gettime=AvailabilityTime::where('availability_id',$checklawyer->id)->where('status','1')->get();
                                    if(sizeof($gettime)){
                                        foreach ($gettime as $value) {
                                            $fromtimearray=explode(":", $value->from);
                                            $totimearray=explode(":", $value->to);

                                            $mytimeArray = explode(":", $todaytime);
                                            // return $mytimeArray;
                                            $toTime = Carbon::createFromTime($totimearray[0], $totimearray[1], $totimearray[2]);
                                            $fromTime = Carbon::createFromTime($fromtimearray[0], $fromtimearray[1], $fromtimearray[2]);

                                            $myTime = Carbon::createFromTime($mytimeArray[0], $mytimeArray[1], $mytimeArray[2]);
                                            // echo  $toTime.'-----'.$fromTime.'----'.$myTime;// return $myTime;
                                            
                                            if ($myTime->between($fromTime, $toTime)){
                                                $dataactive=1;
                                            }else{
                                                $dataactive=0;
                                            } 
                                        }
                                        $row->is_active=$dataactive;
                                    }else{                       
                                        $row->is_active=0;                        
                                    }                            
                                }else{                           
                                    $row->is_active=0;                            
                                }                     

                            }
                            $checkagora=AgoraTokenLawyer::select('token')->where('user_id',$row->user_id)->first();
                            
                            if(!empty($checkagora)){
                                $row->agoraToken=$checkagora->token;
                            }else{
                                $row->agoraToken="";

                            }
                            $getreview=Review::where('lawyer_id',$row->user_id)->count('id');
                            $rating=Review::where('lawyer_id',$row->user_id)->avg('rating');                   
                            $row->id=$row->user_id;
                            $row->user_name=$user_name;
                            $row->profile_img=$profile_img;
                            $row->is_call=$row->is_call;
                            $row->is_chat=$row->is_chat;
                            $row->call_charge=$row->call_charge;
                            $row->chat_charge=$row->chat_charge;
                            $row->experience=$row->experience;
                            $row->education_details=$row->education_details?$row->education_details:null;
                            $row->language=$language;
                            $row->practice_area=implode(',', $area);
                            $row->practice_state=$state;
                            $row->rating=round($rating,2);
                            $row->total_review=$getreview;
                            
                            $data[]=$row;
                            unset($row->user_id);        
                        } 
                    }
                }

                
            
            if($data==[]){
                return response()->json(["status" => 400,"success"=>false,"message"=>$msg]); 
            }else{               
                    $data = json_encode($data, true);
                    $data = json_decode($data, true);                
                    usort($data, function ($a, $b) {
                        return $b['is_active'] - $a['is_active'];
                    });
                    $sortedJson = json_encode($data, JSON_PRETTY_PRINT);   
                return response()->json(["status" => 200,"success"=>true,"message"=>$msg,'data'=>json_decode($sortedJson)]); 
            }

        }else{
            return response()->json(["status" => 400,"success"=>false,"message"=>"Data not found"]); 
        }
    }

    public function lawyerByArea($type=''){  
        
        $lawyers = DB::table('lawyer_details')
        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.education_details','lawyer_details.language')
        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
        ->where('lawyer_details.is_adminVerified', 1)        
        ->where('users.user_type',2)
        ->where('lawyer_details.practice_area', 'LIKE', '%' . $type . '%')
        ->orderByDesc('lawyer_details.id')
        ->orderByDesc('lawyer_details.is_available')
        ->get();
        

        if(sizeof($lawyers)){
            $data=[];
            foreach($lawyers as $row){
                $checkavailabilityforlawyer=Availability::where('user_id',$row->user_id)->first();
                if(!empty($checkavailabilityforlawyer)){


                    $lawyerDetails=User::select('user_name','profile_img','city')->where('id',$row->user_id)->first();                
                    if(!empty($lawyerDetails)){
                        $practice_area=json_decode($row->practice_area);
                        $areadata=[]; 
                        if(count($practice_area)>0){
                            foreach($practice_area as $area){
                                $getarea=PracticeArea::select('name')->where('id',$area)->first();
                               
                                if(!empty($getarea)){
                                    $area=$getarea->name;
                                    $areadata[]=$area;
                                }
                            }
                        }
                        $practice_state=json_decode($row->practice_state);
                        $state=[]; 
                        foreach($practice_state as $area){
                            $getarea=State::select('state_name')->where('id',$area)->first();
                           
                            if(!empty($getarea)){
                                $area=$getarea->state_name;
                                $state[]=$area;
                            }
                        }
                        $languages=json_decode($row->language); 
                        $language_name=[]; 
                        foreach($languages as $language){
                            $getlanguage=Language::select('language_name')->where('id',$language)->first();
                            if(!empty($getlanguage)){
                                $language=$getlanguage->language_name;
                                $language_name[]=$language;
                            }
                            
                        }
                    
                        $user_name=$lawyerDetails->user_name;
                        $city=$lawyerDetails->city;
                        $language1=implode(',',$language_name);
                        $language=$language1;
                        $area=$areadata;
                        $profile_img=$lawyerDetails->profile_img?imgUrl.'profile/'.$lawyerDetails->profile_img:user_img;
                    }
                    $user=Auth::user();
                    if(!empty($user)){
                        $fav=Favorite::where('status','1')->where('lawyer_id',$row->user_id)->where('user_id',$user->id)->first();                   
                        if(!empty($fav)){
                            $row->is_favorite=1;
                        }else{
                            $row->is_favorite=0;
                        }
                        $utcTime = Carbon::now()->format('Y-m-d H:i:s');

                        $indiaTime = Carbon::createFromFormat('Y-m-d H:i:s', $utcTime, 'UTC')->setTimezone('Asia/Kolkata');
                        $today = Carbon::now();
                        $todaytime = Carbon::parse($indiaTime)->format('H:i:s');
                        
                        $dayOfWeek = $today->dayOfWeek;
                        $checklawyer=Availability::where(['user_id'=>$row->user_id,'day_id'=>$dayOfWeek,'status'=>'1'])->first();
                        if(!empty($checklawyer)){
                            $gettime=AvailabilityTime::where('availability_id',$checklawyer->id)->where('status','1')->get();
                            if(sizeof($gettime)){
                                foreach ($gettime as $value) {
                                    $fromtimearray=explode(":", $value->from);
                                    $totimearray=explode(":", $value->to);
                                    $mytimeArray = explode(":", $todaytime);                                
                                    $toTime = Carbon::createFromTime($totimearray[0], $totimearray[1], $totimearray[2]);
                                    $fromTime = Carbon::createFromTime($fromtimearray[0], $fromtimearray[1], $fromtimearray[2]);
                                    $myTime = Carbon::createFromTime($mytimeArray[0], $mytimeArray[1], $mytimeArray[2]);  
                                    if ($myTime->between($fromTime, $toTime)){
                                        $dataactive=1;
                                    }else{
                                        $dataactive=0;
                                    } 
                                }
                                $row->is_active=$dataactive;
                            }else{
                                $row->is_active=0;
                            }
                            
                        }else{
                            $row->is_active=0;
                        }  
                    }else{
                        $row->is_favorite=0;
                        $row->is_active=0;
                            
                    }

                    $checkagora=AgoraTokenLawyer::where('user_id',$row->user_id)->first();
                    if(!empty($checkagora)){
                        $row->agoraToken=$checkagora->token;
                    }else{
                        $row->agoraToken="";

                    }
                    $getreview=Review::where('lawyer_id',$row->user_id)->count('id');
                    $rating=Review::where('lawyer_id',$row->user_id)->avg('rating');
                    // $row->is_active=0;
                    $row->id=$row->user_id;
                    $row->user_name=$user_name;
                    $row->profile_img=$profile_img;
                    $row->is_call=$row->is_call;
                    $row->is_chat=$row->is_chat;
                    $row->call_charge=$row->call_charge;
                    $row->chat_charge=$row->chat_charge;
                    $row->experience=$row->experience;
                    $row->education_details=$row->education_details?$row->education_details:null;
                    $row->language=$language;
                    // $row->practice_area=$area;
                    $row->practice_area=implode(',', $area);
                    $row->practice_state=$state;
                    $row->rating=round($rating,2);
                    $row->total_review=$getreview;                
                    $data[]=$row;
                    unset($row->user_id);      
                }else{
                    $data=[];
                }          
                
            }
            
            return response()->json(["status" => 200,"success"=>true,"message"=>"Lawyer list by area",'data'=>$data]); 

        }else{
            return response()->json(["status" => 200,"success"=>ture,"message"=>"Data not found"]); 
        }
    }

    public function lawyerDetails($id=''){
        
        if($id!=''){
            $user=User::where(['id'=>$id,'is_verified'=>'1','user_type'=>'2'])->first();
        }else{
            return response()->json(['status' => 400,"success" => false,'message'=> "User not found"]);
        }
        if(!empty($user)){ 
            $userInfo['id'] = $user->id;
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
                    // $practice=[];
                    foreach ($getarea as $value) {
                        array_push($practice,$value->name);
                    }
                }
                $getlanguage=Language::select('language_name')->whereIn('id',json_decode($getLawyer->language))->get();
                $language=[];
                if(count($getlanguage)){
                    // $practice=[];
                    foreach ($getlanguage as $value) {
                        array_push($language,$value->language_name);
                    }
                }
                $getstate=State::select('state_name')->whereIn('id',json_decode($getLawyer->practice_state))->get();

                $state=[];
                if(count($getstate)){
                    // $practice=[];
                    foreach ($getstate as $value) {
                        array_push($state,$value->state_name);
                    }
                }
                // return $state;
                $users=Auth::user();
                $userInfo['is_active']=0;
                if(!empty($users)){                
                    $fav=Favorite::where('status','1')->where('lawyer_id',$id)->where('user_id',$users->id)->first();
                   
                    if(!empty($fav)){
                        $userInfo['is_favorite']=1;
                    }else{
                        $userInfo['is_favorite']=0;
                    }
                    $utcTime = Carbon::now()->format('Y-m-d H:i:s');

                    $today = Carbon::now();
                    $indiaTime = Carbon::createFromFormat('Y-m-d H:i:s', $utcTime, 'UTC')->setTimezone('Asia/Kolkata');
                    $todaytime = Carbon::parse($indiaTime)->format('H:i:s');
                    
                    $dayOfWeek = $today->dayOfWeek;
                    $checklawyer=Availability::where(['user_id'=>$id,'day_id'=>$dayOfWeek,'status'=>'1'])->first();
                    if(!empty($checklawyer)){
                       
                        $gettime=AvailabilityTime::where('availability_id',$checklawyer->id)->where('status','1')->get();
                        if(sizeof($gettime)){
                            foreach ($gettime as $value) {
                                $fromtimearray=explode(":", $value->from);
                                $totimearray=explode(":", $value->to);

                                $mytimeArray = explode(":", $todaytime);
                                
                                $toTime = Carbon::createFromTime($totimearray[0], $totimearray[1], $totimearray[2]);
                                $fromTime = Carbon::createFromTime($fromtimearray[0], $fromtimearray[1], $fromtimearray[2]);

                                $myTime = Carbon::createFromTime($mytimeArray[0], $mytimeArray[1], $mytimeArray[2]);
                                
                                if ($myTime->between($fromTime, $toTime)){
                                    $dataactive=1;
                                }else{
                                    $dataactive=0;
                                } 
                            }
                            $userInfo['is_active']=$dataactive;
                        }
                        
                    }
                }else{
                     $userInfo['is_favorite']=0;
                     $userInfo['is_active']=0;
                }
                $checkagora=AgoraTokenLawyer::where('user_id',$id)->first();
                if(!empty($checkagora)){

                    $userInfo['agoraToken']=$checkagora->token;
                }else{
                    $userInfo['agoraToken']="";

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
                $userInfo['rating']=(string)round($rating,2);
                $userInfo['total_review']=$getreview;
                $res=$this->checkavailability($id);
                $userInfo['availability']=$res;
                
                
            }

            $users=Auth::user();
            if(!empty($users)){
                $check=ProfileViewed::where('lawyer_id',$id)->where('username',$users->user_name)->where('date',Carbon::now()->format('Y-m-d'))->first();
                if(empty($check)){
                    $profileView=new ProfileViewed();
                    $profileView->lawyer_id=$id;
                    $profileView->username=$users->user_name;
                    $profileView->date=Carbon::now()->format('Y-m-d');
                    $udatequery=$profileView->save();
                }

                
            }else{
                $check=ProfileViewed::where('lawyer_id',$id)->where('username',"Anonymous")->where('date',Carbon::now()->format('Y-m-d'))->first();
                if(empty($check)){
                    $profileView=new ProfileViewed();
                    $profileView->lawyer_id=$id;
                    $profileView->username="Anonymous";
                    $profileView->date=Carbon::now()->format('Y-m-d');
                    $udatequery=$profileView->save();
                }
                
            }
            
            return response()->json(['status' => 200,"success" => true,'message'=> "User Details",'data'=>$userInfo]);
        }else{
            return response()->json(['status' => 400,"success" => false,'message'=> "User not exist"]);
        }
    }
    

    public function getoffer($type=''){
        $getoffer=Offer::where('status','1')->get();
        if(sizeof($getoffer)){
            foreach ($getoffer as $row) {
                $row->image=$row->image?imgUrl.'offers/'.$row->image:default_img;
                $data[]=$row->image;
            }
            return response()->json(["status" => 200,"success"=>true,"message"=>"Offer's image",'data'=>(array)$data]);
        }else{

            return response()->json(["status" => 200,"success"=>true,"message"=>"Offer image",'data'=>(array)default_img]);
        }
    }
    
    public function checkavailability($id=''){          
            $today = Carbon::now();

            $dayOfWeek = $today->dayOfWeek;
            $currentDayName = Carbon::now()->format('l');
            
            $array=[];
            $array1=[]; 
            $array2=[]; 
            $avilability=Availability::select('id','day_id')->where(['user_id'=>$id])->get();
            if(sizeof($avilability)){                    
                foreach($avilability as $row){
                    $chekctime=AvailabilityTime::select('from','to')->where('availability_id', $row->id)->where('status','1')->get();
                    if(count($chekctime)){
                        $data=[];
                        foreach($chekctime as $val){
                            $val->from=Carbon::parse($val->from)->format('h:i A');
                            $val->to=Carbon::parse($val->to)->format('h:i A');
                            $data[]=$val;
                        }
                    }else{
                        $data=[];
                       
                    }  
                    if($row->day_id==1){
                        if($dayOfWeek==$row->day_id){
                            $row->day="Monday";
                        }else{
                            $row->day="Monday";
                        }


                    }elseif($row->day_id==2){
                        if($dayOfWeek==$row->day_id){
                            $row->day="Tuesday";
                        }else{
                            $row->day="Tuesday";
                        }

                    }elseif($row->day_id==3){
                        if($dayOfWeek==$row->day_id){
                            $row->day="Wednesday";
                        }else{
                            $row->day="Wednesday";
                        }
                        
                    }elseif($row->day_id==4){
                        if($dayOfWeek==$row->day_id){
                            $row->day="Thursday";
                        }else{
                            $row->day="Thursday";
                        }
                        
                    }elseif($row->day_id==5){
                        if($dayOfWeek==$row->day_id){
                            $row->day="Friday";
                        }else{
                            $row->day="Friday";
                        }
                        
                    }elseif($row->day_id==6){
                         if($dayOfWeek==$row->day_id){
                            $row->day="Saturday";
                        }else{
                            $row->day="Saturday";
                        }
                    }else{
                        if($dayOfWeek==$row->day_id){
                            $row->day="Sunday";
                        }else{
                            $row->day="Sunday";
                        }
                        
                    }                    
                                      
                    $row->time=$data;
                    $data1[]= $row;
                    unset($row->id);
                }                
                
               return $data1;
            }else{
                return $array;                    
            }
                        
        
    }    
      

    public function lawyerHome($type=''){
        $user=Auth::user();
        if($user->user_type==2){  
            // return $user;
            $year1=Carbon::now()->format("Y-m-d");
            $checkagora=AgoraTokenLawyer::where('user_id',$user->id)->first();          

      
            
            if(!empty($checkagora)){
                if($checkagora->date!=$year1){
                    $res=$this->createAgoraToken($user->user_name);    
                    if($res) {
                        $updatetokenquery=AgoraTokenLawyer::where('user_id',$user->id)->update([
                            "token"=>$res,
                            'date'=>$year1
                        ]);
                    }
                }
            }else{
                $res=$this->createAgoraToken($user->user_name);
                if($res){                    
                    $updateagora=new AgoraTokenLawyer();
                    $updateagora->user_id=$user->id;
                    $updateagora->channel=$user->user_name;
                    $updateagora->date=$year1;
                    $updateagora->token=$res;
                    $updatetokenquery=$updateagora->save();
                }


            }

            $lastday=Carbon::now()->endOfMonth()->format('Y-m-d');
         
            $year=Carbon::parse($year1)->format('Y');
            $month=Carbon::parse($year1)->format('m');
            $date = Carbon::create($year,$month, 1);
            $array=[];
            $firstDayOfMonth = Carbon::createFromDate($year,$month,1);
            if($type==1){
                $firstDayOfMonth = Carbon::create($year, $month, 1);
                $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();

                    $weeks = [];
                    $weekData = [];
                    for ($i = 1; $i <= 5; $i++) {
                        
                        $startOfWeek = $firstDayOfMonth->copy()->startOfWeek()->addWeek($i - 1);
                        $endOfWeek = $startOfWeek->copy()->endOfWeek();

                        $startOfWeek = $startOfWeek->format('Y-m-d');
                        $endOfWeek = $endOfWeek->format('Y-m-d');

                        $data = DB::table('orders')
                            ->whereBetween('date', [$startOfWeek, $endOfWeek])
                            ->where('lawyer_id',$user->id)
                            ->sum('total_amount');

                        
                        $weekData['title'] = $i;
                        $weekData['value'] = $data;
                        
                        $weeks[] = $weekData;
                    }

                $total_amount= DB::table('orders')
                ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
                ->where('lawyer_id',$user->id)
                ->sum('total_amount');

                // return $array;

                $chat_earning=DB::table('orders')
                ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
                ->where('lawyer_id',$user->id)
                ->where('call_type','0')
                ->sum('total_amount');

                $call_earning=DB::table('orders')
                ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
                ->where('lawyer_id',$user->id)
                ->where('call_type','1')
                ->sum('total_amount');
                $array['chart']=$weeks;
                $array['total_amount']=$total_amount;
                $array['total_amount_roundup']=ceil($total_amount / 1000) * 1000;
                // $roundedNumber = ceil($number / 1000) * 1000;
                $array['call_earning']=$call_earning;
                $array['chat_earning']=$chat_earning;
                $array['wallet_ballance']=$user->wallet_ballance;

                $getrating=Review::where('lawyer_id',$user->id)->avg('rating');
                $array['rating']=(string)round($getrating,2);
                $profileciewd=ProfileViewed::where('lawyer_id',$user->id)->whereBetween('date',[$firstDayOfMonth, $lastDayOfMonth])->count('id');
                $array['profile_views']=$profileciewd;
                
                // $array['profile_views']=25;
                $msg="Current month data";

            // $getdata=order::where()->get
            }else{
                // $endDate  = Carbon::now()->subDays(1);
                $endDate  = Carbon::now();
                // $startdate  = Carbon::now()->subDays(8);
                $lastDayOweek  = Carbon::now()->format('Y-m-d');
                $firstDayOfweek  = Carbon::now()->subDays(7)->format('Y-m-d');
                // return $lastDayOweek;
                $lastSevenDays = [];
                    for ($i = 0; $i < 7; $i++) {
                        $lastSevenDays[] = $endDate->copy();
                        $endDate->subDay();
                    }
                    
                    usort($lastSevenDays, function ($a, $b) {
                        return strtotime($a) - strtotime($b);
                    });
                    // return $lastSevenDays;  
                    foreach ($lastSevenDays as $day) {

                        $data = DB::table('orders')
                        ->where('date',Carbon::parse($day)->format('Y-m-d'))
                        ->where('lawyer_id',$user->id)
                       ->sum('total_amount');
                       // return $data;
                        $intdate=Carbon::parse($day->toDateString())->format('d');
                        $array1['title']=(int)$intdate;
                        $array1['value']=$data;
                        $weeks[]=$array1;
                    }
                $total_amount= DB::table('orders')
                ->whereBetween('date', [$firstDayOfweek, $lastDayOweek])
                ->where('lawyer_id',$user->id)
                ->sum('total_amount');
                // return $total_amount;

                 $chat_earning=DB::table('orders')
                ->whereBetween('date', [$firstDayOfweek, $lastDayOweek])
                ->where('lawyer_id',$user->id)
                ->where('call_type','0')
                ->sum('total_amount');

                $call_earning=DB::table('orders')
                ->whereBetween('date', [$firstDayOfweek, $lastDayOweek])
                ->where('lawyer_id',$user->id)
                ->where('call_type','1')
                ->sum('total_amount');
                $array['chart']=$weeks;

                $array['total_amount']=$total_amount;
                $array['total_amount_roundup']=ceil($total_amount / 1000) * 1000;
                $array['call_earning']=$call_earning;
                $array['chat_earning']=$chat_earning;
                $array['wallet_ballance']=$user->wallet_ballance;
                $getrating=Review::where('lawyer_id',$user->id)->avg('rating');
                $array['rating']=(string)round($getrating,2);
                
                $profileciewd=ProfileViewed::where('lawyer_id',$user->id)->whereBetween('date', [$firstDayOfweek, $lastDayOweek])->count('id');
                $array['profile_views']=$profileciewd;
                $msg="Last Week data";
            }

            return response()->json(["status" => 200,"success"=>true,"message"=>$msg,'data'=>$array]);
         
        }else{
            return response()->json(["status" => 400,"success"=>false,"message"=>"You're not auhtorized"]);
        }
    }

    public function createAgoraToken($username='')
    {

        $appID = '3e54c7302a7f46f6ad5563ef5d86912c';
        $appCertificate = '9e056244db914f4cabde73f49636449e';
        $expireTimeInSeconds = 7200*720;
        $currentTimestamp = now()->getTimestamp();
        // return $currentTimestamp;

        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;
        // return $privilegeExpiredTs;
        $accessToken = new AccessToken2($appID, $appCertificate,  $expireTimeInSeconds );
        $serviceChat = new ServiceChat($appID);
         // print_r($privilegeExpiredTs);die;

        $serviceChat->addPrivilege($serviceChat::PRIVILEGE_APP,  $privilegeExpiredTs);
        $accessToken->addService($serviceChat);
        $agoraToken  = $accessToken->build();
        $uid = null;
        $channelName =$username;
        // $role =1;
        $role = RtcTokenBuilder::RolePublisher;
          // echo 'Token with RTC, RTM, CHAT privileges: ' . $agoraToken . PHP_EOL; 

        $token = ChatTokenBuilder2::buildUserToken($appID, $appCertificate,  $uid,7200*720);
        $rtctoken = RtcTokenBuilder::buildTokenWithUid($appID,$appCertificate,$channelName,$uid,$role,$privilegeExpiredTs);
        $rtmtoken = RtmTokenBuilder::buildToken($appID,$appCertificate,$channelName,$uid,$role,$privilegeExpiredTs);
          // echo 'Token with RTC, RTM, CHAT privileges: ' . $agoraToken . PHP_EOL;

        // $token = AccessToken::init($appID, $appCertificate, $channelName, $uid,$privilegeExpiredTs);

 

        return $rtctoken;

    }
    public function checkLawyer($id='',$type=''){
        $user=Auth::user();
        if($user->user_type==1){

           $checkstatue=User::where('id',$id)->first();
            if($checkstatue->is_loggedin==1){
                $check=LawyerDetail::where('user_id',$id)->where('is_available','1')->first();
                if(!empty($check)){

                    // $year1=Carbon::now()->format("Y-m-d");
                    // $checkagora=AgoraTokenLawyer::where('user_id',$id)->first();
        
              
                    
                    // if(!empty($checkagora)){
                    //     if($checkagora->date!=$year1){                           
                    //        return response()->json(["status" => 400,"success"=>false,"message"=>"Lawyer is not Available"]);
                    //     }
                    // }

                    

                    
                        $orderRunning=Order::where('lawyer_id',$id)->where('total_amount',0)->where('end_by',0)->where('status','0')->first();
                        if(!empty($orderRunning))
                        {
                            return response()->json(["status" => 203,"success"=>true,"message"=>"Lawyer is busy on another Call/Chat"]);
                        }
                    
                        if($type=='1'){
                            if($check->is_call==1){
                                if($user->wallet_ballance >= $check->call_charge){
                                    return response()->json(["status" => 200,"success"=>true,"message"=>"Lawyer is Available to take call/chat"]);
                                }else{
                                    return response()->json(["status" => 202,"success"=>true,"message"=>"You've Insufficient Balance"]);
                                }
                            }else{
                                return response()->json(["status" => 203,"success"=>true,"message"=>"Lawyer is not available on call"]);
                            } 
                            
                        }else{  
                            if($check->is_chat==1){
                                if($user->wallet_ballance >= $check->call_charge*5){
                                    return response()->json(["status" => 200,"success"=>true,"message"=>"Lawyer is Available to take call/chat"]);
                                }else{
                                    return response()->json(["status" => 202,"success"=>true,"message"=>"You've Insufficient Balance"]);
                                }
                            }else{
                                return response()->json(["status" => 203,"success"=>true,"message"=>"Lawyer is not available on chat"]);
                            }                      
                        }
                    
                    
                }else{
                    return response()->json(["status" => 201,"success"=>true,"message"=>"lawyer is on another call/chat"]);
                }
            }else{
                return response()->json(["status" => 400,"success"=>false,"message"=>"Lawyer is not Available"]);
            }
        }else{
            return response()->json(["status" => 401,"success"=>false,"message"=>"You're not auhtorized"]);
        }
    }

    public function updateUserStatus(Request $request)
    {
       $user=Auth::user();       
       UserStatus::where('user_id',$user->id)->update(['date_time'=>date('Y-m-d H:i:s'),'auth_token'=>$request->auth_token]);                    
       return response()->json(["status" => 200,"success"=>true,"message"=>"User status send Successfully"]);
    }

    public function getUserStatus()
    {
    //    $user=Auth::user();
       $current_time = time(); // Get the current timestamp
       $user_statuses=UserStatus::all();

       foreach($user_statuses as $user_status)
       {
        $user_status_time = strtotime($user_status->date_time); 
        $time_difference = $current_time - $user_status_time;

        $chat_start_time = strtotime($user_status->created_at); 

        $duration=$current_time-$chat_start_time;

       
        if ($time_difference > 38) 
            {
                if($duration >300)
                {
                    $duration=300;
                }

                                
                $url = url('/').'/api/updateChat';
            
                $data = array(
                    'chat_id' => $user_status->chat_id,
                    'duration' => $duration,
                );

                // // Convert data array to JSON
                $json_data = json_encode($data);

                $headers = array(
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$user_status->auth_token,
                );

                $ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $response = curl_exec($ch);

                // Check for cURL errors
                if (curl_errno($ch)) {
                    echo 'cURL error: ' . curl_error($ch);
                }
                UserStatus::where('user_id',$user_status->user_id)->where('chat_id',$user_status->chat_id )->delete();
        
                echo $response;
            }
                

              



        // curl_close($ch);

        // // Handle the response
       


       }

  
 return date('Y-m-d H:i:s');

    }

    public function userPermanentDelete()
    {
        $user=Auth::user();

        if(!empty($user))
        {

            User::where('id',$user->id)->update(['is_delete'=>1]);

            if($user->user_type=='2')
            { 

                try {
                    // Fetch availables (assuming 'Availability' model and its relationship with 'AvailabilityTime' is properly defined)
                    $availables = Availability::where('user_id', $user->id)->get();
                
                    // Loop through each availability
                    foreach ($availables as $avail) {
                        // Delete associated availability times
                        $avail->availabilityTimes()->delete();
                    }
                
                    // Delete availabilities
                    Availability::where('user_id', $user->id)->delete();


                    LawyerDetail::where('user_id',$user->id)->update(['is_chat'=>'0','is_call'=>'0']);

                } catch (\Exception $e) {
                    // Log or display the error message
                    dd($e->getMessage());
                }
            
                             
                
            }
            return response()->json(["status" => 200,"success"=>true,"message"=>"User delete Successfully"]);


        }else{
           return response()->json(["status" => 400,"success"=>false,"message"=>"User must Login"]);


        }

    }

    


}



