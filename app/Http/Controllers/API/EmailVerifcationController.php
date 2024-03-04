<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use App\Notifications\emailVerificationRequest;
use App\Notifications\emailVerificationSucess;
use App\Models\User;
use App\Models\Review;
use App\Models\lawyerDetail;
use App\Models\PracticeArea;
use App\Models\State;
use App\Models\Availability;
use App\Models\AvailabilityTime;
use App\Models\Language;
use App\EmailVerification;
use Illuminate\Support\Str;
use Validator;
use DB;
use App\Models\AgoraTokenLawyer;

use App\Classes\AgoraDynamicKey\RtcTokenBuilder;
use App\Classes\AgoraDynamicKey\RtmTokenBuilder;
use App\Classes\AgoraDynamicKey\ChatTokenBuilder2;
use App\Classes\AgoraDynamicKey\AccessToken2;
use App\Classes\AgoraDynamicKey\AccessToken;
use App\Classes\AgoraDynamicKey\ServiceChat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
class EmailVerifcationController extends ApiController
{
    /*we are using this create function from user regestration API so commented here and copy same code in usercontroller (which is running without auth) but if we need to send this again after auth we can use belove function (create)*/
    public function resendOTP(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, [
            'email' => 'required|string|email',
        ]);
        if ($validator->fails()) {
            $status['error'] = $validator->errors();
            return response()->json($status, JsonResponse::HTTP_BAD_REQUEST);
        }

