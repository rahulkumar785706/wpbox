<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Wpbox\Models\Message;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function sendWhatsAppNotification(Request $request, Message $message){
        
        $validate = Validator::make($request->all(), [
            'phone' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validate->errors()->first(),
                'status'=>422
            ], 422);
        }
       return $message->campaign->company;
        $company=null;
        try {
            $company=$message->campaign->company;
            $message->contact->phone = $request->phone;
        } catch (\Throwable $th) {
           $message->error="The company or contact is not found";
           $message->status=1;
           $message->update();
        }
       
        if($company){
            $url = "https://graph.facebook.com/v19.0/".$company->getConfig('whatsapp_phone_number_id','').'/messages';
            $accessToken = $company->getConfig('whatsapp_permanent_access_token','');
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $message->contact->phone, // Add recipient information
                    'type' => 'template',
                    'template'=>[
                        "name"=> $message->campaign->template->name,
                        "language"=> [
                            "code"=> $message->campaign->template->language
                        ],
                        "components"=>json_decode($message->components)
                    ]
                ]);
            
                
                $statusCode = $response->status();
                $content = json_decode($response->body(),true);
                //dd($content);
                $message->created_at=now();
                if(isset($content['messages'])){
                   $message->fb_message_id=$content['messages'][0]['id'];
                }else{
                   $message->error=isset($content['error'])?$content['error']['message']:"Unknown error";
                }
                $message->status=1;
                $message->update();
                // Handle the response as needed based on $statusCode and $content
                return response()->json([
                    "message"=>"success",
                    "status"=>$statusCode,
                    "data"=>$content
                ],200);
            } catch (\Exception $e) {
                // Handle the exception
                throw $e;
                return response()->json([
                    "message"=>"success",
                    "status"=>500,
                    "data"=>[]
                ],500); 
               
            }
        }
        return 'oj';
    }
}
