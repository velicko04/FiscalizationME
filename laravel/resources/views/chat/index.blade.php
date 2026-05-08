<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat - FiscalizationME</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f9fafb;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 24px;
            gap: 12px;
        }
        .chat-header {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }
        .provider-bar {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .provider-btn {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e5e7eb;
            background: white;
            color: #374151;
        }
        .provider-btn.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        .provider-label {
            font-size: 12px;
            color: #9ca3af;
        }
        .prompt-panel {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
        }
        .prompt-panel-title {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 10px;
        }
        .prompt-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .prompt-chip {
            min-height: 38px;
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            color: #374151;
            font-size: 12px;
            text-align: left;
            cursor: pointer;
        }
        .prompt-chip:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: #3730a3;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
        }
        .message {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .message.user { flex-direction: row-reverse; }
        .message-bubble {
            max-width: 70%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        .user .message-bubble {
            background: #6366f1;
            color: white;
            border-bottom-right-radius: 4px;
        }
        .assistant .message-bubble {
            background: #f3f4f6;
            color: #111827;
            border-bottom-left-radius: 4px;
        }
        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .user .message-avatar { background: #6366f1; color: white; }
        .assistant .message-avatar { background: #e5e7eb; }
        .message-stats {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 4px;
            padding-left: 42px;
        }
        .message-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .message-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 8px;
            background: #111827;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            font-family: inherit;
        }
        .message-action:hover { background: #374151; }
        .message-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .message-action.primary {
            background: #16a34a;
        }
        .message-action.primary:hover {
            background: #15803d;
        }
        .message-action.secondary {
            background: white;
            color: #374151;
            border-color: #d1d5db;
        }
        .message-action.secondary:hover {
            background: #f9fafb;
        }
        .message-bubble .message-action {
            white-space: normal;
        }
        .chat-input-bar {
            display: flex;
            gap: 8px;
        }
        .chat-input-bar input {
            flex: 1;
            height: 44px;
            padding: 0 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .chat-input-bar input:focus { border-color: #6366f1; }
        .chat-input-bar button {
            height: 44px;
            padding: 0 20px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .chat-input-bar button:hover { background: #4f46e5; }
        .chat-input-bar button:disabled { background: #a5b4fc; cursor: not-allowed; }
        @media (max-width: 700px) {
            .prompt-grid { grid-template-columns: 1fr; }
            .message-bubble { max-width: 82%; }
        }
    </style>
</head>
<body>
@include('partials.admin-navbar')

<div class="chat-container">
    <div class="chat-header">💬 FiscalizationME Assistant</div>

    <div class="provider-bar">
        <button class="provider-btn active" id="btn-ollama" onclick="setProvider('ollama')">Ollama</button>
        <button class="provider-btn" id="btn-apple" onclick="setProvider('apple')">Apple Intelligence</button>
        <button class="provider-btn" id="btn-gemini" onclick="setProvider('gemini')">Gemma</button>
        <span class="provider-label" id="provider-label">Ollama aktivan</span>
    </div>

    <div class="prompt-panel">
        <div class="prompt-panel-title">Predlozi upita koje možemo raditi jedan po jedan</div>
        <div class="prompt-grid">
            <button type="button" class="prompt-chip" data-prompt="Napravi ugovor">Napravi ugovor</button>
            <button type="button" class="prompt-chip" data-prompt="Napravi fakturu za ugovor">Napravi fakturu za ugovor</button>
            <button type="button" class="prompt-chip" data-prompt="Prikaži mi ugovor">Vidi ugovor</button>
            <button type="button" class="prompt-chip" data-prompt="Prikaži stavke ugovora">Vidi stavke ugovora</button>
            <button type="button" class="prompt-chip" data-prompt="Vidi fakture za ugovor">Vidi fakture za ugovor</button>
            <button type="button" class="prompt-chip" data-prompt="Prikaži nefiskalizovane fakture.">Nefiskalizovane fakture</button>
            <button type="button" class="prompt-chip" data-prompt="Daj mi PDF zadnje fakture za ugovor">PDF fakture</button>
            <button type="button" class="prompt-chip" data-prompt="Pošalji zadnju fakturu za ugovor na mejl">Pošalji fakturu mejlom</button>
        </div>
    </div>

    <div class="chat-messages" id="messages">
        <div class="message assistant">
            <div class="message-avatar">🤖</div>
            <div class="message-bubble">Zdravo! Izaberi jedan od predloga iznad ili napiši svoj upit.</div>
        </div>
    </div>

    <div class="chat-input-bar">
        <input type="text" id="user-input" placeholder="Postavi pitanje..." autocomplete="off">
        <button id="send-btn">Pošalji</button>
    </div>
</div>

<script>
var currentProvider = 'ollama';
var chatHistory = [];
var isStreaming = false;

var input = document.getElementById('user-input');
var sendBtn = document.getElementById('send-btn');
var messages = document.getElementById('messages');
var promptChips = document.querySelectorAll('.prompt-chip');

function setProvider(p) {
    currentProvider = p;
    document.getElementById('btn-ollama').classList.toggle('active', p === 'ollama');
    document.getElementById('btn-apple').classList.toggle('active', p === 'apple');
    document.getElementById('btn-gemini').classList.toggle('active', p === 'gemini');
    document.getElementById('provider-label').textContent = 
    p === 'ollama' ? 'Ollama aktivan' : 
    p === 'apple' ? 'Apple Intelligence aktivan' : 
    'Gemma aktivan';
}

function scrollToBottom() {
    messages.scrollTop = messages.scrollHeight;
}

function addMessage(role, content) {
    var wrapper = document.createElement('div');
    wrapper.className = 'message-wrapper';

    var div = document.createElement('div');
    div.className = 'message ' + role;
    div.innerHTML =
        '<div class="message-avatar">' + (role === 'user' ? '👤' : '🤖') + '</div>' +
        '<div class="message-bubble">' + content + '</div>';

    wrapper.appendChild(div);
    messages.appendChild(wrapper);
    scrollToBottom();
    return { bubble: div.querySelector('.message-bubble'), wrapper: wrapper };
}

function addStats(wrapper, stats) {
    var statsDiv = document.createElement('div');
    statsDiv.className = 'message-stats';
    var parts = [];
    if (stats.tokens) parts.push(stats.tokens + ' tokena');
    parts.push(stats.time_s + 's');
    parts.push(stats.provider === 'apple' ? ' Apple Intelligence' : (stats.provider === 'gemini' ? ' Gemma' : ' Ollama'));
    statsDiv.textContent = parts.join(' · ');
    wrapper.appendChild(statsDiv);
}

function addDownloadActionToBubble(bubble, url, label) {
    addQuickActionsToBubble(bubble, [{
        label: label || 'Preuzmi',
        url: url,
        style: 'primary'
    }]);
}

function addQuickActionsToBubble(bubble, actions) {
    if (!Array.isArray(actions) || actions.length === 0) return;

    var actionsWrap = document.createElement('div');
    actionsWrap.className = 'message-actions';

    actions.forEach(function(action) {
        var label = action.label || 'Akcija';
        var style = action.style ? ' ' + action.style : '';

        if (action.url) {
            var link = document.createElement('a');
            link.className = 'message-action' + style;
            link.href = action.url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = label;
            actionsWrap.appendChild(link);
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'message-action' + style;
        button.textContent = label;
        button.addEventListener('click', function() {
            if (action.prefill) {
                input.value = action.prefill;
                input.focus();
                return;
            }

            if (action.message) {
                actionsWrap.querySelectorAll('button').forEach(function(item) {
                    item.disabled = true;
                });
                input.value = action.message;
                sendMessage();
            }
        });
        actionsWrap.appendChild(button);
    });

    bubble.appendChild(actionsWrap);
}

function sendMessage() {
    var message = input.value.trim();
    if (!message || isStreaming) return;

    isStreaming = true;
    sendBtn.disabled = true;
    input.value = '';

    addMessage('user', message);
    chatHistory.push({ role: 'user', content: message });

    var result = addMessage('assistant', '...');
    var bubble = result.bubble;
    var wrapper = result.wrapper;

    fetch('/chat/stream', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            message: message,
            history: chatHistory.slice(0, -1),
            provider: currentProvider
        })
    })
    .then(function(res) {
        if (!res.ok) {
            throw new Error('HTTP ' + res.status);
        }
        return res.json();
    })
    .then(function(data) {
        bubble.textContent = data.response || '';
        if (data.download_url) addDownloadActionToBubble(bubble, data.download_url, data.download_label);
        if (data.quick_actions) addQuickActionsToBubble(bubble, data.quick_actions);
        if (data.stats) addStats(wrapper, data.stats);
        chatHistory.push({ role: 'assistant', content: data.response || '' });
        isStreaming = false;
        sendBtn.disabled = false;
        scrollToBottom();
    })
    .catch(function(error) {
        bubble.textContent = 'Greška pri komunikaciji: ' + error.message;
        isStreaming = false;
        sendBtn.disabled = false;
    });
}

sendBtn.addEventListener('click', sendMessage);
input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') sendMessage();
});

promptChips.forEach(function(chip) {
    chip.addEventListener('click', function() {
        input.value = chip.dataset.prompt;
        input.focus();
    });
});
</script>
</body>
</html>
