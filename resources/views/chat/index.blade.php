<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('/chat/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    {{-- <style>
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
        
            .chat-container {
                margin: 20px 10px;
            }
        }
    </style> --}}
</head>

<body>
    <div class="chat-container row">
        <div class="sidebar col-md-4">
            <h3>Chat History</h3>
            <div id="chatHistoryList">

            </div>
        </div>
        <div class="chat-content col-md-8">
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input">
                
                <input type="text" id="messageInput" placeholder="Type your message...">
                <button id="microphoneButton">
                    <i class="fa fa-microphone"></i>
                </button>
                <button id="sendMessageButton"><i class="fa fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    <input type="hidden" value="" name="recid_conversation" id="recid">
    <input type="hidden" value="{{ $email }}" name="email" id="email">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/@ffmpeg/core@1.9.1/dist/ffmpeg.min.js"></script> --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ffmpeg/0.11.6/ffmpeg.min.js" integrity="sha512-91IRkhfv1tLVYAdH5KTV+KntIZP/7VzQ9E/qbihXFSj0igeacWWB7bQrdiuaJVMXlCVREL4Z5r+3C4yagAlwEw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    {{-- <script src="{{ asset('chat/chat.js') }}"></script> --}}
<script>

const url = window.location.href;
const recidConversation = extractUUIDFromURL(url) || '{{ $recid }}';

function extractUUIDFromURL(url) {
    const regex = /\/chat\/(\w{8}-(\w{4}-){3}\w{12})/;
    const match = url.match(regex);
    return match ? match[1] : null;
}

let mediaRecorder;
let recordedChunks = [];

const microphoneButton = document.getElementById('microphoneButton');
const sendMessageButton = document.getElementById('sendMessageButton');

// Request access to the microphone
navigator.mediaDevices.getUserMedia({ audio: true })
    .then(function(stream) {
        // Enable the microphone button
        microphoneButton.disabled = false;

        // Create the MediaRecorder
        mediaRecorder = new MediaRecorder(stream);

        // Add data to recordedChunks when available
        mediaRecorder.addEventListener('dataavailable', function(event) {
            recordedChunks.push(event.data);
        });

        // Send the recorded audio to the backend when recording is stopped
        mediaRecorder.addEventListener('stop', function() {
            // Combine recorded chunks into a single blob
            const recordedBlob = new Blob(recordedChunks, { type: 'audio/webm' });

            // Convert the recorded audio to MP3 using ffmpeg.js
            const reader = new FileReader();
            reader.onloadend = function() {
                const ffmpeg = createFFmpeg({ log: true });
                const inputBuffer = reader.result;

                // Initialize ffmpeg.js
                ffmpeg
                    .load()
                    .then(function() {
                        ffmpeg.FS('writeFile', 'input.webm', new Uint8Array(inputBuffer));

                        // Run ffmpeg.js to convert audio to MP3
                        return ffmpeg.run('-i', 'input.webm', 'output.mp3');
                    })
                    .then(function(result) {
                        const outputData = ffmpeg.FS('readFile', 'output.mp3');
                        const mp3Blob = new Blob([outputData.buffer], { type: 'audio/mp3' });

                        // Create a FormData object to send the MP3 blob
                        const formData = new FormData();
                        formData.append('audio', mp3Blob, 'recording.mp3');

                        // Replace 'URL_TO_BACKEND' with the actual URL of your backend endpoint
                        const request = new XMLHttpRequest();
                        request.open('POST', '/api/speech-to-text');
                        request.onreadystatechange = function() {
                            if (request.readyState === 4) {
                                if (request.status === 200) {
                                    const data = JSON.parse(request.responseText);
                                    const transcript = data.transcript;

                                    // Display the transcript in the chat input
                                    document.getElementById('messageInput').value = transcript;
                                } else {
                                    console.error('Error:', request.status);
                                }
                            }
                        };
                        request.send(formData);
                    });
            };
            reader.readAsArrayBuffer(recordedBlob);
        });
    })
    .catch(function(error) {
        console.error('Error accessing microphone:', error);
    });

// Start recording when the microphone button is pressed
microphoneButton.addEventListener('mousedown', function() {
    if (mediaRecorder.state === 'inactive') {
        // Clear the existing recorded chunks
        recordedChunks = [];

        // Start recording
        mediaRecorder.start();

        // Change the microphone button icon
        microphoneButton.innerHTML = '<i class="fa fa-stop"></i>';
    }
});

// Stop recording when the microphone button is released
microphoneButton.addEventListener('mouseup', function() {
    if (mediaRecorder.state === 'recording') {
        // Stop recording
        mediaRecorder.stop();

        // Change the microphone button icon back to the default
        microphoneButton.innerHTML = '<i class="fa fa-microphone"></i>';
    }
});

// Send the message when the send button is clicked
sendMessageButton.addEventListener('click', function() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value;

    // TODO: Send the message to the backend using Ajax

    // Clear the message input
    messageInput.value = '';
});

