<?php

namespace Modules\Wpbox\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Wpbox\Models\Contact;
use Modules\Wpbox\Models\Campaign;
use Modules\Wpbox\Models\Template;
use Modules\Wpbox\Traits\Whatsapp;
use Modules\Wpbox\Traits\Contacts;
use Carbon\Carbon;
use Closure;
use Modules\Contacts\Models\Group;
use Modules\Wpbox\Models\Message;
use Modules\Wpbox\Models\Reply;

class APIController extends Controller
{
    use Contacts;
    use Whatsapp;

    
   


    //Send message to phone number
    public function sendMessageToPhoneNumber(Request $request)
    {

        return $request;
       
        return $this->authenticate($request,function($request){
            //Company
            $company=$this->getCompany();
             
            //Make or get the contact
            $contact=$this->getOrMakeContact($request->phone,$company,$request->phone);

            //If request has buttons
            if($request->has('buttons') || $request->has('header') || $request->has('footer')){
                
                $header_text="";
                if($request->has('header')){
                    $header_text=$request->header;
                }
                $footer_text="";
                if($request->has('footer')){
                    $footer_text=$request->footer;
                }

                // Make Replay object
                $replay = new Reply([
                    'trigger' => 'none',
                    'type' => 1,
                    'text' => $request->message,
                    'company_id' => $company->id,
                    'header' => $header_text,
                    'footer' => $footer_text,
                ]);

                //In the $reply we have button1, button1_id. Assign them from $buttons
                if($request->has('buttons')){
                    foreach ($request->buttons as $key => $button) {
                        if($key<3){
                            $replay['button'.($key+1)]=$button['title'];
                            $replay['button'.($key+1).'_id']=$button['id'];
                        }
                    }
                }
               

                $message=$contact->sendReply($replay);

            }else{
                //Just message
                $message=$contact->sendMessage($request->message,false);
            }
            return response()->json(['status'=>'success','message_id'=>$message->id,'message_wamid'=>$message->fb_message_id]);     




            
           
        },[
            'token' => 'required',
            'phone' => 'required',
            'message' => 'required',
        ]); 
    }

    //Send Template     message to phone number
    public function sendTemplateMessageToPhoneNumber(Request $request)
    {
        // return($request->header('token'));
        // return 'done';
        return $this->authenticate($request,function($request){
            //Company 
            $company=$this->getCompany();
            
             //Make or get the contact
             $contact=$this->getOrMakeContact($request->phone,$company,$request->phone);

             //Find the template based on the provided id
             $template=Template::where('company_id',$company->id)->where('name',$request->template_name)->where('language',$request->template_language)->first();
 
             if(!$template){
                 return response()->json(['status'=>'error','message'=>'Invalid template']);
             }
 
             $campaign = Campaign::create([
                 'name'=>"api_message_".now(),
                 'timestamp_for_delivery'=>null,
                 'variables'=>"",
                 'variables_match'=>"",
                 'template_id'=>$template->id,
                 'group_id'=>null,
                 'contact_id'=>$contact->id,
                 'total_contacts'=>Contact::count(),
             ]);
 
             $bodyText="API Message";
             $header_text="";
             try {
                 foreach(json_decode($template->components,true) as $component){
                     if(strtoupper($component['type'])=='BODY'){
                         $bodyText=$component['text'];
                         foreach ($request->components as $key => $receivedComponent) {
                             if(strtolower($receivedComponent['type'])=='body'){
                                 foreach ($receivedComponent['parameters'] as $keyp => $parameter) {
                                     $bodyText=str_replace("{{".($keyp+1)."}}", $parameter['text'], $bodyText);
                                 }
                             }
                         }
                     }
                     if($component['type']=='HEADER'&&$component['format']=="TEXT"){ 
                         $header_text=$component['text'];
                         foreach ($request->components as $key => $receivedComponent) {
                             if($receivedComponent['type']=='header'){
                                 foreach ($receivedComponent['parameters'] as $keyp => $parameter) {
                                     $bodyText=str_replace("{{".($keyp+1)."}}", $parameter['text'], $bodyText);
                                 }
                             }
                         }
                     }
                 }

             } catch (\Throwable $th) {
                 //throw $th;
             }
             
            
             $dataForMessage=[
                 "contact_id"=>$contact->id,
                 "company_id"=>$contact->company_id,
                 "value"=>$bodyText,
                 "header_image"=>"",
                 "header_video"=>"",
                 "header_audio"=>"",
                 "header_document"=>"",
                 "footer_text"=>"",
                 "buttons"=>"",
                 "header_text"=>$header_text,
                 "is_message_by_contact"=>false,
                 "is_campign_messages"=>true,
                 "status"=>0,
                 "created_at"=>now(),
                 "scchuduled_at"=>Carbon::now(),
                 "components"=>json_encode($request->components),
                 "campaign_id"=>$campaign->id,
             ];
            
             //return response()->json($dataForMessage);
             //Create a message on the contact
             $message=Message::create($dataForMessage);

             $this->sendCampaignMessageToWhatsApp($message);
 
 
             return response()->json(['status'=>'success','message_id'=>$message->id,'message_wamid'=>$message->fb_message_id]);

        },
        [
         'token' => 'required',
            'phone' => 'required',
            'template_name' => 'required',
            'template_language' => 'required',
            'components' => 'array'
        ]);

        
    }

