<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Keşif Haritası - Eğlenceli öğrenme macerası">
    <title>@yield('title', 'Kaşif Haritası')</title>
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
</head>
<body>
<main>
    @yield('content')
</main>

@yield('js')
</body>
</html>
