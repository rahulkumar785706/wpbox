<?php

namespace App\Http\Controllers;
use App\Models\Messagesalesforce;

use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function storeMessage(Request $request)
    {
        // Validate the incoming data
        $validatedData = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
            'sentAt' => 'required|date_format:Y-m-d H:i:s',
            'mediaUrl' => 'nullable|url',
            'messageId' => 'required|string|unique:notifications,message_id',
            'status' => 'required|string',
            'customerName' => 'required|string',
            'countryCode' => 'required|string',
        ]);

       

        // Save the data to the database
        $notification = Messagesalesforce::create([
            'phone' => $validatedData['phone'],
            'message' => $validatedData['message'],
            'sent_at' => $validatedData['sentAt'],
            'media_url' => $validatedData['mediaUrl'],
            'message_id' => $validatedData['messageId'],
            'status' => $validatedData['status'],
            'customer_name' => $validatedData['customerName'],
            'country_code' => $validatedData['countryCode'],
            
        ]);
        dd($request);
        // Return a response
        return response()->json([
            'success' => true,
            'message' => 'Message stored successfully!',
            'data' => $notification,
        ], 201);
    }
    
}
