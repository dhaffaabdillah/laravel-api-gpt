<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Conversation;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function fetchConversationHistory(Request $request)
    {
        try {
            $recidConversation = $request->input('recid_conversation');
            $conversation = Conversation::where('recid_conversations', $recidConversation)
                ->orderBy('created_at', 'ASC')
                ->first();

            if ($conversation) {
                $context = json_decode($conversation->context, true);

                // Extract the chat messages from the context
                $messages = array_map(function ($message) {
                    return [
                        'role' => $message['role'],
                        'message' => $message['message'],
                    ];
                }, $context);

                return response()->json($messages);
            }

            return response()->json([]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch conversation history.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function fetchChatHistory(Request $request)
    {
        try {
            $email = $request->input('email');
            $conversation = Conversation::where('email', $email)
                ->orderBy('created_at', 'DESC')
                ->get();
            

            return response()->json(['data' => $conversation]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch conversation history.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function sendMessage(Request $request)
    {
        // Get the user's input message
        $userMessage = StringHelper::repair($request->input('message'));

        // Store the conversation context in the session
        $sessionEmail = $request->input('email');
        $recid_conversation = $request->input('recid_conversation'); // Notes: You must generate uuid in this request
        $conversation = Conversation::where('recid_conversations', $recid_conversation)
                    ->where('email', $sessionEmail)
                    ->first();

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->recid_conversations = $recid_conversation;
            $conversation->email = $sessionEmail;
            $conversation->context = '[]';
            $conversation->save();
        }

        $context = json_decode($conversation->context, true);

        // Append the user's message to the context
        $context[] = [
            'role' => 'user',
            'message' => $userMessage,
        ];

        // Send the conversation context and the user's message to the OpenAI API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => $this->formatPrompt($context),
            // 'max_tokens' => 50, // Adjust as per your requirements
        ]);

        
        // Check if the API call was successful
        if ($response->successful()) {
            // Extract the chatbot's reply from the API response
            $chatbotReply = $response->json('choices.0.message.content');

            // Append the chatbot's reply to the context
            $context[] = [
                'role' => 'assistant',
                'message' => $chatbotReply,
            ];

            // Update the conversation context in the database
            $conversation->context = json_encode($context);
            $conversation->save();

            // Return the response
            return response()->json([
                'reply' => $chatbotReply,
                'conversation_id' => $recid_conversation,
            ]);
        } else {
            // Get the error response from the OpenAI API
            $errorResponse = $response->json();
            return response()->json($errorResponse, $response->status());
        }
    }

    public function trainModels(Request $request)
    {
        $validator = Validator::make($request->all(), 
        [ 
            'purpose' => 'required',
            'file' => 'required|mimes:doc,docx,pdf,txt,json,jsonl|max:2048',
        ]);   
        if(!$validator->fails()) {
            $files = $request->file('file');
             // Store the file in the storage/app/uploads directory
            $filename = $files->getClientOriginalName();
            $path = $files->store('training_file');

            // Optionally, you can get the public URL to the uploaded file
            $url = Storage::url($path);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'multipart/form-data',
            ])->post('https://api.openai.com/v1/files', [
                'purpose' => 'fine-tune',
                // 'file' => "https://vanaroma.sgp1.digitaloceanspaces.com/dataset.jsonl",
                'file' => $files->getRealPath(),
            ]);
    
            return response()->json(['msg' => $response->json()]);
        } else {
            return response()->json(['error'=>$validator->errors()], 401);
        }
        // return response()->json(['msg' => $file]);
    }

    public function trainModel(Request $request)
    {
        // Get the uploaded file from the form request
        $file = $request->file('file-upload');

        // API endpoint URL
        $apiUrl = 'https://api.openai.com/v1/files';

        // API Key
        $apiKey = env('OPENAI_API_KEY');

        // Prepare the payload
        $payload = [
            'purpose' => 'fine-tune',
            'file' => $file
        ];

        // Make the API call using Laravel's HTTP Client
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->attach('file', file_get_contents($file), $file->getClientOriginalName())
        ->post($apiUrl, $payload);

        // Handle the response (you can decode the JSON if needed)
        $result = $response->json();

        // Now you can use $result to work with the API response
        return response()->json($result);
    }
    

    public function analyzeData(Request $request)
    {
        $file = $request->file('file');
        // $file_path = $file->store('uploads');
        // $file_path = $file->getClientOriginalName();
        $file_path = public_path('excel.xlsx');
        // Get the user's input message
        $userMessage = $request->input('message');

        // Store the conversation context in the session
        $sessionEmail = $request->input('email');
        $recid_conversation = $request->input('recid_conversation'); // Notes: You must generate uuid in this request
        $conversation = Conversation::where('recid_conversations', $recid_conversation)
                    ->where('email', $sessionEmail)
                    ->first();

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->recid_conversations = $recid_conversation;
            $conversation->email = $sessionEmail;
            $conversation->context = '[]';
            $conversation->save();
        }

        $context = json_decode($conversation->context, true);

        // Append the user's message to the context
        $context[] = [
            'role' => 'user',
            'message' => $userMessage,
        ];

        // Send the conversation context and the user's message to the OpenAI API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/fine-tunes/analysis', [
            'file' => file_get_contents($file_path ),
        ]);

        
        // Check if the API call was successful
        if ($response->successful()) {
            // Extract the chatbot's reply from the API response
            $chatbotReply = $response->json('analysis');

            // Append the chatbot's reply to the context
            $context[] = [
                'role' => 'assistant',
                'message' => $chatbotReply,
            ];

            // Update the conversation context in the database
            $conversation->context = json_encode($context);
            $conversation->save();

            // Return the response
            return response()->json([
                'reply' => $chatbotReply,
                'conversation_id' => $recid_conversation,
            ]);
        } else {
            // Get the error response from the OpenAI API
            $errorResponse = $response->json();
            return response()->json($errorResponse, $response->status());
        }
    }

    public function analyzeExcel()
    {
        try {
            // Load the Excel file
            $filePath = public_path('excel.xlsx');
            $spreadsheet = IOFactory::load($filePath);
        
            // Select the first worksheet
            $worksheet = $spreadsheet->getActiveSheet();
        
            // Get the data from the worksheet
            $data = $worksheet->toArray();
        
            // Prepare the analysis result variable
            $analysisResult = '';
        
            // Split the data into chunks of a specific size
            $chunkSize = 10; // Adjust as per your requirements
            $dataChunks = array_chunk($data, $chunkSize);
        
            // Iterate through each data chunk and make separate API calls
            foreach ($dataChunks as $chunk) {
                // Prepare the messages array for the API call
                $messages = [];
        
                // Add the user's input message as the first message
                $messages[] = [
                    'role' => 'user',
                    'content' => 'Analyze Excel',
                ];
        
                // Iterate through the data chunk and add them as messages
                foreach ($chunk as $row) {
                    $messages[] = [
                        'role' => 'user',
                        'content' => implode(", ", $row),
                    ];
                }
        
                // Make the API call to OpenAI
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'max_tokens' => 100,
                    'temperature' => 0.5,
                    'top_p' => 1.0,
                    'n' => 1,
                    'stop' => null,
                ]);
        
                // Extract the analysis result from the API response
                $chunkResult = $response->json('choices.0.message.content');
        
                // Append the chunk result to the overall analysis result
                $analysisResult .= $chunkResult;
            }
        
            return $analysisResult;
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
                'code' => $e->getCode(),
            ], 500);
        }
        
    }
   
    
    public function convertSpeechToText(Request $request)
    {
        $audioFile = $request->file('audio');
        $filename = $audioFile->getClientOriginalName();
        // Check if audio file exists
        if (!$audioFile) {
            return response()->json([
                'error' => 'No audio file provided.',
            ], 400);
        }

        $client = new Client();
        $response = $client->post('https://api.openai.com/v1/audio/transcriptions', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($audioFile->getRealPath(), 'r'),
                    'filename' => $filename,
                ],
                [
                    'name' => 'model',
                    'contents' => 'whisper-1',
                ],
            ],
        ]);

        // Check if the request was successful
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            $transcript = $data['text'];

            // Return the transcript as a response
            return response()->json([
                'transcript' => $transcript,
            ]);
        } else {
            // Handle the API request error
            $errorMessage = $response->getReasonPhrase();
            return response()->json([
                'error' => $errorMessage,
            ], $response->getStatusCode());
        }
    }

    private function formatPrompt($context)
    {
        $messages = [];

        foreach ($context as $item) {
            $messages[] = [
                'role' => $item['role'],
                'content' => $item['message']
            ];
        }

        return $messages;
    }
}