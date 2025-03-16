<?php
// PHP Backend: Process POST requests to send the input to the Gemini API
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = trim($_POST["message"]);
    if (!$input) {
        echo json_encode(["error" => "Message cannot be empty"]);
        exit;
    }

    
    $api_key = "AIs"; // Replace with your actual API key
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$api_key";
    
    $data = json_encode([
        "contents" => [[
            "parts" => [["text" => $input]]
        ]]
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $decoded_response = json_decode($response, true);
    $bot_message = $decoded_response["candidates"][0]["content"]["parts"][0]["text"] ?? "No response";
    
    echo json_encode(["message" => $bot_message]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AI Help Chatbot Widget</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f5f5f5;
    }
    /* Floating chatbot icon at bottom-right */
    .chatbot-icon {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #007bff;
      color: #fff;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: background 0.3s ease;
      z-index: 1000;
    }
    .chatbot-icon:hover {
      background: #0056b3;
    }
    /* Chat window popup styling */
    .chat-window {
      position: fixed;
      bottom: 90px;
      right: 20px;
      width: 300px;
      max-height: 400px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      overflow: hidden;
      display: none;
      flex-direction: column;
      animation: fadeIn 0.3s ease;
      z-index: 1000;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .chat-window-header {
      background: #007bff;
      color: #fff;
      padding: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .chat-window-header h4 {
      margin: 0;
      font-size: 16px;
    }
    .chat-window-header .close-btn {
      cursor: pointer;
      font-size: 20px;
      font-weight: bold;
    }
    .chat-window-body {
      padding: 10px;
      flex: 1;
      overflow-y: auto;
      background: #f9f9f9;
    }
    .chat-window-footer {
      padding: 10px;
      border-top: 1px solid #ddd;
      background: #fff;
      display: flex;
    }
    .chat-window-footer input {
      flex: 1;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      outline: none;
    }
    .chat-window-footer button {
      margin-left: 10px;
      padding: 8px 12px;
      background: #007bff;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .chat-window-footer button:hover {
      background: #0056b3;
    }
    .message {
      margin: 8px 0;
      padding: 8px;
      border-radius: 5px;
      word-wrap: break-word;
    }
    .message.user {
      background: #d1e7fd;
      text-align: right;
    }
    .message.bot {
      background: #f1f1f1;
      text-align: left;
    }
    .loader {
      border: 4px solid #f3f3f3;
      border-top: 4px solid #007bff;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      animation: spin 1s linear infinite;
      margin: 10px auto;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

  <!-- Floating chatbot icon -->
  <div class="chatbot-icon" id="chatbotIcon">&#128172;</div>
  
  <!-- Chat window popup -->
  <div class="chat-window" id="chatWindow">
    <div class="chat-window-header">
      <h4>How can we help you?</h4>
      <span class="close-btn" id="closeBtn">&times;</span>
    </div>
    <div class="chat-window-body" id="chatBody">
      <p class="message bot">Welcome! Ask about our services.</p>
    </div>
    <div class="chat-window-footer">
      <input type="text" id="chatInput" placeholder="Type your message..." />
      <button id="sendChatBtn">Send</button>
    </div>
  </div>
  
  <script>
    // Get key elements
    const chatbotIcon = document.getElementById('chatbotIcon');
    const chatWindow = document.getElementById('chatWindow');
    const closeBtn = document.getElementById('closeBtn');
    const chatInput = document.getElementById('chatInput');
    const sendChatBtn = document.getElementById('sendChatBtn');
    const chatBody = document.getElementById('chatBody');

    // Toggle chat window visibility
    chatbotIcon.addEventListener('click', () => {
      chatWindow.style.display = 'flex';
    });
    closeBtn.addEventListener('click', () => {
      chatWindow.style.display = 'none';
    });

    // Append message to chat body
    function appendMessage(sender, text) {
      const msgDiv = document.createElement('div');
      msgDiv.classList.add('message', sender);
      msgDiv.textContent = text;
      chatBody.appendChild(msgDiv);
      chatBody.scrollTop = chatBody.scrollHeight;
    }

    // Show a loader while waiting for response
    function showLoader() {
      const loaderDiv = document.createElement('div');
      loaderDiv.className = 'loader';
      loaderDiv.id = 'loader';
      chatBody.appendChild(loaderDiv);
      chatBody.scrollTop = chatBody.scrollHeight;
    }

    // Remove the loader after response is received
    function removeLoader() {
      const loader = document.getElementById('loader');
      if (loader) loader.remove();
    }

    // Send message to PHP backend, which forwards to Gemini API
    function sendMessage() {
      const message = chatInput.value.trim();
      if (!message) return;
      
      // Append the user's message
      appendMessage('user', "You: " + message);
      chatInput.value = "";
      sendChatBtn.disabled = true;
      showLoader();

      // Send the message via POST request
      fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "message=" + encodeURIComponent(message)
      })
      .then(response => response.json())
      .then(data => {
        removeLoader();
        if (data.error) {
          appendMessage('bot', "Error: " + data.error);
        } else {
          appendMessage('bot', "Bot: " + data.message);
        }
        sendChatBtn.disabled = false;
      })
      .catch(error => {
        removeLoader();
        appendMessage('bot', "Error: Unable to fetch response.");
        console.error("Error:", error);
        sendChatBtn.disabled = false;
      });
    }

    // Send message on button click or when Enter key is pressed
    sendChatBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keydown', (e) => {
      if (e.key === "Enter") sendMessage();
    });
  </script>
</body>
</html>
