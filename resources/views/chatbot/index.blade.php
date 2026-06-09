@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-6">
    <!-- Chat Container -->
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-blue-500">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">AI Assistant</h2>
                        <p class="text-xs text-gray-500">Powered by Gemini AI</p>
                    </div>
                </div>
                <button onclick="startNewChat()" class="rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100">
                    New Chat
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div id="chat-messages" class="h-[500px] overflow-y-auto px-6 py-4" style="overflow-y: auto; scroll-behavior: smooth;">
            <!-- Welcome Message -->
            <div id="welcome-screen" class="py-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-500 to-blue-500">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="mb-2 text-xl font-semibold text-gray-900">How can I help?</h3>
                <p class="text-sm text-gray-500">Ask me about products, inventory, or anything else</p>
                
                <div class="mx-auto mt-6 grid max-w-md gap-2">
                    <button onclick="sendMessage('What products are available?')" class="rounded-lg border border-gray-200 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                        📦 What products are available?
                    </button>
                    <button onclick="sendMessage('How much is Coca-Cola?')" class="rounded-lg border border-gray-200 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                        💰 How much is Coca-Cola?
                    </button>
                    <button onclick="sendMessage('Do you have Pepsi in stock?')" class="rounded-lg border border-gray-200 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                        🥤 Do you have Pepsi in stock?
                    </button>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div id="messages-container" class="text-gray-700"></div>
        </div>

        <!-- Input Form -->
        <div class="border-t border-gray-200 px-6 py-4">
            <form id="chat-form">
                @csrf
                <input type="hidden" name="conversation_id" id="conversation-id" value="">
                
                <div class="flex gap-2">
                    <textarea id="message-input" 
                              name="message"
                              rows="1"
                              placeholder="Type a message..."
                              class="flex-1 resize-none rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                              style="min-height: 42px; max-height: 120px;"></textarea>
                    <button type="submit" 
                            id="send-button"
                            class="rounded-lg bg-purple-600 px-4 py-2 text-sm text-white hover:bg-purple-700 disabled:opacity-50">
                        Send
                    </button>
                </div>
                <p class="mt-2 text-center text-xs text-gray-400">
                    Press Enter to send, Shift + Enter for new line
                </p>
            </form>
        </div>
    </div>
    
    <!-- Sidebar for recent chats -->
    <div id="sidebar" class="fixed left-0 top-0 hidden h-full w-64 border-r border-gray-200 bg-white p-4 shadow-lg">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Recent Chats</h3>
            <button onclick="toggleSidebar()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div id="conversations-list" class="space-y-1">
            <div class="text-center text-sm text-gray-500">No conversations</div>
        </div>
    </div>
    
    <!-- Toggle sidebar button -->
    <button onclick="toggleSidebar()" class="fixed bottom-6 left-6 rounded-full bg-purple-600 p-3 text-white shadow-lg hover:bg-purple-700">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
</div>

