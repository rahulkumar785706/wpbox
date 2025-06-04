<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookLog;

class PostmanController extends Controller
{
    public function sendRequest(Request $request)
    {

  
      
        // Validate the incoming request to ensure required fields are present
        $validatedData = $request->validate([
            'phone' => '|string',
            'message' => '|string',
            'sentAt' => '|string',
            'mediaUrl' => 'nullable|url',
            'messageId' => '|string',
            'status' => '|string',
            'customerName' => '|string',
        ]);

        // URL to send the request
        $url = 'https://adissia--newsbox.sandbox.my.salesforce-sites.com/notif/McubeWhatsAppVf';

        // Append validated data as a query string 
        $queryString = http_build_query(['data' => json_encode($validatedData)]);

        // Final URL with query string
        $finalUrl = $url . '?' . $queryString;

        // Send the GET request
        $response = Http::withoutVerifying()->get($finalUrl);

        // Handle the response
        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => "Message Broadcast successful",
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $response->status(),
                'message' => $response->body(),
            ], $response->status());
        }
    }

    public function testWebhook(Request $request){
       
        try{

            Log::info($request->all());

            $this->cron_log(json_encode($request->all()));

            
            $formattedData = [];

            // Loop through the entry array
            foreach ($request->entry as $entry) {
                if(empty($value['messages'])){
                    continue;
                }
                foreach ($entry['changes'] as $change) {

                    $value = $change['value'];

                    if(empty($value['messages'])){
                        continue;
                    }
                    
                    // Extract data from the "messages" array
                    foreach ($value['messages'] as $message) {
                        $formattedData[] = [
                            'phone'        => $message['from'], 
                            'message'      => $message['text']['body'], 
                            'sentAt'       => date('Y-m-d H:i:s', $message['timestamp']),
                            'mediaUrl'     => null,
                            'messageId'    => $message['id'],
                            'status'       => 'received', 
                            'customerName' => $value['contacts'][0]['profile']['name'], // Customer name
                            'countryCode'  => +91
                        ];
                    }
                }
            }


            $res = Http::post('https://adissia--newsbox.sandbox.my.salesforce-sites.com/notif/McubeWhatsAppVf', [
                "data" => $formattedData
            ]);

            Log::info($formattedData);

            $this->cron_log(json_encode($res->json()));

           

        }catch(\Exception $e)
        {
            $response = [
                "status" => "error",
                "msg" => $e->getMessage(),
                "line"  => $e->getLine()
            ];
        }    


    }



}
 