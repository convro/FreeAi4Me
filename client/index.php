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
            'ngrok-skip-browser-warning: true',
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FreeAi4Me Chat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-tertiary: #1a1a24;
            --bg-hover: #22222e;
            --border-color: #2a2a3a;
            --text-primary: #ffffff;
            --text-secondary: #a0a0b0;
            --text-muted: #606070;
            --accent: #6366f1;
            --accent-hover: #818cf8;
            --accent-glow: rgba(99, 102, 241, 0.3);
            --success: #22c55e;
            --error: #ef4444;
            --error-bg: rgba(239, 68, 68, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent) 0%, #a855f7 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .header-title h1 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .header-title p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-tertiary);
            padding: 8px 14px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            50% { opacity: 0.8; box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
        }

        /* Chat Area */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            overflow: hidden;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* Messages */
        .message {
            display: flex;
            gap: 12px;
            max-width: 85%;
            animation: messageIn 0.3s ease;
        }

        @keyframes messageIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.ai {
            align-self: flex-start;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .message.user .message-avatar {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
        }

        .message.ai .message-avatar {
            background: linear-gradient(135deg, var(--accent) 0%, #a855f7 100%);
        }

        .message-bubble {
            padding: 14px 18px;
            border-radius: 16px;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .message.user .message-bubble {
            background: var(--accent);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.ai .message-bubble {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-bottom-left-radius: 4px;
        }

        .message-bubble.error {
            background: var(--error-bg);
            border: 1px solid var(--error);
            color: var(--error);
        }

        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            gap: 5px;
            padding: 14px 18px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            border-bottom-left-radius: 4px;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: var(--text-muted);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); background: var(--text-muted); }
            30% { transform: translateY(-8px); background: var(--accent); }
        }

        /* Input Area */
        .input-container {
            padding: 16px 20px 24px;
            background: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            flex-shrink: 0;
        }

        .input-wrapper {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            gap: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 8px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-wrapper:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .input-wrapper input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 16px;
            font-size: 1rem;
            color: var(--text-primary);
            font-family: inherit;
            outline: none;
        }

        .input-wrapper input::placeholder {
            color: var(--text-muted);
        }

        .send-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .send-btn:hover:not(:disabled) {
            background: var(--accent-hover);
        }

        .send-btn:active:not(:disabled) {
            transform: scale(0.98);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .send-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 12px 16px;
            }

            .header-title h1 {
                font-size: 1rem;
            }

            .status-badge {
                padding: 6px 10px;
            }

            .status-text {
                display: none;
            }

            .chat-messages {
                padding: 16px;
                gap: 12px;
            }

            .message {
                max-width: 90%;
            }

            .message-avatar {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            .message-bubble {
                padding: 12px 14px;
                font-size: 0.9rem;
            }

            .input-container {
                padding: 12px 16px 20px;
            }

            .input-wrapper {
                padding: 6px;
            }

            .input-wrapper input {
                padding: 10px 12px;
                font-size: 0.95rem;
            }

            .send-btn {
                padding: 10px 16px;
            }

            .send-btn span {
                display: none;
            }
        }

        /* Extra small screens */
        @media (max-width: 400px) {
            .logo {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }

            .header-left {
                gap: 10px;
            }
        }

        /* Safe area for notched phones */
        @supports (padding: max(0px)) {
            .input-container {
                padding-bottom: max(24px, env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <div class="logo">⚡</div>
            <div class="header-title">
                <h1>FreeAi4Me</h1>
                <p>Mistral 3B • RTX 3060 Ti</p>
            </div>
        </div>
        <div class="status-badge">
            <div class="status-dot"></div>
            <span class="status-text">Online</span>
        </div>
    </header>

    <main class="chat-container">
        <div class="chat-messages" id="messages">
            <div class="message ai">
                <div class="message-avatar">🤖</div>
                <div class="message-bubble">
                    Cześć! Jestem lokalnym AI działającym na twojej karcie graficznej. W czym mogę pomóc?
                </div>
            </div>
        </div>
    </main>

    <div class="input-container">
        <div class="input-wrapper">
            <input type="text" id="input" placeholder="Napisz wiadomość..." autocomplete="off">
            <button class="send-btn" id="send">
                <span>Wyślij</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
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
                <div class="message-avatar">${isUser ? '👤' : '🤖'}</div>
                <div class="message-bubble ${isError ? 'error' : ''}">${escapeHtml(content)}</div>
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
                <div class="message-avatar">🤖</div>
                <div class="typing-indicator"><span></span><span></span><span></span></div>
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
