<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('/chat/style.css') }}">
    <style>
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
        
            .chat-container {
                margin: 20px 10px;
            }
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <div class="sidebar">
            <h3>Chat History</h3>
            <ul>
                <li>Chat 1</li>
                <li>Chat 2</li>
                <li>Chat 3</li>
                <li>Chat 4</li>
                <li>Chat 5</li>
            </ul>
        </div>
        <div class="chat-content">
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Type your message...">
                <button id="sendMessageButton">Send</button>
            </div>
        </div>
    </div>
    <input type="hidden" value="" name="recid_conversation" id="recid">
    <input type="hidden" value="{{ $email }}" name="email" id="email">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('chat/chat.js') }}"></script>
</body>

</html>
