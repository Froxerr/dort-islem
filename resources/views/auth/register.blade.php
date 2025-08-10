@extends('layouts.app')

@section('title', 'Kaşif Ol | Maceraya Başla')

@section('css')
    <link rel="stylesheet" href="{{asset('assets/css/anasayfa.css')}}">
    <style>
        .auth-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 5;
            margin: 2rem;
        }

        .form-title {
            color: #4338ca;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.2rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.3rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 1rem;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-input:focus {
            border-color: #4338ca;
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
            transform: translateY(-2px);
        }

        .auth-button {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(45deg, #1e293b, #3730a3, #4338ca);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(67, 56, 202, 0.3);
            margin-top: 1rem;
        }

        .auth-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 56, 202, 0.4);
        }

        .auth-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6b7280;
        }

        .auth-link a {
            color: #4338ca;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .auth-link a:hover {
            color: #3730a3;
            text-decoration: underline;
        }

        .error-message {
            color: #ef4444;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .auth-form {
                margin: 1rem;
                padding: 2rem;
                max-width: 90%;
            }

            .form-title {
                font-size: 1.8rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-group {
                margin-bottom: 1.2rem;
            }
        }
    </style>
@endsection

@section('content')
<div class="nature-bg">
    <div class="stars"></div>
    <div class="twinkling"></div>

    <div class="content-wrapper">
        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf
            <h1 class="form-title">Kaşif Olmaya Hazır mısın?</h1>

            <div class="form-group">
                <label for="name" class="form-label">Kaşif Adın</label>
                <input id="name" type="text" class="form-input @error('name') is-invalid @enderror"
                       name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                @error('name')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">E-posta Adresin</label>
                <input id="email" type="email" class="form-input @error('email') is-invalid @enderror"
                       name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Gizli Şifren</label>
                    <input id="password" type="password" class="form-input @error('password') is-invalid @enderror"
                           name="password" required autocomplete="new-password">
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password-confirm" class="form-label">Şifreni Tekrar Yaz</label>
                    <input id="password-confirm" type="password" class="form-input"
                           name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="auth-button">
                Maceraya Başla!
            </button>

            <div class="auth-link">
                Zaten bir kaşif misin?
                <a href="{{ route('login') }}">Giriş Yap!</a>
            </div>
        </form>
    </div>

    <div class="bubo-container">
        <img src="/assets/img/dalkus-left.png" alt="Bilge Baykuş Bubo">
        <div class="speech-bubble">Hoş geldin yeni kaşif! Birlikte muhteşem matematiksel maceralara çıkacağız!</div>
    </div>
</div>

<!-- Verileri JSON olarak sakla -->
<script id="app-data" type="application/json">
    {
        "topics": [],
        "difficultyLevels": []
    }
</script>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="{{asset('assets/js/login-register.js')}}"></script>
@endsection