<script>
    let currentConversationId = null;
    let isSending = false;
    let conversations = [];
    let currentTypingMessage = null;
    let typingInterval = null;

    const textarea = document.getElementById('message-input');
    
    // Auto-resize textarea
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Enter to send
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').dispatchEvent(new Event('submit'));
        }
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('hidden');
    }

    // Load conversations from localStorage
    function loadConversations() {
        const saved = localStorage.getItem('chat_conversations');
        if (saved) {
            conversations = JSON.parse(saved);
            renderConversationsList();
        }
    }

    function saveConversations() {
        localStorage.setItem('chat_conversations', JSON.stringify(conversations));
        renderConversationsList();
    }

    function renderConversationsList() {
        const container = document.getElementById('conversations-list');
        if (!container) return;
        
        if (conversations.length === 0) {
            container.innerHTML = '<div class="text-center text-sm text-gray-500 py-4">No conversations</div>';
            return;
        }
        
        container.innerHTML = conversations.map(conv => `
            <button onclick="loadConversation('${conv.id}')" 
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <div class="font-medium truncate">${escapeHtml(conv.title || 'New Chat')}</div>
                <div class="text-xs text-gray-400 mt-0.5">${new Date(conv.updatedAt).toLocaleDateString()}</div>
            </button>
        `).join('');
    }

    function saveCurrentConversation(userMessage, assistantResponse) {
        const messages = getCurrentMessages();
        
        if (!currentConversationId) {
            currentConversationId = 'conv_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            conversations.unshift({
                id: currentConversationId,
                title: userMessage.substring(0, 30) + (userMessage.length > 30 ? '...' : ''),
                messages: [],
                createdAt: new Date().toISOString(),
                updatedAt: new Date().toISOString()
            });
        }
        
        const conversation = conversations.find(c => c.id === currentConversationId);
        if (conversation) {
            conversation.messages = messages;
            conversation.updatedAt = new Date().toISOString();
            saveConversations();
        }
    }

    function getCurrentMessages() {
        const messages = [];
        const messageDivs = document.querySelectorAll('#messages-container > div');
        
        messageDivs.forEach(div => {
            const isUser = div.classList.contains('justify-end');
            const contentEl = div.querySelector('.message-text');
            if (contentEl) {
                messages.push({
                    role: isUser ? 'user' : 'assistant',
                    content: contentEl.innerText,
                    timestamp: new Date().toISOString()
                });
            }
        });
        
        return messages;
    }

    async function loadConversation(conversationId) {
        // Stop any ongoing typing animation
        if (typingInterval) {
            clearInterval(typingInterval);
        }
        
        const conversation = conversations.find(c => c.id === conversationId);
        if (!conversation) return;
        
        currentConversationId = conversationId;
        document.getElementById('conversation-id').value = conversationId;
        
        const messagesContainer = document.getElementById('messages-container');
        const welcomeScreen = document.getElementById('welcome-screen');
        messagesContainer.innerHTML = '';
        welcomeScreen.classList.add('hidden');
        
        if (conversation.messages && conversation.messages.length > 0) {
            for (const msg of conversation.messages) {
                if (msg.role === 'user') {
                    addUserMessage(msg.content, false);
                } else {
                    // Add assistant message without animation for loaded conversations
                    await addAssistantMessageInstant(msg.content, false);
                }
            }
        }
        
        scrollToBottom();
        toggleSidebar();
    }

    function startNewChat() {
        // Stop any ongoing typing animation
        if (typingInterval) {
            clearInterval(typingInterval);
        }
        
        currentConversationId = null;
        document.getElementById('conversation-id').value = '';
        
        const messagesContainer = document.getElementById('messages-container');
        const welcomeScreen = document.getElementById('welcome-screen');
        messagesContainer.innerHTML = '';
        welcomeScreen.classList.remove('hidden');
        
        document.getElementById('message-input').value = '';
        document.getElementById('message-input').style.height = 'auto';
    }

    function sendMessage(message) {
        document.getElementById('message-input').value = message;
        document.getElementById('chat-form').dispatchEvent(new Event('submit'));
    }

    function addUserMessage(content, scroll = true) {
        const messagesContainer = document.getElementById('messages-container');
        const welcomeScreen = document.getElementById('welcome-screen');
        welcomeScreen.classList.add('hidden');
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex justify-end mb-3';
        messageDiv.innerHTML = `
            <div class="max-w-[80%] rounded-2xl rounded-tr-sm bg-purple-600 px-4 py-2 shadow-sm">
                <div class="message-text text-sm whitespace-pre-wrap text-white">${escapeHtml(content)}</div>
            </div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        if (scroll) scrollToBottom();
    }

    // Type message character by character like a real person
    async function typeMessage(content, messageDiv, contentDiv) {
        let index = 0;
        const formattedContent = formatText(content);
        const plainText = content;
        
        return new Promise((resolve) => {
            typingInterval = setInterval(() => {
                if (index < formattedContent.length) {
                    // Show formatted HTML character by character
                    const currentChar = formattedContent.charAt(index);
                    const currentPlainChar = plainText.charAt(index);
                    
                    if (currentChar === '<') {
                        // Handle HTML tags - add the whole tag at once
                        let tagEnd = formattedContent.indexOf('>', index);
                        if (tagEnd !== -1) {
                            const fullTag = formattedContent.substring(index, tagEnd + 1);
                            contentDiv.innerHTML += fullTag;
                            index = tagEnd + 1;
                        } else {
                            contentDiv.innerHTML += currentChar;
                            index++;
                        }
                    } else {
                        contentDiv.innerHTML += currentChar;
                        index++;
                    }
                    
                    scrollToBottom();
                } else {
                    clearInterval(typingInterval);
                    typingInterval = null;
                    resolve();
                }
            }, 15); // 15ms per character for natural typing speed
        });
    }

    // Add assistant message with typing animation
    async function addAssistantMessage(content, scroll = true) {
        const messagesContainer = document.getElementById('messages-container');
        const welcomeScreen = document.getElementById('welcome-screen');
        welcomeScreen.classList.add('hidden');
        
        // Create message container
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex gap-2 mb-3';
        messageDiv.innerHTML = `
            <div class="flex-shrink-0">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-blue-500">
                    <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <div class="rounded-2xl rounded-tl-sm bg-gray-100 px-4 py-2 shadow-sm">
                    <div class="message-text text-sm whitespace-pre-wrap leading-relaxed text-gray-900"></div>
                </div>
            </div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        
        const contentDiv = messageDiv.querySelector('.message-text');
        
        // Start typing animation
        await typeMessage(content, messageDiv, contentDiv);
        
        if (scroll) scrollToBottom();
    }

    // Add assistant message instantly (for loaded conversations)
    async function addAssistantMessageInstant(content, scroll = true) {
        const messagesContainer = document.getElementById('messages-container');
        const welcomeScreen = document.getElementById('welcome-screen');
        welcomeScreen.classList.add('hidden');
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex gap-2 mb-3';
        messageDiv.innerHTML = `
            <div class="flex-shrink-0">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-blue-500">
                    <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <div class="rounded-2xl rounded-tl-sm bg-gray-100 px-4 py-2 shadow-sm">
                    <div class="message-text text-sm whitespace-pre-wrap leading-relaxed text-gray-900">${formatText(content)}</div>
                </div>
            </div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        if (scroll) scrollToBottom();
    }

    function formatText(content) {
        if (!content) return '';
        
        // Bold text with purple color for visibility
        let formatted = content.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-purple-600">$1</strong>');
        
        // Line breaks
        formatted = formatted.replace(/\n/g, '<br>');
        
        // Bullet points
        formatted = formatted.replace(/^[•\-]\s+(.*?)(<br>|$)/gm, '<li class="ml-4 text-gray-700">$1</li>');
        if (formatted.includes('<li')) {
            formatted = formatted.replace(/(<li.*?<\/li>)/gs, '<ul class="list-disc mt-1 mb-1">$1</ul>');
        }
        
        return formatted;
    }

    function scrollToBottom() {
        const container = document.getElementById('chat-messages');
        container.scrollTop = container.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Handle form submission
    document.getElementById('chat-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (isSending) return;
        
        const messageInput = document.getElementById('message-input');
        const message = messageInput.value.trim();
        
        if (!message) return;
        
        addUserMessage(message);
        messageInput.value = '';
        messageInput.style.height = 'auto';
        
        const sendButton = document.getElementById('send-button');
        sendButton.disabled = true;
        isSending = true;
        
        // Add loading indicator
        const messagesContainer = document.getElementById('messages-container');
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'flex gap-2 mb-3';
        loadingDiv.id = 'loading-indicator';
        loadingDiv.innerHTML = `
            <div class="flex-shrink-0">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-blue-500">
                    <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <div class="rounded-2xl rounded-tl-sm bg-gray-100 px-4 py-2 shadow-sm">
                    <div class="flex items-center gap-1.5">
                        <div class="h-2 w-2 rounded-full bg-purple-500 animate-bounce" style="animation: bounce 0.8s infinite;"></div>
                        <div class="h-2 w-2 rounded-full bg-purple-500 animate-bounce" style="animation: bounce 0.8s infinite 0.2s;"></div>
                        <div class="h-2 w-2 rounded-full bg-purple-500 animate-bounce" style="animation: bounce 0.8s infinite 0.4s;"></div>
                        <span class="ml-2 text-xs text-gray-500">AI is thinking...</span>
                    </div>
                </div>
            </div>
        `;
        messagesContainer.appendChild(loadingDiv);
        scrollToBottom();
        
        try {
            const formData = new FormData();
            formData.append('message', message);
            
            if (currentConversationId) {
                formData.append('conversation_id', currentConversationId);
            }
            
            const response = await fetch('/chat-bot', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const data = await response.json();
            loadingDiv.remove();
            
            if (data.success) {
                if (data.conversation_id) {
                    currentConversationId = data.conversation_id;
                    document.getElementById('conversation-id').value = currentConversationId;
                }
                
                let responseText = '';
                
                if (typeof data.response === 'string') {
                    responseText = data.response;
                } else if (data.response && typeof data.response === 'object') {
                    responseText = data.response.text || 
                                   data.response.response || 
                                   data.response.content ||
                                   (data.response.messages && data.response.messages[0]?.content) ||
                                   'How can I help you?';
                } else {
                    responseText = 'I received your message. How can I help?';
                }
                
                responseText = responseText.replace(/\[object Object\]/g, '');
                
                // Show response with typing animation
                await addAssistantMessage(responseText);
                saveCurrentConversation(message, responseText);
            } else {
                await addAssistantMessage('Sorry, I encountered an error. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            loadingDiv.remove();
            await addAssistantMessage('Network error. Please check your connection.');
        } finally {
            sendButton.disabled = false;
            isSending = false;
            messageInput.focus();
        }
    });

    // Add bounce animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes bounce {
            0%, 100% { transform: translateY(0); opacity: 0.4; }
            50% { transform: translateY(-5px); opacity: 1; }
        }
        .animate-bounce {
            animation: bounce 0.8s infinite;
        }
    `;
    document.head.appendChild(style);

    // Load conversations on page load
    loadConversations();
</script>
@endsection