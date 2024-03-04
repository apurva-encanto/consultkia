<?php





return [

 // email_verification



    'page_title' => 'Email verification',
    // 'codeotp' => 'Otp Code',

    'success_message_email_verification' => 'We have e-mailed your Email Verification code!.',
    'success_message_change_email_verification' => 'We have e-mailed you a Email Verification code on your current email id, because of security reasons we can not send it on new email address so please verify it to update you new email!.',
    'email_verification_code' => 'Your verification code is :code',
    'email_forgot_code' => 'Your reset password code is :code',

    'email_verification_title' => 'Please verify your email address.',

    'email_verification_action' => 'Verify email address',

    'email_verification_content_1' => 'Hi :userName !',

    'email_verification_content_2' => 'Verify your email',

    'email_verification_content_3' => 'Thank You for using Delux Cars App .',

    'Regards' => 'Regards ,',

    'email_verification_success_subject' => 'Email Verification Sucess',

    'email_verification_content_4' => 'Your email verified successfully',

    'email_verification_content_5' => 'Thank you for using our application!',

    'email_verification_button' => 'Proceed',

    'email_verification_error_400' => 'All fields are mandatory, please check and fill!!',

    'email_verification_error_401' => 'This verification is token Invalid or expired!',

    'email_verification_error_402' => 'We can\'t find a user with that e-mail address.',

    'email_verification_error_403' => 'Your email verification done!.',
    
    'invalid_email_password' => 'Email id or password is invalid!!',



    // email footer -- /resources/views/vendor/mail/html/message.blade.php

    'email_footer' => 'All rights reserved',



    // login controllers

    'login_without_verification' => 'Verification is pending please check your email for verification code!',

    'login_without_phone_verification' => 'Verification is pending please check your phone messages for verification code!',

    'login_success' => 'Login successful',

   // set new password 


    'success_message_reset_request' => 'We have e-mailed you a password reset link!.',

    'password_newset_button' => 'Login with Temporary Password',

    'password_newset_title' => 'Mail Login with company',
    'email_temporary_password' => 'Your Temporary password is :code',

   
    'password_newset_content_1' => 'You are receiving this email because we received a password new set request for your account.',
    'password_newset_content_3' => 'If you did not request a password new set, no further action is required.',

    // reset password 

    'success_message_reset_request' => 'We have e-mailed you a password reset link!.',

    'password_reset_button' => 'Reset Password',

    'password_reset_title' => 'Reset Password Notification',

    'password_reset_error_403' => 'Password successfully reset!.',

    // reset email content

    'password_reset_content_1' => 'You are receiving this email because we received a password reset request for your account.',

    'password_reset_content_2' => 'This password reset link will expire in 60 minutes.',

    'password_reset_content_3' => 'If you did not request a password reset, no further action is required.',

    'password_reset_success_subject' => 'Reset pasword Sucess',

    'password_reset_success_content_1' => 'Your password reset successfully.',

    // sms otp verification

    'phone_verification' => 'You\'re receiving this message because you recently created a new : account or added a new phone number. If this wasn\'t you, please ignore this message, OTP is : ',
    'success_message_phone_verification' => 'We have sent a message to your phone number for a Verification code! .',


    // phone verification messages
    'phone_verification_error_402' => 'We can\'t find a user with that phone number.',

    // success  message by sms
     'phone_verification_success' => 'Your phone verification successfully done',
    'success_message_phone_verification' => 'We have sent a message to your phone number for a Verification code! .',


    // Temporary password
    'temporary_password' => 'Otp Code For Reset Password : ',
    'ForgetPassword' => 'Otp Code For Forget Password : ',

];



    ?>