    //Get ot make contact  //Last Update by Brij 24Jun
    public function makeContact($name,$phone,$company)
    {
        $contact=Contact::where('company_id',$company->id)->where('phone',$phone)->first();
        if(!$contact){
            $contact=Contact::create([
                'name'=>$name ?? $phone,
                'phone'=>$phone,
                'company_id'=>$company->id,
            ]);
        }
        return $contact;
    }

    //Get templates
    public function getTemplates(Request $request)
    {
       
        return $this->authenticate($request,function($request){
            //Company
            $company=$this->getCompany();
             //Find the template based on the provided id
             $templates=Template::where('company_id',$company->id)->get();

             return response()->json(['status'=>'success','templates'=>$templates]);
        });
    }

    //Send Campaign via API
    public function sendCampaignMessageToPhoneNumber(Request $request)
    {

        return $this->authenticate($request,function($request){
   
            //Make or get the contact
            $contact=$this->getOrMakeContact($request->phone,$this->getCompany(),$request->phone);

            //All the passed data in request data, merge with the contact
            $contact['extra_value']=$request->data; 
       
             //Get the campaign
            $message=Campaign::findOrFail($request->campaing_id)->makeMessages(null,$contact);
 
            $this->sendCampaignMessageToWhatsApp($message);

            //Api responses
            return response()->json(['status'=>'success','message_id'=>$message->id,'message_wamid'=>$message->fb_message_id]);
        },
        [
            'token' => 'required',
            'phone' => 'required',
            'campaing_id' => 'required',
        ]);  
    }

    //Get groups
    public function getGroups(Request $request)
    {
        
        return $this->authenticate($request,function($request){
            //Company
            $company=$this->getCompany();
            if ($request->has('showContacts') && $request->showContacts == "yes") {
                $groups = Group::where('company_id', $company->id)->with('contacts')->get();
            } else {
                $groups = Group::where('company_id', $company->id)->get();
            }

            return response()->json(['status'=>'success','groups'=>$groups]);

        });
    }

    public function getCampaigns(Request $request)
    {
        
        return $this->authenticate($request,function($request){
            //Company
            $company=$this->getCompany();


            
            if ($request->has('type')) {
                if($request->type=='bot'){
                    $items = Campaign::where('company_id', $company->id)->where('is_bot',true)->get();
                }else if($request->type=='api'){
                    $items = Campaign::where('company_id', $company->id)->where('is_api',true)->get();
                }else if($request->type=='regular'){
                    $items = Campaign::where('company_id', $company->id)->where('is_api',false)->where('is_bot',false)->get();
                }
            } else {
                $items = Campaign::where('company_id', $company->id)->get();
            }

            return response()->json(['status'=>'success','items'=>$items]);

        });
    }

    

