const url = window.location.href;
const recidConversation = extractUUIDFromURL(url) || "{{ $recid }}";
console.log(recidConversation);

function extractUUIDFromURL(url) {
  const regex = /\/chat\/(\w{8}-(\w{4}-){3}\w{12})/;
  const match = url.match(regex);
  return match ? match[1] : null;
}

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
    const messageContent = $('<div>').addClass('message-content').text(message);
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
          addChatMessage(message.role, message.message);
        });
      },
      error: function (error) {
        console.error(error);
      }
    });
  }
});
