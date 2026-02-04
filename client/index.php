<?php
// ============================================
// FreeAi4Me - Chat z lokalnym AI
// ============================================

$NGROK_URL = "https://cyclostomatous-nonbindingly-maricruz.ngrok-free.dev";

// API Proxy - obsługa requestów do AI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api'])) {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? '';

    if (empty($message)) {
        echo json_encode(['error' => 'Brak wiadomości']);
        exit;
    }

    $payload = json_encode([
        'model' => 'local-model',
        'messages' => [
            ['role' => 'user', 'content' => $message]
        ],
        'max_tokens' => 1000,
        'temperature' => 0.7
    ]);

    $ch = curl_init($NGROK_URL . '/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'ngrok-skip-browser-warning: true',  // WAŻNE - omija ekran ostrzeżenia
            'Authorization: Bearer lm-studio'
        ],
        CURLOPT_TIMEOUT => 120
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['error' => 'Błąd połączenia: ' . $error]);
        exit;
    }

    if ($httpCode !== 200) {
        echo json_encode(['error' => 'Błąd API: HTTP ' . $httpCode, 'details' => $response]);
        exit;
    }

    $data = json_decode($response, true);
    $reply = $data['choices'][0]['message']['content'] ?? 'Brak odpowiedzi';

    echo json_encode(['reply' => $reply]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreeAi4Me Chat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .chat-container {
            width: 100%;
            max-width: 600px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .chat-header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .chat-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .status {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #4ade80;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .message.user {
            align-items: flex-end;
        }

        .message.ai {
            align-items: flex-start;
        }

        .message-content {
            max-width: 80%;
            padding: 12px 18px;
            border-radius: 18px;
            line-height: 1.5;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.ai .message-content {
            background: white;
            color: #333;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }

        .message-label {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 4px;
            padding: 0 10px;
        }

        .chat-input {
            display: flex;
            padding: 20px;
            background: white;
            border-top: 1px solid #e2e8f0;
        }

        .chat-input input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input input:focus {
            border-color: #667eea;
        }

        .chat-input button {
            margin-left: 10px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .chat-input button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .chat-input button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .typing {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
        }

        .typing span {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            margin: 0 2px;
            animation: typing 1s infinite;
        }

        .typing span:nth-child(2) { animation-delay: 0.2s; }
        .typing span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .error {
            color: #ef4444;
            background: #fef2f2;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h1><span class="status"></span>FreeAi4Me Chat</h1>
            <p>Mistral 3B na RTX 3060 Ti</p>
        </div>

        <div class="chat-messages" id="messages">
            <div class="message ai">
                <span class="message-label">AI</span>
                <div class="message-content">
                    Cześć! Jestem lokalnym AI działającym na twojej karcie graficznej. W czym mogę pomóc?
                </div>
            </div>
        </div>

        <div class="chat-input">
            <input type="text" id="input" placeholder="Napisz wiadomość..." autocomplete="off">
            <button id="send">Wyślij</button>
        </div>
    </div>

    <script>
        const messagesDiv = document.getElementById('messages');
        const input = document.getElementById('input');
        const sendBtn = document.getElementById('send');

        function addMessage(content, isUser, isError = false) {
            const div = document.createElement('div');
            div.className = `message ${isUser ? 'user' : 'ai'}`;
            div.innerHTML = `
                <span class="message-label">${isUser ? 'Ty' : 'AI'}</span>
                <div class="message-content ${isError ? 'error' : ''}">${escapeHtml(content)}</div>
            `;
            messagesDiv.appendChild(div);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            return div;
        }

        function addTypingIndicator() {
            const div = document.createElement('div');
            div.className = 'message ai';
            div.id = 'typing';
            div.innerHTML = `
                <span class="message-label">AI</span>
                <div class="typing"><span></span><span></span><span></span></div>
            `;
            messagesDiv.appendChild(div);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function removeTypingIndicator() {
            const typing = document.getElementById('typing');
            if (typing) typing.remove();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function sendMessage() {
            const message = input.value.trim();
            if (!message) return;

            input.value = '';
            sendBtn.disabled = true;

            addMessage(message, true);
            addTypingIndicator();

            try {
                const response = await fetch('?api=1', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });

                const data = await response.json();
                removeTypingIndicator();

                if (data.error) {
                    addMessage('Błąd: ' + data.error, false, true);
                } else {
                    addMessage(data.reply, false);
                }
            } catch (error) {
                removeTypingIndicator();
                addMessage('Błąd połączenia: ' + error.message, false, true);
            }

            sendBtn.disabled = false;
            input.focus();
        }

        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        input.focus();
    </script>
</body>
</html>