    public function getContacts(Request $request)
    {
        return $this->authenticate($request,function($request){
            //Company
            $company=$this->getCompany();
            return response()->json(['status'=>'success','contacts'=>Contact::where('company_id',$company->id)->get()]);
        });
    }


     //Send Template     message to phone number
     public function contactApiMake(Request $request)
     {
        return $this->authenticate($request,function($request){
            //Company
            $company=$this->getCompany();
            
            $contact=$this->makeContact($request->name,$request->phone,$company);

            //If request has groups
            if($request->has('groups')){
               // Attaching groups to the contact
                $contact->groups()->attach($request->groups);
            }

            //If request has custom fields
            if($request->has('custom')){
                $contact->fields()->sync([]);
                foreach ($request->custom as $key => $value) {
                    if($value){
                        $contact->fields()->attach($key, ['value' => $value]);
                    }
                }
                
            }
            $contact->update();
            return response()->json(['status'=>'success','contact'=>$contact]);

        },[
            'token' => 'required',
            'phone' => 'required'
        ]);
    }

     private function authenticate(Request $request,Closure $next,$rules=null){
        //  dd();
        $rules=['token' => 'required'];
        $validator = Validator::make($request->header(), $rules);
        if ($validator->fails()) {
            return response()->json([
               'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }
        // else { 
        //     // $rules=["phone"=>'required',"template_name"=>'required',"template_language"=>'required'];
        //     // // $validator = Validator::make($request, $rules);
        //     // // if ($validator->fails()) {
        //     // //     return response()->json([
        //     // //        'status' => 'error',
        //     // //         'errors' => $validator->errors()
        //     // //     ], 400);
        //     // // }
        // }
       

      
        
        if (config('settings.is_demo')) {
            return response()->json([
                'status' => 'error',
                'errors' => "API is disabled in demo"
            ], 400);
        }

        $token = PersonalAccessToken::findToken($request->header("token"));
        
  
        if(!$token){
            return response()->json(['status'=>'error','message'=>'Invalid token']);
        }else{
            
            $user=User::findOrFail($token->tokenable_id);
            Auth::login($user);
            return $next($request);
        }
    }

    public function info()  
    {
        $token = PersonalAccessToken::where('tokenable_id',auth()->user()->id)->where('tokenable_type','App\Models\User')->first();
        $company= $this->getCompany();

        if(!$token||$company->getConfig('whatsapp_webhook_verified','no')!='yes' ||$company->getConfig('whatsapp_settings_done','no')!='yes'){
            return redirect(route('whatsapp.setup'));
         }

       
        //Get old config
        $planText=$company->getConfig('plain_token','');
        
        return view('wpbox::api.info',['token'=>$planText,'company'=>$company]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      

        $items = Campaign::orderBy('id', 'desc')->whereNull('contact_id')->where('is_api', true)->get();
           
        //Regular, bot ant template based bot
        $setup=[
            'usefilter'=>null,
            'title'=>__('API Campaigns'),
            'action_link'=>route('campaigns.create',['type'=>'api']),
            'action_name'=>__('New API Campaign'),
            'action_link2'=>route('api.info'),
            'action_name2'=>__('API Info'),
            'items'=>$items,
            'item_names'=>__('API Campaigns'),
            'webroute_path'=>'campaigns.',
            'fields'=>[],
            'filterFields'=>[],
            'custom_table'=>true,
            'parameter_name'=>'campaigns',
            'parameters'=>count($_GET) != 0,
            'hidePaging'=>true,
        ];


        return view('wpbox::api.index', ['setup' => $setup]);
    
    }
}

