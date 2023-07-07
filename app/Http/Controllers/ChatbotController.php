<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ChatbotController extends Controller
{

    public function sendMessage(Request $request)
    {
        // Get the user's input message
        $userMessage = $request->input('message');

        // Store the conversation context in the session
        $sessionEmail = 'dhaffa@vanaroma.com';
        $conversation = Conversation::where('email', $sessionEmail)->first();

        if (!$conversation) {
            $conversation = new Conversation();
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

        // Extract the chatbot's reply from the API response
        $chatbotReply = $response->json('choices.0.message.content');
        // $chatbotReply = $response->json();

        // Append the chatbot's reply to the context
        $context[] = [
            'role' => 'assistant',
            'message' => $chatbotReply,
        ];

        // Update the conversation context in the database
        $conversation->context = json_encode($context);
        $conversation->save();

        // Return the chatbot's reply as a response
        return response()->json([
            'reply' => $chatbotReply,
        ]);
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

        // Check if audio file exists
        if (!$audioFile) {
            return response()->json([
                'error' => 'No audio file provided.',
            ], 400);
        }

        // Prepare the API request
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->attach('audio', $audioFile)->post('https://api.openai.com/v1/speech-to-text');

        // Check if the request was successful
        if ($response->successful()) {
            $transcription = $response->json('text');

            // Return the transcript as a response
            return response()->json([
                'transcript' => $transcription,
            ]);
        } else {
            // Handle the API request error
            $errorMessage = $response->json('error.message', 'An error occurred while processing the audio.');
            return response()->json([
                'error' => $errorMessage,
            ], $response->status());
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