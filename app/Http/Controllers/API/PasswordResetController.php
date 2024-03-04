<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request; 

use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Notifications\PasswordResetRequest;

use App\Notifications\PasswordResetSuccess;

use App\PasswordReset;

use Illuminate\Http\JsonResponse;

use Illuminate\Support\Str;

use Validator;

use Session;

use App\Models\User;



class PasswordResetController extends Controller

{

    public function create(Request $request)

    {

        $data = json_decode($request->getContent(), true);

        $validator = Validator::make($data, [

            'email' => 'required|string|email',

        ]);

        if ($validator->fails()) {

            return response()->json(["error"=>trans('emailNotifications.email_verification_error_400')],JsonResponse::HTTP_BAD_REQUEST); 

        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {

            return response()->json(["error"=>trans('emailNotifications.email_verification_error_402')],JsonResponse::HTTP_BAD_REQUEST); 

        }

        $token = Str::random(64);

        $passwordReset = PasswordReset::updateOrCreate(

            ['email' => $user->email],

            [

                'email' => $user->email,

                'token' => $token,

            ]

        );

        if ($user && $passwordReset) {

            $user->notify(new PasswordResetRequest($passwordReset->token));

        }

        return response()->json(["message"=>trans('emailNotifications.success_message_reset_request')],JsonResponse::HTTP_OK);

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

        $passwordReset = PasswordReset::where('token', $token)->first();

        if (!$passwordReset) {

            return response()->json(['status' => '400', 'message' => 'This password reset token is invalid.']);

        }

        if (

            Carbon::parse($passwordReset->updated_at)

                ->addMinutes(720)

                ->isPast()

        ) {

            $passwordReset->delete();

            return response()->json(['status' => '400', 'message' => 'This password reset token is invalid.']);

        }

        return response()->json($passwordReset);

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

    public function reset(Request $request)

    {

        // print_r($request->all()); die();

        $validator = Validator::make($request->all(), [

            'email' => 'required|string|email',

            'password' => 'required',

            // 'token' => 'required|string',

        ]);

        if ($validator->fails()) {

            // return response()->json(['message' => 'All fields with star are mandatory, please check and fill!!', 'error' => $validator->errors(), 'status' => 400]);

              // return redirect()->back()

              //           ->withErrors($validator)

              //           ->withInput();

            $message = '400';

            return redirect('/reset-link-password/'.$request->token.'/'.base64_encode($request->current_lang).'/'.base64_encode($request->email).'/'.$message);

        }



        $passwordReset = PasswordReset::where([['token', $request->token], ['email', $request->email]])->first();

        if (!$passwordReset) {

            // return response()->json(

            //     [

            //         'message' => 'This password reset token or email is invalid.',

            //     ],

            //     404

            // );

            $message = '401';

            return redirect('/reset-link-password/'.$request->token.'/'.base64_encode($request->current_lang).'/'.base64_encode($request->email).'/'.$message);

        }

        $user = User::where([['email','=', $passwordReset->email],['status','=',1]])->first();

        if (!$user) {

            // return response()->json(

            //     [

            //         'message' => "We can't find a user with that e-mail address.",

            //     ],

            //     404

            // );

            $message = '402';

            return redirect('/reset-link-password/'.$request->token.'/'.base64_encode($request->current_lang).'/'.base64_encode($request->email).'/'.$message);

        }

        // $user->original_password = $request->password;

        $user->password = bcrypt($request->password);

        $user->save();

        $passwordReset->delete();

        $user->notify(new PasswordResetSuccess($passwordReset));

        // $res = ['status'=>"200",'message'=>'Your password reset successfully! please click on login button for login page'];

        // return response()->json($res);

        // return response()->json($user);

        $message = '403';

            return redirect('/reset-link-password/'.$request->token.'/'.base64_encode($request->current_lang).'/'.base64_encode($request->email).'/'.$message);

    }

}

