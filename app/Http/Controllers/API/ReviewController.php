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
use App\Models\OrderReview;
use App\Models\Rate;
use App\Models\Pet;
use App\Models\Review;

class ReviewController extends ApiController
{
    public function __construct(){
        Auth::shouldUse('api');
    }

    public function reviewlist(Request $request){    
        $validator = Validator::make($request->all(), [ 
            'lawyer_id' => 'required',   
            "sort_by"=>"required",
            "rating"=>""
         ]);
        if($validator->fails()){
            return response()->json(["status" => 407,"success" => false,"message" => $validator->errors()->first()]);
        }   

        if($request->sort_by=='1'){
            if($request->rating!=''){
                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id,'rating'=>$request->rating])->orderby('rating','desc')->paginate('8');
            }else{

                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id])->orderby('rating','desc')->paginate('8');
            }
        }elseif($request->sort_by=='2'){
            if($request->rating!=''){
                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id,'rating'=>$request->rating])->orderby('rating','asc')->paginate('8');
            }else{

                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id])->orderby('rating','asc')->paginate('8');
            }
        }elseif($request->sort_by=='3'){
             if($request->rating!=''){
                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id,'rating'=>$request->rating])->orderby('id','asc')->paginate('8');
                }else{

                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id])->orderby('id','asc')->paginate('8');
            }
        }elseif($request->sort_by=='4'){
            if($request->rating!=''){
                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id,'rating'=>$request->rating])->orderby('id','desc')->paginate('8');
            }else{
                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id])->orderby('id','desc')->paginate('8');
            }

        }else{
            if($request->rating!=''){
                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id,'rating'=>$request->rating])->orderby('id','desc')->paginate('8');
            }else{
                $review = Review::with('userDetail')->select('id','lawyer_id','user_name','user_id','rating','description')->where(['lawyer_id'=>$request->lawyer_id])->orderby('id','desc')->paginate('8');
            }
            
        }
                
        if(sizeof($review)){            
            foreach($review  as $row){                           
                $row->id=$row->id;
                $row->user_id=$row->user_id;
                if($row->user_name!=null){

                    $row->user_name=$row->user_name;
                }else{
                    $row->user_name=$row->userDetail->user_name;
                }
                $row->user_profile=$row->userDetail->profile_img?imgUrl.'user_profile/'.$row->userDetail->profile_img:user_img;
                $row->description=$row->description;
                $row->rating=$row->rating?$row->rating:0;
                $data[]=$row;   
                unset($row->userDetail);
            }
            $finalData['status'] = 200;
            $finalData['success'] = true;
            $finalData['message'] ="Reviews list";
            $finalData['data'] =!empty($data)?$data:array();
            $finalData['currentPage'] = $review->currentPage();
            $finalData['last_page'] = $review->lastPage();
            $finalData['total_record'] = $review->total();
            $finalData['per_page'] = $review->perPage();
            return response($finalData);
            // return response()->json(['status' => 200,'success' => true, 'message' =>"Reviews list",'data'=>$data]);                   
        }else{     
                $finalData['status'] = 200;
                $finalData['success'] = true;
                $finalData['message'] ="Reviews not found";
                $finalData['data'] =[];
                $finalData['currentPage'] = 1;
                $finalData['last_page'] = 1;
                $finalData['total_record'] = 0;
                $finalData['per_page'] = "8";
                return response($finalData);       
            // return response()->json(['status' => 400,'success' => false, 'message'=>"No data found"]);
        }
    }

    public function addReview(Request $request){ 
        $validator = Validator::make($request->all(), [ 
           'lawyer_id' => 'required',   
           'order_id' => 'required',   
           "description"=>"",
           "rating"=>""
        ]);
        if($validator->fails()){
            return response()->json(["status" => 400,"success" => false,"message" => $validator->errors()->first()]);
        }   

        $user=Auth::user();   
        if(!empty($user)) {
            $review=new Review();
            $review->lawyer_id=$request->lawyer_id;
            $review->order_id=$request->order_id;
            $review->user_id=$user->id;
            $review->user_name=$request->user_name?$request->user_name:null;
            $review->description=$request->description?$request->description:null;            
            $review->date=Carbon::now();
            $review->rating=$request->rating?$request->rating:0;
            // return  $review;
            $query=$review->save();
            if($query==true){
                return response()->json(['status' => 200,'success' => true, 'message' =>"Your review has been successfully submitted !"]);
            }else{
                return response()->json(['status' => 400,'success' => false, 'message'=>"Failed try again"]);
            }   
        }else{
            return response()->json(['status' => 400,'success' => false, 'message'=>"You're not authorized"]);
        } 
    }

    public function reviewResponse(Request $request){ 
        $validator = Validator::make($request->all(), [ 
           'review_id' => 'required',   
           "description"=>"required",
           
        ]);
        if($validator->fails()){
            return response()->json(["status" => 400,"success" => false,"message" => $validator->errors()->first()]);
        }   

        $user=Auth::user();   
        if(!empty($user)) {
            $review=new Review();
            $review->review_id=$request->review_id;
            $review->user_id=$user->id;
           
            $review->description=$request->description?$request->description:null;
            
            $review->date=Carbon::now();
            $review->rating=$request->rating?$request->rating:0;
            $query=$review->save();
            if($query){
                return response()->json(['status' => 200,'success' => true, 'message' =>"Your review has been submitted !"]);
            }else{
                return response()->json(['status' => 400,'success' => false, 'message'=>"Failed try again"]);
            }   
        }else{
            return response()->json(['status' => 400,'success' => false, 'message'=>"You're not authorized"]);
        } 
    }
    
    public function myReviews(){
        $user=Auth::user();
        if($user->user_type==2){
            $getallReviews=Review::select('id','user_id','rating','description')->with('userDetail')->where('lawyer_id',$user->id)->orderbydesc('id')->paginate(8);        
            if(sizeof($getallReviews)){            
                foreach($getallReviews  as $row){                           
                    $row->id=$row->id;
                    $row->user_id=$row->user_id;
                    $row->user_name=$row->userDetail->user_name;
                    $row->user_profile=$row->userDetail->profile_img?imgUrl.'user_profile/'.$row->userDetail->profile_img:user_img;
                    $row->description=$row->description;
                    $row->rating=$row->rating?$row->rating:0;
                    $data[]=$row;   
                    unset($row->userDetail);
                }
                $finalData['status'] = 200;
                $finalData['success'] = true;
                $finalData['message'] ="My Reviews list";
                $finalData['data'] =!empty($data)?$data:array();
                $finalData['currentPage'] = $getallReviews->currentPage();
                $finalData['last_page'] = $getallReviews->lastPage();
                $finalData['total_record'] = $getallReviews->total();
                $finalData['per_page'] = $getallReviews->perPage();
                return response($finalData);
                                  
            }else{     
                $finalData['status'] = 200;
                $finalData['success'] = true;
                $finalData['message'] ="Reviews not found";
                $finalData['data'] =[];
                $finalData['currentPage'] = 1;
                $finalData['last_page'] = 1;
                $finalData['total_record'] = 0;
                $finalData['per_page'] = 8;
                return response($finalData);
            }
        }elseif($user->user_type==3){
            $getallReviews=Review::select('id','user_id','rating','lawyer_id','description')->with('userDetail','lawyerDetails')->orderbydesc('id')->paginate(10);
            if(sizeof($getallReviews)){            
                foreach($getallReviews  as $row){                           
                    $row->id=$row->id;
                    $row->user_id=$row->user_id;
                    $row->user_name=$row->userDetail->name;
                    $row->user_image=$row->userDetail->profile_img?imgUrl.'profile/'.$row->userDetail->profile_img:user_img;
                    $row->lawyer_name=$row->lawyerDetails->first_name.' '.$row->lawyerDetails->last_name;
                    $row->lawyer_image=$row->lawyerDetails->profile_img?imgUrl.'profile/'.$row->lawyerDetails->profile_img:user_img;
                    $row->description=$row->description;
                    $row->rating=$row->rating?$row->rating:0;
                    $data[]=$row;   
                    unset($row->userDetail);
                    unset($row->lawyerDetails);
                }
                $finalData['status'] = 200;
                $finalData['success'] = true;
                $finalData['message'] ="All Reviews list";
                $finalData['data'] =!empty($data)?$data:array();
                $finalData['currentPage'] = $getallReviews->currentPage();
                $finalData['last_page'] = $getallReviews->lastPage();
                $finalData['total_record'] = $getallReviews->total();
                $finalData['per_page'] = $getallReviews->perPage();
                return response($finalData);
                                  
            }else{     
                $finalData['status'] = 200;
                $finalData['success'] = true;
                $finalData['message'] ="Reviews not found";
                $finalData['data'] =[];
                $finalData['currentPage'] = 1;
                $finalData['last_page'] = 1;
                $finalData['total_record'] = 0;
                $finalData['per_page'] = 10;
                return response($finalData);
            }
        }else{
            return response()->json(["status" => 400,"success"=>false,"message"=>"You're not authorized"]);
        }

    }

    public function addReviewResponse(Request $request){ 
            $validator = Validator::make($request->all(), [ 
               'order_id' => 'required',   
               "description"=>"",
               "rating"=>""
            ]);
            if($validator->fails()){
                return response()->json(["status" => 400,"success" => false,"message" => $validator->errors()->first()]);
            }   

            $user=Auth::user();   
            if(!empty($user)) {
                $check=OrderReview::where('order_id',$request->order_id)->where('lawyer_id',$user->id)->first();
                if(!empty($check)){
                    $review=OrderReview::find($check->id); 
                    if($request->description!=''){
                        $review->description=$request->description;    
                        $msg="Review successfully updated";            
                    
                    }else{
                         $review->rating=$request->rating;
                         $msg="Rating successfully updated";
                    }
                    
                }else{

                    $review=new OrderReview();
                    if($request->description!=''){
                        $review->description=$request->description;    
                        $msg="Review successfully sent";            
                    
                    }else{
                         $review->rating=$request->rating;
                         $msg="Rating successfully done";
                    }
                    $review->lawyer_id=$user->id;
                    $review->order_id=$request->order_id;
                               
                    
                }
                $query=$review->save();
                if($query){
                    return response()->json(['status' => 200,'success' => true, 'message' =>$msg]);
                }else{
                    return response()->json(['status' => 400,'success' => false, 'message'=>"Failed try again"]);
                }   
            }else{
                return response()->json(['status' => 400,'success' => false, 'message'=>"You're not authorized"]);
            } 
    }

    public function getOrderReview($id=''){
        $user=Auth::user();
        if($user->user_type==2){
            $check=Review::with('userDetail')->where('order_id',$id)->first();

            if(!empty($check)){
                $array1=[];
                $checkresponse=OrderReview::where('order_id',$check->order_id)->first();   
                // return $checkresponse;
                if(!empty($checkresponse)){
                    $array1['description'] =  $checkresponse->description?$checkresponse->description:null;    
                    $array1['rating'] =  $checkresponse->rating?(string)$checkresponse->rating:"0.00";   
                    $array1['user_name'] =$user->user_name;     
                }else{
                    $array1['description'] = null;
                    $array1['rating'] = "0.00";    
                    $array1['user_name'] =$user->user_name;     
                }

                $array['rating']=$check->rating?$check->rating:"0.00";
                $array['description']=$check->description?$check->description:null;
                $array['user_name']=$check->userDetail->user_name?$check->userDetail->user_name:"Anonymous";
                $data['user']=$array;
                $data['lawyer']=$array1;
               // return $data;
                return response()->json(['status' => 200,'success' => true, 'message'=>"Order respommnse review",'data'=>$data]);
            }else{
                return response()->json(['status' => 400,'success' => false, 'message'=>"No data found"]);
            }
        }else{
            return response()->json(['status' => 400,'success' => false, 'message'=>"You're not authorized"]);
        }
    }

   
}
