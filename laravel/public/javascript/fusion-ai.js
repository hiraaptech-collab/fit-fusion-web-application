/* ===========================================
   FitFusion Ã¢â‚¬â€œ Fusion-AI Chat Client
   AI-powered fitness assistant interface
   ============================================ */

(function () {
    'use strict';

    // Chat history (in-memory)
    let chatHistory = [];

    // UI element references
    let messagesContainer,
        chatWelcome,
        chatInput,
        sendBtn,
        clearChatBtn;

    // Timeout for API requests (30 seconds)
    const API_TIMEOUT = 30000;

    // Same-origin Laravel endpoint; the provider key is never sent by this script.
    const CHAT_ENDPOINT = '/api/chat';
    const historyEndpoint = document.querySelector('meta[name=fitfusion-history-endpoint]')?.content;
    let conversationId = document.querySelector('#historyConversationId')?.value || null;

    // Flag to prevent concurrent sends
    let isSending = false;

    // Store the pending (failed) message for retry
    let pendingRetry = null;

    // ===========================================
    // Initialization
    // ===========================================
    function initFusionAI() {
        messagesContainer = document.getElementById('chatMessages');
        chatWelcome = document.getElementById('chatWelcome');
        chatInput = document.getElementById('chatInput');
        sendBtn = document.getElementById('sendBtn');
        clearChatBtn = document.getElementById('clearChatBtn');

        if (!messagesContainer || !chatInput || !sendBtn) return;

        bindEvents();
        const storedHistory = document.getElementById('storedAiHistory');
        if (storedHistory) {
            const saved = JSON.parse(storedHistory.textContent);
            saved.forEach(message => { chatHistory.push({ role: message.role, content: message.content }); addMessage(message.role === 'user' ? 'user' : 'ai', message.content, message.source); });
            if (saved.length && chatWelcome) chatWelcome.style.display = 'none';
        }
        // Fit the focused chat to the visible viewport when a phone keyboard opens.
        const viewport = window.visualViewport;
        const panel = document.querySelector('.fusion-ai-container');
        if (panel && 'IntersectionObserver' in window) {
            new IntersectionObserver(entries => {
                document.body.classList.toggle('chat-in-view', entries[0].isIntersecting);
            }, { threshold: 0 }).observe(panel);
        }
        function fitChatViewport() {
            const focused = document.activeElement === chatInput;
            const keyboardOpen = focused && viewport && window.innerHeight - viewport.height > 120;
            document.body.classList.toggle('chat-keyboard-open', Boolean(keyboardOpen));
            if (keyboardOpen && panel && window.matchMedia('(max-width: 767.98px)').matches) {
                panel.style.height = Math.max(180, viewport.height - 24) + 'px';
                panel.style.minHeight = '0';
                panel.scrollIntoView({ block: 'end', behavior: 'instant' });
            } else if (panel) {
                panel.style.height = '';
                panel.style.minHeight = '';
            }
        }
        viewport?.addEventListener('resize', fitChatViewport);
        window.addEventListener('resize', fitChatViewport);
        chatInput.addEventListener('focus', fitChatViewport);
        chatInput.addEventListener('blur', fitChatViewport);
    }

    function bindEvents() {
        // Send button click
        sendBtn.addEventListener('click', () => {
            sendMessage();
        });

        // Textarea input: auto-resize + toggle send button
        chatInput.addEventListener('input', () => {
            autoResizeTextarea();
            updateSendButtonState();
        });

        // Enter to send, Shift+Enter for newline
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Clear chat
        clearChatBtn.addEventListener('click', () => {
            clearChat();
        });

        // Suggested prompts
        document.querySelectorAll('.suggested-prompt').forEach((btn) => {
            btn.addEventListener('click', () => {
                const prompt = btn.getAttribute('data-prompt');
                if (prompt) {
                    chatInput.value = prompt;
                    autoResizeTextarea();
                    updateSendButtonState();
                    sendMessage();
                }
            });
        });
    }

    // ===========================================
    // Message sending
    // ===========================================
    function sendMessage(retryContent) {
        if (isSending) return;

        let content = retryContent;
        if (!content) {
            content = chatInput.value.trim();
        }

        if (!content) return;

        // Prevent empty messages
        if (content.length > 1000) {
            showError('Message is too long. Please keep it under 1000 characters.');
            return;
        }

        // Hide welcome screen once messages start
        if (chatWelcome) {
            chatWelcome.style.display = 'none';
        }

        // Clear input
        chatInput.value = '';
        autoResizeTextarea();
        updateSendButtonState();

        // Add user message to UI and history
        // A retry reuses the unsuccessful turn rather than duplicating it.
        const previousTurn = chatHistory[chatHistory.length - 1];
        const isRetry = retryContent && previousTurn?.role === 'user' && previousTurn.content === content;
        if (!isRetry) {
            addMessage('user', content);
            chatHistory.push({ role: 'user', content: content });
        }

        // Show typing indicator
        const typingEl = showTypingIndicator();

        isSending = true;
        disableInput(true);

        sendToAPI(chatHistory)
            .then((result) => {
                // Remove typing indicator
                if (typingEl) typingEl.remove();

                // Add AI message to UI and history
                addMessage('ai', result.reply, result.source);
                chatHistory.push({ role: 'assistant', content: result.reply });
                pendingRetry = null;
            })
            .catch((error) => {
                // Remove typing indicator
                if (typingEl) typingEl.remove();

                pendingRetry = content;
                const errorMessage = getErrorMessage(error);
                showChatError(errorMessage, content);
            })
            .finally(() => {
                isSending = false;
                enableInput();
                updateSendButtonState();
            });
    }

    // ===========================================
    // API request
    // ===========================================
    async function sendToAPI(messages) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT);

        try {
            const consent = document.getElementById('historyConsent')?.checked;
            const requiresConsent = document.querySelector('meta[name="fitfusion-history-required"]');
            if (historyEndpoint && requiresConsent && !consent) throw new Error('Please allow personal history storage before sending.');
            const saveHistory = Boolean(historyEndpoint && consent);
            const payload = saveHistory
                ? { message: messages[messages.length - 1].content, conversation_id: conversationId, consent: true }
                : { messages: messages };
            const response = await fetch(saveHistory ? historyEndpoint : CHAT_ENDPOINT, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(payload),
                signal: controller.signal
            });

            let data = null;
            try {
                data = await response.json();
            } catch (e) {
                data = null;
            }

            if (!response.ok) {
                const code = data && data.code ? data.code : 'INTERNAL_ERROR';
                const message = data && (data.error || data.message) ? (data.error || data.message) : 'Something went wrong. Please try again.';
                const error = new Error(message);
                error.code = code;
                error.status = response.status;
                throw error;
            }

            if (data && data.conversation_id) {
                conversationId = data.conversation_id;
                if (requiresConsent) window.history.replaceState(null, '', historyEndpoint + '/' + conversationId);
            }
            if (data && typeof data.reply === 'string' && data.reply.trim()) {
                return { reply: data.reply.trim(), source: data.source || 'ai' };
            }

            const emptyError = new Error('The AI returned an empty response. Please try again.');
            emptyError.code = 'EMPTY_RESPONSE';
            throw emptyError;

        } catch (error) {
            if (error.name === 'AbortError') {
                const timeoutError = new Error('The request timed out. Please try again.');
                timeoutError.code = 'TIMEOUT';
                throw timeoutError;
            }

            if (error instanceof TypeError) {
                const connError = new Error('Cannot reach the FitFusion server. Please make sure the Laravel server is running.');
                connError.code = 'NETWORK_ERROR';
                throw connError;
            }

            throw error;
        } finally {
            clearTimeout(timeoutId);
        }
    }

    // ===========================================
    // Message rendering
    // ===========================================
    function addMessage(role, content, source) {
        const row = document.createElement('div');
        row.className = `chat-message ${role}`;

        const avatar = document.createElement('div');
        avatar.className = 'chat-avatar';
        avatar.setAttribute('aria-hidden', 'true');
        avatar.innerHTML = role === 'user'
            ? '<i class="bi bi-person-fill"></i>'
            : '<i class="bi bi-robot"></i>';

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';

        if (role === 'ai') {
            bubble.innerHTML = formatAIResponse(content);
            if (source === 'local') {
                const badge = document.createElement('div');
                badge.className = 'chat-source-badge';
                badge.textContent = 'Offline mode \u2014 AI service unavailable';
                bubble.appendChild(badge);
            }
        } else {
            bubble.textContent = content;
        }

        row.appendChild(avatar);
        row.appendChild(bubble);
        messagesContainer.appendChild(row);

        scrollToBottom();
    }

    function showTypingIndicator() {
        const row = document.createElement('div');
        row.className = 'chat-message ai typing-message';
        row.id = 'typingIndicator';

        const avatar = document.createElement('div');
        avatar.className = 'chat-avatar';
        avatar.setAttribute('aria-hidden', 'true');
        avatar.innerHTML = '<i class="bi bi-robot"></i>';

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.innerHTML = '<div class="typing-indicator" aria-label="Fusion-AI is typing" role="status"><span></span><span></span><span></span></div>';

        row.appendChild(avatar);
        row.appendChild(bubble);
        messagesContainer.appendChild(row);

        scrollToBottom();
        return row;
    }

    function showChatError(message, originalContent) {
        const row = document.createElement('div');
        row.className = 'chat-message ai';

        const avatar = document.createElement('div');
        avatar.className = 'chat-avatar';
        avatar.setAttribute('aria-hidden', 'true');
        avatar.innerHTML = '<i class="bi bi-robot"></i>';

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.innerHTML = `
            <div class="chat-error" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>${escapeHTML(message)}</span>
            </div>
            <button class="chat-retry-btn" type="button" aria-label="Retry sending your message">
                <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
        `;

        const retryBtn = bubble.querySelector('.chat-retry-btn');
        retryBtn.addEventListener('click', () => {
            // Remove this error row first
            row.remove();
            sendMessage(originalContent);
        });

        row.appendChild(avatar);
        row.appendChild(bubble);
        messagesContainer.appendChild(row);

        scrollToBottom();
    }

    function showError(message) {
        // Display momentary error inline (for validation issues)
        const row = document.createElement('div');
        row.className = 'chat-message ai';

        const avatar = document.createElement('div');
        avatar.className = 'chat-avatar';
        avatar.setAttribute('aria-hidden', 'true');
        avatar.innerHTML = '<i class="bi bi-robot"></i>';

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.innerHTML = `<div class="chat-error" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><span>${escapeHTML(message)}</span></div>`;

        row.appendChild(avatar);
        row.appendChild(bubble);
        messagesContainer.appendChild(row);

        scrollToBottom();

        // Auto-remove after 5 seconds
        setTimeout(() => row.remove(), 5000);
    }

    // ===========================================
    // Formatting helpers
    // ===========================================
    function formatAIResponse(text) {
        const escaped = escapeHTML(text);
        const withStrong = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        const lines = withStrong.split(/\n/);
        const html = lines.map((line) => {
            const trimmed = line.trim();
            if (trimmed.startsWith('- ') || trimmed.startsWith('Ã¢â‚¬Â¢ ')) {
                return `<li>${trimmed.replace(/^[-Ã¢â‚¬Â¢]\s+/, '')}</li>`;
            }
            return line;
        }).join('\n');

        let result = html;

        // Wrap consecutive bullet lines into a <ul>
        const listPattern = /(?:<li>.*?<\/li>\n?)+/g;
        result = result.replace(listPattern, (match) => `<ul>${match.trim()}</ul>`);

        // Convert remaining line breaks into <p> paragraphs
        const paragraphs = result.split(/\n/).filter((p) => p.trim() !== '');

        if (paragraphs.length === 0) return '<p>' + result + '</p>';

        return paragraphs.map((p) => {
            if (p.trim().startsWith('<ul>') && p.trim().endsWith('</ul>')) {
                return p;
            }
            return `<p>${p}</p>`;
        }).join('');
    }

    function escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ===========================================
    // Textarea helpers
    // ===========================================
    function autoResizeTextarea() {
        chatInput.style.height = 'auto';
        chatInput.style.height = Math.min(chatInput.scrollHeight, 120) + 'px';
    }

    function updateSendButtonState() {
        const hasText = chatInput.value.trim().length > 0;
        sendBtn.disabled = !hasText || isSending;
    }

    // ===========================================
    // Input enable/disable
    // ===========================================
    function disableInput(disableTextarea) {
        if (disableTextarea) {
            chatInput.setAttribute('disabled', 'disabled');
            chatInput.style.opacity = '0.6';
        }
        sendBtn.disabled = true;
    }

    function enableInput() {
        chatInput.removeAttribute('disabled');
        chatInput.style.opacity = '';
        updateSendButtonState();
        chatInput.focus();
    }

    // ===========================================
    // Scrolling
    // ===========================================
    function scrollToBottom() {
        requestAnimationFrame(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    }

    // ===========================================
    // Clear chat
    // ===========================================
    function clearChat() {
        if (isSending) return;

        // Clear history
        chatHistory = [];
        conversationId = null;
        pendingRetry = null;

        // Remove all messages except the welcome screen
        messagesContainer.querySelectorAll('.chat-message').forEach((msg) => msg.remove());

        // Re-show welcome screen
        if (chatWelcome) {
            chatWelcome.style.display = '';
            messagesContainer.appendChild(chatWelcome);
        }

        // Reset input
        chatInput.value = '';
        autoResizeTextarea();
        updateSendButtonState();
        chatInput.focus();
    }

    // ===========================================
    // Error message mapping
    // ===========================================
    function getErrorMessage(error) {
        const code = error && error.code ? error.code : '';
        const message = error && error.message ? error.message : '';

        switch (code) {
            case 'API_KEY_NOT_CONFIGURED':
                return 'The AI service is not configured yet. Please add your API key in the server configuration.';
            case 'INVALID_REQUEST':
                return 'Sorry, I could not process that request. Please try again.';
            case 'INVALID_API_KEY':
                return 'The AI service could not authenticate. Please check the API key configuration.';
            case 'FORBIDDEN':
                return 'The AI service refused the request. Please try again later.';
            case 'NOT_FOUND':
            case 'UPSTREAM_ERROR':
                return 'Sorry, the AI service is temporarily unavailable. Please try again.';
            case 'MESSAGE_TOO_LARGE':
                return 'That message was too long. Please shorten it and try again.';
            case 'RATE_LIMITED':
                return error.message || 'Too many requests. Please wait a moment and try again.';
            case 'QUOTA_EXCEEDED':
                return 'The AI service quota has been exceeded. Please try again later.';
            case 'EMPTY_RESPONSE':
                return 'The AI returned an empty response. Please try again.';
            case 'TIMEOUT':
                return 'The request timed out. Please check your connection and try again.';
            case 'NETWORK_ERROR':
                return message || 'Sorry, I am having trouble connecting right now. Please try again.';
            case 'INTERNAL_ERROR':
            default:
                return message || 'Sorry, I am having trouble connecting right now. Please try again.';
        }
    }

    // ===========================================
    // Boot
    // ===========================================
    document.addEventListener('DOMContentLoaded', initFusionAI);

})();