        $where = [['email', '=', $data['email']], ['status', '!=', 3]];
        $user = User::where($where)->first();
        // print_r($user); die();
        if (!$user) {
            return response()->json(['error' => trans('emailNotifications.email_verification_error_402')], JsonResponse::HTTP_BAD_REQUEST);
        }
        $token = mt_rand(1000, 9999);
        $verify = EmailVerification::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => $token,
            ]
        );
        if ($user && $verify) {
            $user->notify(new emailVerificationRequest($verify->token));
        }
        // $success['token'] =  $user->createToken('MyApp')-> accessToken; 
        // $success['name'] =  $user->name;
        header('authtoken:' . $user->createToken('MyApp')->accessToken);
        return response()->json(["message"=>trans('emailNotifications.success_message_email_verification'),"OTP"=>$token],JsonResponse::HTTP_OK); 
    }
    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {
        $verify = EmailVerification::where('token', $token)->first();
        if (!$verify) {
            return response()->json(['status' => '400', 'message' => 'This verification token is invalid.']);
        }
        if (
            Carbon::parse($verify->updated_at)
                ->addMinutes(1440)
                ->isPast()
        ) {
            $verify->delete();
            return response()->json(['status' => '400', 'message' => 'This verification token is invalid.']);
        }
        return response()->json($verify);
    }
    /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function confirm(Request $request)
    {
        // print_r($request->all()); die();
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            // 'veryfication' => 'required',
            // 'token' => 'required|string',
        ]);
        if ($validator->fails()) {
            // return response()->json(['message' => 'All fields with star are mandatory, please check and fill!!', 'error' => $validator->errors(), 'status' => 400]);
            // return redirect()->back()
            //           ->withErrors($validator)
            //           ->withInput();
            $message = '400';
            return redirect('/Verify_email/' . $request->token . '/' . base64_encode($request->current_lang) . '/' . base64_encode($request->email) . '/' . $message);
        }
        $verify = EmailVerification::where([['token', $request->token], ['email', $request->email]])->first();
        if (!$verify) {
            // return response()->json(
            //     [
            //         'message' => 'This password reset token or email is invalid.',
            //     ],
            //     404
            // );
            $message = '401';
            return redirect('/Verify_email/' . $request->token . '/' . base64_encode($request->current_lang) . '/' . base64_encode($request->email) . '/' . $message);
        }
        $where = [['email', '=', $verify->email], ['status', '!=', 3]];
        $user = User::where($where)->first();
        if (!$user) {
            // return response()->json(
            //     [
            //         'message' => "We can't find a user with that e-mail address.",
            //     ],
            //     404
            // );
            $message = '402';
            return redirect('/Verify_email/' . $request->token . '/' . base64_encode($request->current_lang) . '/' . base64_encode($request->email) . '/' . $message);
        }
        // $user->original_password = $request->password;
        $user->is_email_verified = $request->veryfication ? $request->veryfication : 1;
        $user->email_verified_at = strtotime(now());
        $user->save();
        $verify->delete();
        $user->notify(new emailVerificationSucess($verify));
        // $res = ['status'=>"200",'message'=>'Your password reset successfully! please click on login button for login page'];
        // return response()->json($res);
        // return response()->json($user);
        $message = '403';
        return redirect('/Verify_email/' . $request->token . '/' . base64_encode($request->current_lang) . '/' . base64_encode($request->email) . '/' . $message);
    }


    public function confirmWithOTP(Request $request)
    {
       if ($request->is_email == "1") {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }
        $verify = EmailVerification::where([['token', $request->otp], ['email', $request->email]])->first();
        if (!$verify) {
            return response()->json(['error' => trans('emailNotifications.email_verification_error_401')], JsonResponse::HTTP_BAD_REQUEST);
        }
        $where = [['email', '=', $verify->email], ['status', '!=', 3]];
        $user = User::where($where)->first();
        if (!$user) {
            return response()->json(['error' => trans('emailNotifications.email_verification_error_402')], JsonResponse::HTTP_BAD_REQUEST);
        }
        // $user->original_password = $request->password;
        // $user->is_email_verified = $request->veryfication ? $request->veryfication : 1;
        $user->is_email_verified = 1;
        $user->email_verified_at = strtotime(now());
        $user->save();
        $verify->delete();
        $user->notify(new emailVerificationSucess($verify));

        $user->authtoken = $user->createToken('MyApp')->accessToken;
        $user->profile = ($user->profile)?profile_url.$user->profile:profile_url.'defaultUser.png';
        $user->is_email_verified = ($user->is_email_verified)?strval($user->is_email_verified):'';
        return response()->json(['message' => trans('emailNotifications.email_verification_error_403'),"record"=>$user], JsonResponse::HTTP_OK);
        } 
        elseif ($request->is_email == "0") {
        // if registered with phone
            $validator = Validator::make($request->all(), [
            'email' => 'required|numeric',
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }
        $verify = EmailVerification::where([['token', $request->otp], ['phone', $request->email]])->first();
        if (!$verify) {
            return response()->json(['error' => trans('emailNotifications.email_verification_error_401')], JsonResponse::HTTP_BAD_REQUEST);
        }
        $where = [['phone', '=', $verify->phone], ['status', '!=', 3]];
        $user = User::where($where)->first();
        if (!$user) {
            return response()->json(['error' => trans('emailNotifications.phone_verification_error_402')], JsonResponse::HTTP_BAD_REQUEST);
        }
        // $user->original_password = $request->password;
        $user->is_phone_verified = 1;
        $user->phone_verified_at = strtotime(now());
        $user->save();
        $verify->delete();
        
         //getting token for sms otp form token api
        $authTokenSms = $this->token();

        $url = send_otp;
        // print_r($url); die();
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = ["Content-Type: application/x-www-form-urlencoded","Authorization: Bearer ".$authTokenSms];
        // print_r($headers); die();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = "mobile_phone=".$user->phone."&message= Hi, ".trans('emailNotifications.phone_verification_success')."&from=4546";

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);

        $user->authtoken = $user->createToken('MyApp')->accessToken;
        $user->profile = ($user->profile)?profile_url.$user->profile:profile_url.'defaultUser.png';
        $user->is_email_verified = ($user->is_email_verified)?strval($user->is_email_verified):'';
        return response()->json(['message' => trans('emailNotifications.email_verification_error_403'),"record"=>$user], JsonResponse::HTTP_OK);
        }
    }

        // return response()->json($res);

    // change email section sending OTP on new email and save it as temoporary email until verified

    public function changeEmailOTP(Request $request)
    {
        $loginUser = Auth::user();
         $data = json_decode($request->getContent(), true);
        // print_r($data['email']); die();
        $where = [['email', '=', $data['email']], ['status', '!=', 3]];
        $checkDeleted = User::select('id')
            ->where($where)
            ->get();
        
        // print_r($checkDeleted); die();
        $input = [];
        $input = $data;

     if (count($checkDeleted) > 0) {

        $validator = Validator::make($data,[ 
            'email' => 'required|email|unique:users,email', 
        ]);
    }
    else
    {
      $validator = Validator::make($data,[ 
            'email' => 'required|email', 
        ]);  
    }
        if ($validator->fails()) {
            $status['error'] = $validator->errors();
            return response()->json($status, JsonResponse::HTTP_BAD_REQUEST);
        }
        $where = [['email', '=', $loginUser->email], ['status', '!=', 3]];
        $user = User::where($where)->first();
       
        if (!$user) {
            return response()->json(['error' => trans('emailNotifications.email_verification_error_402')], JsonResponse::HTTP_BAD_REQUEST);
        }
        $updateEmail['temporary_email'] = $data['email'];
        $insert = User::where('id',$user->id)->update($updateEmail);
        // die();
        if($insert)
        {

        $token = mt_rand(1000, 9999);
        $verify = EmailVerification::updateOrCreate(
            ['email' => $user->temporary_email],
            [
                'email' => $user->temporary_email,
                'token' => $token,
            ]
        );
        if ($user && $verify) {
            $user->notify(new emailVerificationRequest($verify->token));
        }
        // $success['token'] =  $user->createToken('MyApp')-> accessToken; 
        // $success['name'] =  $user->name;
        header('authtoken:' . $user->createToken('MyApp')->accessToken);
        return response()->json(["message"=>trans('emailNotifications.success_message_change_email_verification'),"OTP"=>$token],JsonResponse::HTTP_OK); 
        }
         else
        {
            return response()->json(["error" => trans('customMessages.something_wrong')], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    // confirm new email

    public function confirmNewEmailWithOTP(Request $request)
    {
        // print_r($request->all()); die();
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }
        $verify = EmailVerification::where([['token', $request->otp], ['email', $request->email]])->first();
        if (!$verify) {
            return response()->json(['error' => trans('emailNotifications.email_verification_error_401')], JsonResponse::HTTP_BAD_REQUEST);
        }
        $where = [['temporary_email', '=', $verify->email], ['status', '!=', 3]];
        $user = User::where($where)->first();
        if (!$user) {
            return response()->json(['error' => trans('emailNotifications.email_verification_error_402')], JsonResponse::HTTP_BAD_REQUEST);
        }
        // $user->original_password = $request->password;
        $user->is_email_verified = 1;
        $user->email = $request->email;
        $user->temporary_email = '';
        $user->email_verified_at = strtotime(now());
        $user->save();
        $verify->delete();
        $user->notify(new emailVerificationSucess($verify));

        $user->authtoken = $user->createToken('MyApp')->accessToken;
        $user->profile = ($user->profile)?profile_url.$user->profile:profile_url.'defaultUser.png';
        $user->is_email_verified = ($user->is_email_verified)?strval($user->is_email_verified):'';
        return response()->json(['message' => trans('emailNotifications.email_verification_error_403'),"record"=>$user], JsonResponse::HTTP_OK);
        // return response()->json($res);
    }


    // curl function to get sms otp

    public function token()
    {
        $url = sms_url;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = ["Content-Type: application/x-www-form-urlencoded"];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = "email=" . sms_email . "&password=" . sms_pass;

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $decodedData = json_decode($resp);
        $token = $decodedData->data->token;
        return $token;
    }



    public function lawyersByFilter(Request $request){
        // return "hello"; // sort_by 1-relevence2-experinece high to low,3-price low to high,4-price high to low,5-popularity high to low,6-rating high to low,
        // practice area
        // price range 1- 10-50,2-50-100,3-100-200,4-200-500.
        // price range 1- 10-50,2-50-100,3-100-200,4-200-500.

        

        if (empty(array_diff(['1', '2', '3', '4'], $request->price))) {
            $min = "10";
            $max = "500";
            $min1="0";            
            $max1="0";
        }elseif(empty(array_diff(['1','2','3'],$request->price))){            
            $min="10";
            $max="200";
        }elseif(empty(array_diff(['1','2','4'],$request->price))){
            $min="10";
            $max="100";
            $min1="200";
            $max1="500";
            
        }elseif(empty(array_diff(['1','3','4'],$request->price))){
            $min="10";
            $max="50";
            $min1="100";
            $max1="500";
            
        }elseif(empty(array_diff(['2','3','4'],$request->price))){
            $min="50";            
            $max="500";
            $min1="0";            
            $max1="0";
            
        }elseif(empty(array_diff(['1','2'],$request->price))){
             $min="10";
             $max="100";
             $min1="0";            
             $max1="0";
            
        }elseif(empty(array_diff(['1','3'],$request->price))){
            $min="10";
            $max="50";
            $min1="100";
            $max1="200";
            
        }elseif(empty(array_diff(['1','4',],$request->price))){
            $min="10";
            $max="50";
            $min1="200";
            $max1="500";
            
        }elseif(empty(array_diff(['2','3'],$request->price))){
            $min="50";
            $max="200";
            $min1="0";
            $max1="0";
            
        }elseif(empty(array_diff(['2','4'],$request->price))){
            $min="50";
            $max="100";
            $min1="200";
            $max1="500";
            
        }elseif(empty(array_diff(['3','4'],$request->price))){
            $min="100";
            $max="500";
            $min1="0";
            $max1="0";
            
        }elseif(empty(array_diff(['1'],$request->price))){
            $min="10";
            $max="50";
            $min1="0";
            $max1="0";
        }elseif(empty(array_diff(['2'],$request->price))){
            $min="50";
            $max="100";
            $min1="0";
            $max1="0";
            
        }elseif(empty(array_diff(['3'],$request->price))){
            $min="100";
            $max="200";
            $min1="0";
            $max1="0";
            
        }elseif(empty(array_diff(['4'],$request->price))){
            $min="200";
            $max="500";
            $min1="0";
            $max1="0";
        }else{
            $min = "0";
            $max = "0";
            $min1 = "0";            
            $max1 = "0";
        }


        if (empty(array_diff(['1', '2', '3', '4'], $request->experience))) {
            $minexp = "0";
            $maxexp= "99";
            $min1exp="0";            
            $max1exp="0";
        }elseif(empty(array_diff(['1','2','3'],$request->experience))){            
            $minexp="0";
            $maxexp="20";
        }elseif(empty(array_diff(['1','2','4'],$request->experience))){
            $minexp="0";
            $maxexp="10";
            $min1exp="20";
            $max1exp="99";
            
        }elseif(empty(array_diff(['1','3','4'],$request->experience))){
            $minexp="0";
            $maxexp="5";
            $min1exp="10";
            $max1exp="99";
            
        }elseif(empty(array_diff(['2','3','4'],$request->experience))){
            $minexp="5";            
            $maxexp="99";
            $min1exp="0";            
            $max1exp="0";
            
        }elseif(empty(array_diff(['1','2'],$request->experience))){
             $minexp="0";
             $maxexp="10";
             $min1exp="0";            
             $max1exp="0";
            
        }elseif(empty(array_diff(['1','3'],$request->experience))){
            $minexp="0";
            $maxexp="5";
            $min1exp="10";
            $max1exp="20";
            
        }elseif(empty(array_diff(['1','4',],$request->experience))){
            $minexp="0";
            $maxexp="5";
            $min1exp="20";
            $max1exp="99";
            
        }elseif(empty(array_diff(['2','3'],$request->experience))){
            $minexp="5";
            $maxexp="20";
            $min1exp="0";
            $max1exp="0";
            
        }elseif(empty(array_diff(['2','4'],$request->experience))){
            $minexp="5";
            $maxexp="10";
            $min1exp="20";
            $max1exp="99";
            
        }elseif(empty(array_diff(['3','4'],$request->experience))){
            $minexp="10";
            $maxexp="99";
            $min1exp="0";
            $max1exp="0";
            
        }elseif(empty(array_diff(['1'],$request->experience))){
            $minexp="0";
            $maxexp="5";
            $min1exp="0";
            $max1exp="0";
        }elseif(empty(array_diff(['2'],$request->experience))){
            $minexp="5";
            $maxexp="10";
            $min1exp="0";
            $max1exp="0";
            
        }elseif(empty(array_diff(['3'],$request->experience))){
            $minexp="10";
            $maxexp="20";
            $min1exp="0";
            $max1exp="0";
            
        }elseif(empty(array_diff(['4'],$request->experience))){
            $minexp="20";
            $maxexp="99";
            $min1exp="0";
            $max1exp="0";
        }else{
            $minexp= "0";
            $maxexp= "0";
            $min1exp= "0";            
            $max1exp= "0";
        }
        $getprcaticeArea=$request->practice_area;
        $getstates=$request->practice_state;
        $getlanguage=$request->language;
        if($request->sort_by!="0"){
            if($request->sort_by==1){
                if(count($request->price)>0){ 
                    $lawyers = DB::table('lawyer_details')
                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                    ->where('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)
                    ->whereBetween('lawyer_details.call_charge',[$min,$max])                    
                    ->orWhere('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)
                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                    ->get();
                    
                    
                }else{
                    $lawyers = DB::table('lawyer_details')
                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                        ->where('lawyer_details.is_adminVerified', 1)
                        ->where('users.user_type', 2)           
                        ->get();
                    
                }
            }elseif($request->sort_by==2){
                if(count($request->price)>0){
                    $lawyers = DB::table('lawyer_details')
                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                    ->where('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)
                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                    ->orWhere('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)
                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                    ->orderByAsc('call_charge')
                    ->get();
                }else{
                    $lawyers = DB::table('lawyer_details')
                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                    ->where('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)  
                    ->orderByAsc('call_charge')              
                    ->get();
                }
            }elseif($request->sort_by==3){
                if(count($request->price)>0){
                    $lawyers = DB::table('lawyer_details')
                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                    ->where('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)
                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                    ->orWhere('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)
                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                    ->orderByDesc('call_charge')  
                    ->get();
                }else{
                    $lawyers = DB::table('lawyer_details')
                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                    ->where('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)    
                    ->orderByDesc('call_charge')              
                    ->get();
                }
            }elseif($request->sort_by==4){
                if(count($request->price)>0){                    
                    $lawyers = DB::table('lawyer_details')
                            ->select('lawyer_details.id','lawyer_details.user_id','lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge',
                                'lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                            ->join('users', 'users.id', '=', 'lawyer_details.user_id')                            
                            ->where('lawyer_details.is_adminVerified', 1)
                            ->where('users.user_type',2)
                            ->whereBetween('lawyer_details.call_charge',[$min,$max])
                            ->orWhere('lawyer_details.is_adminVerified', 1)                            
                            ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                            ->get();
                    
                            
                }else{
                    $lawyers = DB::table('lawyer_details')
                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                    ->where('lawyer_details.is_adminVerified', 1)           
                    ->where('users.user_type',2)          
                    ->get();
                }
            }else{
                if(count($request->price)>0){
                    $lawyers = DB::table('lawyer_details')
                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                    ->where('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)
                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                    ->orWhere('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)
                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                    ->get();
                }else{
                    $lawyers = DB::table('lawyer_details')
                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                    ->where('lawyer_details.is_adminVerified', 1)
                    ->where('users.user_type', 2)                
                    ->get();
                }
            }
            
        }else{            
            if(count($request->price)>0){
                if(count($request->practice_area)>0){  
                    if(count($request->practice_state)>0){
                        if(count($request->language)>0){
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])

                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])

                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])

                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->get();

                                }else{
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                                }
                                        })

                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                                }
                                        })
                                        ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                                }
                                        })
                                    ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }
                            }
                        }else{
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])                                    
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])                                    
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])                                    
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                     ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getstates) {
                                        foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }
                            }
                        }
                    }else{
                        if(count($request->language)>0){
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                     ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }else{                                    
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }
                            }else{    
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                     ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                     ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }                           
                            }
                        }else{  
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }
                            }else{ 
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }else{
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    

                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getprcaticeArea) {
                                        foreach ($getprcaticeArea as $value) {
                                            $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                    })
                                    ->get();
                                }                            
                            }                          
                        }
                    }
                }else{  
                    if(count($request->practice_state)>0){
                        if(count($request->language)>0){
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->get();
                                }else{                                    
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->get();
                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->get();
                                }else{                                    
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->get();
                                }
                            }
                        }else{
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where('users.gender',$request->gender)
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->where('users.gender',$request->gender)
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->get(); 
                                }
                            }else{
                                if(count($request->experience)>0){
                                     $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->get(); 
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $value) {
                                            $query->orWhere('lawyer_details.practice_state', 'like', '%' . $value . '%');
                                            }
                                         })
                                        ->get(); 
                                }
                            }
                        }
                    }else{
                        if(count($request->language)>0){
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                     ->where(function ($query) use ($getlanguage) {
                                    foreach ($getlanguage as $value) {
                                        $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getlanguage) {
                                    foreach ($getlanguage as $value) {
                                        $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->where(function ($query) use ($getlanguage) {
                                    foreach ($getlanguage as $value) {
                                        $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->where(function ($query) use ($getlanguage) {
                                    foreach ($getlanguage as $value) {
                                        $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })
                                    ->get();
                                }else{
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)

                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                     ->where(function ($query) use ($getlanguage) {
                                    foreach ($getlanguage as $value) {
                                        $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->where(function ($query) use ($getlanguage) {
                                    foreach ($getlanguage as $value) {
                                        $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })
                                    ->get();

                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                         ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                         ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                         ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                         ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->get(); 
                                }else{                                    
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                         ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                         ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                        ->get(); 
                                }
                            }
                        }else{
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                    ->get(); 
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                    ->get(); 
                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                         ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                         ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                        ->get(); 
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min,$max])
                                        ->orWhere('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->whereBetween('lawyer_details.call_charge',[$min1,$max1])
                                        ->get(); 
                                }
                            }

                        }
                    }                
                }
                
            }else{                                   
                if(count($request->practice_area)>0){                    
                    if(count($request->practice_state)>0){
                        if(count($request->language)>0){
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)      
                                    ->where('users.gender', $request->gender)      
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])        

                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $lang) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $lang . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)      
                                    ->where('users.gender', $request->gender)      
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])        

                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $lang) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $lang . '%');
                                            }
                                        })
                                    ->get();
                                
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2) 
                                    ->where('users.gender', $request->gender)    
                                    // ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                                 
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $lang) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $lang . '%');
                                            }
                                        })
                                    ->get();
                                }

                            }else{
                                if(count($request->experience)>0){
                                
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])  
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $lang) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $lang . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])  
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $lang) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $lang . '%');
                                            }
                                        })
                                    ->get();

                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $lang) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $lang . '%');
                                            }
                                        })
                                    ->get();
                                }
                            }
                        }else{
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])  
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])  
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->get();     

                                }else{
                                    if(count($request->experience)>0){

                                        $lawyers = DB::table('lawyer_details')
                                                ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])  
                                        ->where(function ($query) use ($getprcaticeArea) {
                                                foreach ($getprcaticeArea as $value) {
                                                    $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                            })
                                        ->where(function ($query) use ($getstates) {
                                                foreach ($getstates as $states) {
                                                    $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                                }
                                            })
                                        ->orWhere('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])  
                                        ->where(function ($query) use ($getprcaticeArea) {
                                                foreach ($getprcaticeArea as $value) {
                                                    $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                            })
                                        ->where(function ($query) use ($getstates) {
                                                foreach ($getstates as $states) {
                                                    $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                                }
                                            })
                                        ->get();
                                    }else{
                                        $lawyers = DB::table('lawyer_details')
                                                ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                        ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                        ->where('lawyer_details.is_adminVerified', 1)
                                        ->where('users.user_type', 2)
                                        ->where('users.gender',$request->gender)
                                        ->where(function ($query) use ($getprcaticeArea) {
                                                foreach ($getprcaticeArea as $value) {
                                                    $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                            })
                                        ->where(function ($query) use ($getstates) {
                                                foreach ($getstates as $states) {
                                                    $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                                }
                                            })
                                        ->get();

                                    }
                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)        
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                          
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)        
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])                          
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->get();                                
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)                                
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->get();  
                                }
                            }

                        }
                    }else{
                        if(count($request->language)>0){
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])  
                                    ->where('users.gender',$request->gender)
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                     ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])  
                                    ->where('users.gender',$request->gender)
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                     ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                   
                                    ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                     ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                     ->get();
                                   
                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                                  
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                     ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                                  
                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                     ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                   
                                    ->get();
                                }else{                                    
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)

                                    ->where(function ($query) use ($getprcaticeArea) {
                                            foreach ($getprcaticeArea as $value) {
                                                $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                            }
                                        })
                                     ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                   
                                    ->get();
                                }
                            }
                        }else{
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                            ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                            ->where('lawyer_details.is_adminVerified', 1)
                                            ->where('users.user_type', 2) 
                                            ->where('users.gender',$request->gender)                           
                                            ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                           
                                            ->where(function ($query) use ($getprcaticeArea) {
                                                foreach ($getprcaticeArea as $value) {
                                                    $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                            })
                                            ->orWshere('lawyer_details.is_adminVerified', 1)
                                            ->where('users.user_type', 2) 
                                            ->where('users.gender',$request->gender)                           
                                            ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])                           
                                            ->where(function ($query) use ($getprcaticeArea) {
                                                foreach ($getprcaticeArea as $value) {
                                                    $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                            })
                                   
                                        ->get();
                                }else{
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                            ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                            ->where('lawyer_details.is_adminVerified', 1)
                                            ->where('users.user_type', 2) 
                                            ->where('users.gender',$request->gender)                           
                                            ->where(function ($query) use ($getprcaticeArea) {
                                                foreach ($getprcaticeArea as $value) {
                                                    $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                }
                                            })
                                   
                                    ->get();                                    
                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                                ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                                ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                                ->where('lawyer_details.is_adminVerified', 1)
                                                ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])
                                                ->where('users.user_type', 2)                            
                                                ->where(function ($query) use ($getprcaticeArea) {
                                                    foreach ($getprcaticeArea as $value) {
                                                    $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                    }
                                                })  
                                                ->orWhere('lawyer_details.is_adminVerified', 1)
                                                ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])
                                                ->where('users.user_type', 2)                            
                                                ->where(function ($query) use ($getprcaticeArea) {
                                                    foreach ($getprcaticeArea as $value) {
                                                    $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                    }
                                                })                                       
                                               ->get();
                                }else{
                                    $lawyers = DB::table('lawyer_details')
                                            ->select('lawyer_details.user_id', 'lawyer_details.is_call','lawyer_details.is_chat','lawyer_details.call_charge','lawyer_details.chat_charge','lawyer_details.practice_area','lawyer_details.practice_state','lawyer_details.experience','lawyer_details.language')
                                            ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                            ->where('lawyer_details.is_adminVerified', 1)
                                            ->where('users.user_type', 2)                            
                                            ->where(function ($query) use ($getprcaticeArea) {
                                                    foreach ($getprcaticeArea as $value) {
                                                        $query->orWhere('lawyer_details.practice_area', 'like', '%' . $value . '%');
                                                    }
                                                })
                                           
                                            ->get();
                                }

                            }

                        }
                    }
                    
                }else{
                    if(count($request->practice_state)>0){
                        if(count($request->language)>0){ 
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                            
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])                            
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->get();
                                }else{
                                    $lawyers = DB::table('lawyer_details')
                                        ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)                            
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->get();
                                }
                            }else{ 
                                if(count($request->experience)>0) {
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                                                             
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])                                                             
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)                                                            
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->get();
                                }                          
                            }                          
                        }else{  
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                                 
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])                                 
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->get();
                                }else{
                                    $lawyers = DB::table('lawyer_details')                                    
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)                                                                
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })                                    
                                    ->get();
                                }
                                
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp])                                 
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])                                 
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->get();
                                }else{                                    
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)                                
                                    ->where(function ($query) use ($getstates) {
                                            foreach ($getstates as $states) {
                                                $query->orWhere('lawyer_details.practice_state', 'like', '%' . $states . '%');
                                            }
                                        })
                                    ->get();
                                }
                            }                          
                           
                        }
                    }else{
                        if(count($request->language)>0){
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)   
                                    ->where('users.gender',$request->gender) 
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp]) 
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2) 
                                    ->where('users.gender',$request->gender)   
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp]) 
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })                            
                                    ->get();
                                }else{
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('user_id', 'is_call', 'is_chat', 'call_charge', 'chat_charge', 'practice_area', 'practice_state', 'experience', 'language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })
                                    ->get();
                                }
                            }else{
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)    
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp]) 
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)    
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp]) 
                                    ->where(function ($query) use ($getlanguage) {
                                        foreach ($getlanguage as $value) {
                                            $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                        }
                                    })                            
                                    ->get();
                                }else{

                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)    
                                    ->where(function ($query) use ($getlanguage) {
                                            foreach ($getlanguage as $value) {
                                                $query->orWhere('lawyer_details.language', 'like', '%' . $value . '%');
                                            }
                                        })                            
                                    ->get();
                                }
                            }
                        }else{
                            if($request->gender!=''){
                                if(count($request->experience)>0){
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp]) 
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])  
                                    ->get();
                                }else{
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->where('users.gender',$request->gender)                                   
                                    ->get();
                                }
                            }else{
                                if(count($request->experience)>0){
                                     $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$minexp,$maxexp]) 
                                    ->orWhere('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    ->whereBetween('lawyer_details.experience',[$min1exp,$max1exp])                                    
                                    ->get();
                                }else{                                    
                                    $lawyers = DB::table('lawyer_details')
                                    ->select('lawyer_details.user_id', 'lawyer_details.is_call', 'lawyer_details.is_chat', 'lawyer_details.call_charge', 'lawyer_details.chat_charge', 'lawyer_details.practice_area', 'lawyer_details.practice_state', 'lawyer_details.experience', 'lawyer_details.language')
                                    ->join('users', 'users.id', '=', 'lawyer_details.user_id')
                                    ->where('lawyer_details.is_adminVerified', 1)
                                    ->where('users.user_type', 2)
                                    
                                    ->get();
                                }
                            }
                        }
                    }
                } 
            }
            
        }
        if(sizeof($lawyers)){
            $data=[];
            foreach($lawyers as $row){
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

                    $geteducations=DB::table('lawyer_details')->select('education_details')->where('user_id',$row->user_id)->first();
                    if(!empty($geteducations)){
                        $row->education_details=$geteducations->education_details?$geteducations->education_details:null;
                    }else{
                        $row->education_details=null;
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
                    
                    $row->language=$language;
                    $row->practice_area=implode(',', $area);
                    $row->practice_state=$state;
                    $row->rating=round($rating,2);
                    $row->total_review=$getreview;                                        
                    $data[]=$row;
                    unset($row->user_id); 
                }
            }         
            
           if($data==[]){
                return response()->json(["status" => 400,"success"=>false,"message"=>"Data not found"]); 
            }else{               
                    $data = json_encode($data, true);
                    $data = json_decode($data, true);                
                    usort($data, function ($a, $b) {
                        return $b['is_active'] - $a['is_active'];
                    });
                    $sortedJson = json_encode($data, JSON_PRETTY_PRINT);   
                return response()->json(["status" => 200,"success"=>true,"message"=>"All Lawyer's by filter",'data'=>json_decode($sortedJson)]); 
            }

        }else{
            return response()->json(["status" => 400,"success"=>false,"message"=>"data not found"]); 
        }
    }
}