// Start recording when the microphone button is clicked
microphoneButton.addEventListener('click', function() {
    if (mediaRecorder.state === 'inactive') {
        // Clear the existing chunks
        chunks = [];

        // Start recording
        mediaRecorder.start();

        // Change the microphone button icon
        microphoneButton.innerHTML = '<i class="fa fa-stop"></i>';
    } else if (mediaRecorder.state === 'recording') {
        // Stop recording
        mediaRecorder.stop();

        // Change the microphone button icon back to the default
        microphoneButton.innerHTML = '<i class="fa fa-microphone"></i>';
    }
});

// Send the message when the send button is clicked
sendMessageButton.addEventListener('click', function() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value;

    // TODO: Send the message to the backend using Ajax

    // Clear the message input
    messageInput.value = '';
});

$(document).ready(function () {
    let conversationId; // Variable to store the conversation ID
    $('#recid').val(recidConversation);
    fetchConversationHistory();

    // Function to send a message to the backend
    function sendMessage() {
        const conv_id = $("#recid").val();
        const email = $("#email").val();
        const message = $('#messageInput').val();

        // Display the user's chat in the chat interface
        addChatMessage('user', message);

        // Show the loading animation
        showLoadingAnimation();

        // Make an AJAX request to the backend
        $.ajax({
        url: `/api/chatbot`, // Replace with your backend endpoint
        method: 'POST',
        data: {
            message: message,
            recid_conversation: conv_id, // Pass the conversation ID to the backend
            email: email,
        },
        success: function (response) {
            // Handle the response from the backend
            const reply = response.reply;

            // Hide the loading animation
            hideLoadingAnimation();

            // Display the chatbot's reply in the chat interface
            addChatMessage('assistant', reply);

            // Update the conversation ID if it's returned by the backend
            if (response.conversation_id) {
            conversationId = response.conversation_id;
            }
        },
        error: function (error) {
            // Hide the loading animation
            hideLoadingAnimation();

            // Handle the error response from the backend
            console.log(error);
        },
        });

        // Clear the input field after sending the message
        $('#messageInput').val('');
    }

    // Function to add a chat message to the chat interface
    function addChatMessage(role, message) {
        const chatMessages = $('#chatMessages');
        var formattedMessage = message.replace(/\n/g, '<br>'); // Replace newline characters with HTML line breaks
        const messageContent = $('<div>').addClass('message-content').html(formattedMessage);
        const chatMessage = $('<div>').addClass('chat-message').addClass(role + '-message').append(messageContent);
        chatMessages.append(chatMessage);

        // Scroll to the bottom of the chat messages
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    // Function to show the loading animation
    function showLoadingAnimation() {
        const chatMessages = $('#chatMessages');
        const loadingAnimation = $('<div>').addClass('loading-animation');
        chatMessages.append(loadingAnimation);

        // Scroll to the bottom of the chat messages
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    // Function to hide the loading animation
    function hideLoadingAnimation() {
        $('.loading-animation').remove();
    }

    // Event listener for the send message button
    $('#sendMessageButton').on('click', function () {
        sendMessage();
    });

    // Event listener for pressing Enter key in the message input field
    $('#messageInput').on('keydown', function (event) {
        if (event.keyCode === 13) {
        sendMessage();
        }
    });

    // Function to fetch the conversation history and display it
    function fetchConversationHistory() {
        const recidConversation = $("#recid").val();

        // Make an AJAX request to fetch the conversation history
        $.ajax({
            url: `/api/history-chat`,
            method: 'POST',
            data: {
                recid_conversation: recidConversation
            },
            success: function (response) {
                // Iterate through the chat messages and display them in the chat interface
                
                response.forEach(function (message) {
                    var formattedMessage = message.message.replace(/\n/g, '<br>'); // Replace newline characters with HTML line breaks
                    addChatMessage(message.role, formattedMessage);
                });
            },
            error: function (error) {
                console.error(error);
            }
        });
    }
    
    // Fetch conversation history from the backend
    fetchChatHistory()
    function fetchChatHistory() {
        const email = $("#email").val();

        // Make an AJAX request to the backend API
        $.ajax({
        url: '/api/chat-history',
            type: 'POST',
            dataType: 'json',
            data: {
                email: email
            },
            success: function (data) {
                // Process the retrieved conversation data
                const conversationData = data.data;

                // Update the chat history list
                const chatHistoryList = $('#chatHistoryList');
                chatHistoryList.empty();
                if(!data) {
                    chatHistoryList.html('<h3>No chat found</h3>');
                }

                
                conversationData.forEach(conversation => {

                    const linkDetailChat = ( conversation.recid_conversations !== recidConversation ) ? `<a href="/chat/${conversation.recid_conversations}">${conversation.email} - ${conversation.created_at}</a>` : `<p>${conversation.email} - ${conversation.created_at}</p>`
                    const listItem = $('<li>').html(linkDetailChat); // Replace with the actual property containing the message
                    chatHistoryList.append(listItem);
                });
            },
            error: function (xhr, status, error) {
                console.error('Failed to fetch conversation history:', error);
                // Handle the error appropriately
            }
        });
    }

});
    
</script>
</body>

</html>
