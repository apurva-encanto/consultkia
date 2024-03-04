<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
	
    Route::get('getUserStatus', [App\Http\Controllers\API\UserController::class, 'getUserStatus']);
    Route::post('/agoraWithAppUserDelete',[AgoraController::class,'agoraWithAppUserDelete']);
    /** Agora routing start */ 
   //  Route::get('/createToken',[AgoraController::class,'createToken']);
    Route::post('/createToken',[App\Http\Controllers\API\AgoraController::class,'createToken']);
    Route::post('/userRtcToken',[App\Http\Controllers\API\AgoraController::class,'userRtcToken']);
    Route::post('/createToken2',[App\Http\Controllers\API\AgoraController::class,'createToken2']);
    Route::post('/agoraRegister',[App\Http\Controllers\API\AgoraController::class,'agoraRegister']);
    Route::post('/agoraProfileUpdate',[App\Http\Controllers\API\AgoraController::class,'agoraProfileUpdate']);
    Route::post('/agoraProfileGet',[App\Http\Controllers\API\AgoraController::class,'agoraProfileGet']);
    Route::post('/agoraUserList',[App\Http\Controllers\API\AgoraController::class,'agoraUserList']);
    Route::post('/agoraUserDelete',[App\Http\Controllers\API\AgoraController::class,'agoraUserDelete']);
    Route::post('/sendOneToOneMsg',[App\Http\Controllers\API\AgoraController::class,'sendOneToOneMsg']);
    Route::post('/chatroomsCreate',[App\Http\Controllers\API\AgoraController::class,'chatroomsCreate']);
    Route::post('/chatroomsDetails',[App\Http\Controllers\API\AgoraController::class,'chatroomsDetails']);
    Route::post('/sendChatroomsMsg',[App\Http\Controllers\API\AgoraController::class,'sendChatroomsMsg']);
    Route::post('/contactManage',[App\Http\Controllers\API\AgoraController::class,'contactManage']);
    Route::post('/msgSendForWeb',[App\Http\Controllers\API\AgoraController::class,'msgSendForWeb']);
    Route::post('/sprect_test',[App\Http\Controllers\API\AgoraController::class,'sprect_test']);
  
    Route::post('lawyersByFilter', [App\Http\Controllers\API\EmailVerifcationController::class, 'lawyersByFilter']);
    Route::post('register', [App\Http\Controllers\API\UserController::class, 'register']);
    Route::post('checkUser', [App\Http\Controllers\API\UserController::class, 'checkUser']);
    Route::post('login', [App\Http\Controllers\API\UserController::class, 'login']); 
    Route::post('forgetPassword', [App\Http\Controllers\API\UserController::class, 'forgetPassword']); 
    Route::post('resetPassword', [App\Http\Controllers\API\UserController::class, 'resetPassword']); 
    Route::post('otpVerified', [App\Http\Controllers\API\UserController::class, 'otpVerified']); 
    Route::post('resendotp', [App\Http\Controllers\API\UserController::class, 'resendotp']); 
    Route::get('getLawyerDeatils/{id?}', [App\Http\Controllers\API\UserController::class, 'getLawyerDeatils']);
    Route::get('lawyerDetails/{id?}', [App\Http\Controllers\API\UserController::class, 'lawyerDetails']);
    
    
    Route::get('stateList', [App\Http\Controllers\API\AdminController::class, 'stateList']);
    Route::get('stateDetail/{id?}', [App\Http\Controllers\API\AdminController::class, 'stateDetail']);
    Route::get('courtDetail/{id?}', [App\Http\Controllers\API\AdminController::class, 'courtDetail']);
    Route::get('languageDetail/{id?}', [App\Http\Controllers\API\AdminController::class, 'languageDetail']);
    Route::get('practiceDetail/{id?}', [App\Http\Controllers\API\AdminController::class, 'practiceDetail']);
    Route::get('courtList', [App\Http\Controllers\API\AdminController::class, 'courtList']);
    Route::get('getoffer/{type?}', [App\Http\Controllers\API\UserController::class, 'getoffer']);
    Route::get('languageList', [App\Http\Controllers\API\AdminController::class, 'languageList']);
    // Route::get('languageList', [App\Http\Controllers\API\AdminController::class, 'languageList']);
    
    Route::get('practiceList', [App\Http\Controllers\API\AdminController::class, 'practiceList']);
    Route::get('blogList', [App\Http\Controllers\API\AdminController::class, 'blogList']);
    Route::get('lawyersList/{type?}', [App\Http\Controllers\API\UserController::class, 'lawyersList']);
    Route::get('lawyerByArea/{type?}', [App\Http\Controllers\API\UserController::class, 'lawyerByArea']);
    Route::post('reviewlist', [App\Http\Controllers\API\ReviewController::class, 'reviewlist']);
    Route::get('reviewscount/{id?}', [App\Http\Controllers\API\ReviewController::class, 'reviewscount']);
    Route::get('blogDetails/{id?}', [App\Http\Controllers\API\AdminController::class, 'blogDetails']);
    Route::get('checkavailability/{id?}', [App\Http\Controllers\API\AvailabilityController::class, 'checkavailability']);
    Route::get('guidelines/{usertype?}/{type?}', [App\Http\Controllers\API\AdminController::class, 'guidelines']);
    Route::get('clearOrder', [App\Http\Controllers\API\AdminController::class, 'clearOrder']);
    Route::post('peymenthandle', [App\Http\Controllers\API\PaymentController::class, 'peymenthandle']);


    
    Route::get('changeActiveStatus/{id}', [App\Http\Controllers\API\AdminController::class, 'changeActiveStatus']);


      

    Route::group(['middleware'=>['Login']],function(){ 
        
        Route::get('userTicketlist', [App\Http\Controllers\API\ChatController::class, 'userTicketlists']);  

	    Route::post('updateUserStatus', [App\Http\Controllers\API\UserController::class, 'updateUserStatus']);  

        
    	Route::post('changePassword', [App\Http\Controllers\API\UserController::class, 'changePassword']);
        Route::get('dashboard', [App\Http\Controllers\API\AdminController::class, 'dashboard']);
    	Route::get('addEditPremium/{id}', [App\Http\Controllers\API\AdminController::class, 'addEditPremium']);
    	Route::post('addEditState', [App\Http\Controllers\API\AdminController::class, 'addEditState']);
    	Route::post('addEditPractice', [App\Http\Controllers\API\AdminController::class, 'addEditPractice']);  	
        Route::get('userDetails/{id?}', [App\Http\Controllers\API\AdminController::class, 'userDetails']);
        Route::get('lawyerDetailforAdmin/{id?}', [App\Http\Controllers\API\AdminController::class, 'lawyerDetailforAdmin']);
        Route::get('faqDetail/{id?}', [App\Http\Controllers\API\AdminController::class, 'faqDetail']);
        Route::get('practiceDelete/{id}', [App\Http\Controllers\API\AdminController::class, 'practiceDelete']);
        Route::get('stateDelete/{id}', [App\Http\Controllers\API\AdminController::class, 'stateDelete']);
        Route::get('courtDelete/{id}', [App\Http\Controllers\API\AdminController::class, 'courtDelete']);
        Route::get('languageDelete/{id}', [App\Http\Controllers\API\AdminController::class, 'languageDelete']);

        Route::post('UpdateKycStatus', [App\Http\Controllers\API\AdminController::class, 'UpdateKycStatus']);
        Route::post('usersList', [App\Http\Controllers\API\AdminController::class, 'usersList']);
        Route::post('faqdelete', [App\Http\Controllers\API\AdminController::class, 'faqdelete']);
        Route::get('withdrawalRequestlist', [App\Http\Controllers\API\AdminController::class, 'withdrawalRequestlist']);
        Route::get('lawyerListforAdmin/{type?}', [App\Http\Controllers\API\AdminController::class, 'lawyerListforAdmin']);
        Route::post('alluserslist', [App\Http\Controllers\API\AdminController::class, 'alluserslist']);

	    Route::post('addtransaction', [App\Http\Controllers\API\PaymentController::class, 'addtransaction']);
	    Route::get('walletBalance', [App\Http\Controllers\API\PaymentController::class, 'walletBalance']);
	    Route::post('testApi', [App\Http\Controllers\API\PaymentController::class, 'testApi']);
	    Route::get('checkstatus/{id?}', [App\Http\Controllers\API\PaymentController::class, 'checkstatus']);
	    Route::post('addQuidelines', [App\Http\Controllers\API\AdminController::class, 'addQuidelines']);
	    Route::get('getprofile', [App\Http\Controllers\API\UserController::class, 'getprofile']);
	    Route::post('updateProfile', [App\Http\Controllers\API\UserController::class, 'updateProfile']);	        
	    Route::post('addEditLanguage', [App\Http\Controllers\API\AdminController::class, 'addEditLanguage']);
	    Route::post('addEditCourt', [App\Http\Controllers\API\AdminController::class, 'addEditCourt']);
	    Route::post('updateSequencing', [App\Http\Controllers\API\AdminController::class, 'updateSequencing']);
	    Route::post('addEditBlog', [App\Http\Controllers\API\AdminController::class, 'addEditBlog']);
	    Route::post('addavailability', [App\Http\Controllers\API\AvailabilityController::class, 'addavailability']);
	    Route::post('favoriteUnfavorite', [App\Http\Controllers\API\UserController::class, 'favoriteUnfavorite']);
	    Route::get('favoriteUnfavoriteList', [App\Http\Controllers\API\UserController::class, 'favoriteUnfavoriteList']);
	    Route::get('getAllOrders/{id?}', [App\Http\Controllers\API\OrderController::class, 'getAllOrders']);
	    Route::get('getCallhistory', [App\Http\Controllers\API\OrderController::class, 'getCallhistory']);
	    Route::get('getChathistory/{type?}', [App\Http\Controllers\API\OrderController::class, 'getChathistory']);
	    Route::get('callDetail/{id?}', [App\Http\Controllers\API\OrderController::class, 'callDetail']);
	    Route::post('addCallRecord/{id?}', [App\Http\Controllers\API\OrderController::class, 'addCallRecord']);
	    Route::get('transactionList/{type?}', [App\Http\Controllers\API\TransactionController::class, 'transactionList']);
	    Route::post('withdrawalRequest', [App\Http\Controllers\API\TransactionController::class, 'withdrawalRequest']);
	    Route::post('approvedWithdrawal', [App\Http\Controllers\API\TransactionController::class, 'approvedWithdrawal']);
	    Route::get('myReviews', [App\Http\Controllers\API\ReviewController::class, 'myReviews']);
	    Route::post('addReview', [App\Http\Controllers\API\ReviewController::class, 'addReview']);
	    Route::post('reviewResponse', [App\Http\Controllers\API\ReviewController::class, 'reviewResponse']);
	    Route::post('addReviewResponse', [App\Http\Controllers\API\ReviewController::class, 'addReviewResponse']);
	    Route::get('getOrderReview/{id?}', [App\Http\Controllers\API\ReviewController::class, 'getOrderReview']);

        // notification
        Route::get('lawyerHome/{type?}', [App\Http\Controllers\API\UserController::class, 'lawyerHome']);
        Route::get('checkLawyer/{id?}/{type?}', [App\Http\Controllers\API\UserController::class, 'checkLawyer']);
        Route::post('updateFcm', [App\Http\Controllers\API\UserController::class, 'updateFcm']);
	    Route::post('updateChat', [App\Http\Controllers\API\NotificationController::class, 'updateChat']);
        Route::post('callNotification', [App\Http\Controllers\API\NotificationController::class, 'callNotification']);
        Route::post('sendRequest', [App\Http\Controllers\API\NotificationController::class, 'sendRequest']);
        Route::post('acceptRequest', [App\Http\Controllers\API\NotificationController::class, 'acceptRequest']);
        Route::post('cancelledRequest', [App\Http\Controllers\API\NotificationController::class, 'cancelledRequest']);

         // chat module
        Route::post('ticketstatus_update', [App\Http\Controllers\API\ChatController::class, 'ticketstatus_update']);
        Route::post('addChat', [App\Http\Controllers\API\ChatController::class, 'addChat']);
        Route::post('clickAction', [App\Http\Controllers\API\ChatController::class, 'clickAction']);
        Route::get('getMsg/{ticket?}', [App\Http\Controllers\API\ChatController::class, 'getMsg']);
        Route::get('orderchat/{ticket?}', [App\Http\Controllers\API\ChatController::class, 'orderchat']);
        Route::post('getMsgforadmin', [App\Http\Controllers\API\ChatController::class, 'getMsgforadmin']);
        // Route::post('getMsgforadmin', [App\Http\Controllers\API\ChatController::class, 'getMsgforadmin']);
        Route::post('createTicket', [App\Http\Controllers\API\ChatController::class, 'createTicket']);
        Route::post('saveImage', [App\Http\Controllers\API\ChatController::class, 'saveImage']);
        Route::get('userTicketlist/{id?}', [App\Http\Controllers\API\ChatController::class, 'userTicketlist']); 
        Route::post('addChatCategory', [App\Http\Controllers\API\ChatController::class, 'addChatCategory']); 
        Route::get('getChatCategory', [App\Http\Controllers\API\ChatController::class, 'getChatCategory']); 
        // chat end here
        

        
	    Route::get('logout', [App\Http\Controllers\API\UserController::class, 'logout']);


        
	    Route::get('adminWithdrawalRequest', [App\Http\Controllers\API\PaymentController::class, 'adminWithdrawalRequest']);
	    Route::get('approveWithdrawalRequest/{id}/{status}', [App\Http\Controllers\API\PaymentController::class, 'approveWithdrawalRequest']);
        
	    Route::get('userPermanentDelete', [App\Http\Controllers\API\UserController::class, 'userPermanentDelete']);




        Route::post('sendCallRequest', [App\Http\Controllers\API\OrderController::class, 'sendCallRequest']);
});
