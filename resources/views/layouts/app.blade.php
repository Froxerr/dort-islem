<!DOCTYPE html>
<html lang="tr">
<head>
    @yield('head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Keşif Haritası - Eğlenceli öğrenme macerası">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    <title>@yield('title', 'Kaşif Haritası')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            overflow-x: hidden;
        }

        main {
            min-height: 100vh;
        }
    </style>
    @yield('css')

    @auth
    <!-- Chat Widget Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/chat.css') }}">
    <!-- Notification System Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
    <!-- Pusher JS -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        window.pusherKey = "{{ env('PUSHER_APP_KEY') }}";
        window.pusherCluster = "{{ env('PUSHER_APP_CLUSTER') }}";
    </script>
    @endauth
</head>
<body>
<main>
    @yield('content')
</main>
@auth
    <div class="chat-widget">
        <button class="chat-toggle" id="chatToggle">
            💬
            <span class="unread-badge" id="unreadBadge" style="display: none;">0</span>
        </button>

        <div class="chat-container" id="chatContainer">
            <div class="chat-header">
                <h3>Arkadaşlarım</h3>
                <button class="chat-close" id="chatClose">✕</button>
            </div>

            <div class="chat-body">
                <div class="friends-list" id="friendsList">
                </div>

                <div class="chat-window" id="chatWindow">
                    <div class="chat-window-header">
                        <button class="back-to-friends" id="backToFriends">←</button>
                        <div class="current-friend-avatar" id="currentFriendAvatar"></div>
                        <div class="current-friend-name" id="currentFriendName"></div>
                    </div>

                    <div class="messages-container" id="messagesContainer">
                    </div>

                    <div class="typing-indicator" id="typingIndicator" style="display: none;">
                        <div class="message-avatar" id="typingAvatar"></div>
                        <div class="typing-text">
                            yazıyor
                            <div class="typing-dots">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>

                    <div class="message-input-container">
                        <form class="message-input-form" id="messageForm">
                        <textarea
                            class="message-input"
                            id="messageInput"
                            placeholder="Mesajınızı yazın..."
                            rows="1"
                        ></textarea>
                            <button type="submit" class="send-button" id="sendButton">
                                ➤
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endauth

@yield('js')

@auth
<!-- Chat Widget Script -->
@vite(['resources/js/app.js'])
<!-- Notification System Script -->
<script src="{{ asset('assets/js/notifications.js') }}"></script>
@endauth
</body>
</html>
