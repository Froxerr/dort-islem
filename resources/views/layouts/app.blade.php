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

@yield('js')

@auth
<!-- Chat Widget Script -->
<script src="{{ asset('assets/js/chat.js') }}"></script>
<!-- Notification System Script -->
<script src="{{ asset('assets/js/notifications.js') }}"></script>
@endauth
</body>
</html>
