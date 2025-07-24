@extends('layouts.app')

@section('title', 'Kaşif Ol')

@section('css')
<style>
    .register-container {
        background: linear-gradient(135deg, #87CEEB, #E0F7FA);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .cloud {
        position: absolute;
        opacity: 0.8;
    }

    .cloud-1 {
        top: 10%;
        left: 10%;
        animation: float 20s infinite;
    }

    .cloud-2 {
        top: 20%;
        right: 15%;
        animation: float 15s infinite reverse;
    }

    .owl {
        position: absolute;
        width: 150px;
        bottom: 20px;
        right: 20px;
        animation: bounce 2s infinite;
    }

    .tree-left {
        position: absolute;
        height: 300px;
        left: 0;
        bottom: 0;
    }

    .tree-right {
        position: absolute;
        height: 300px;
        right: 0;
        bottom: 0;
    }

    .register-form {
        background: rgba(255, 255, 255, 0.95);
        padding: 2rem;
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 500px;
        margin: 2rem;
        position: relative;
        z-index: 1;
    }

    .form-title {
        color: #2196F3;
        text-align: center;
        margin-bottom: 2rem;
        font-size: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        color: #333;
        font-size: 1.1rem;
    }

    .form-input {
        width: 100%;
        padding: 0.8rem;
        border: 2px solid #E0E0E0;
        border-radius: 10px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-input:focus {
        border-color: #2196F3;
        outline: none;
    }

    .register-button {
        width: 100%;
        padding: 1rem;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.2rem;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .register-button:hover {
        transform: scale(1.02);
    }

    .error-message {
        color: #f44336;
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
</style>
@endsection

@section('content')
<div class="register-container">
    <img src="{{ asset('assets/img/bulut/bulut1.png') }}" alt="Bulut 1" class="cloud cloud-1">
    <img src="{{ asset('assets/img/bulut/bulut2.png') }}" alt="Bulut 2" class="cloud cloud-2">
    <img src="{{ asset('assets/img/agac/sol-agac.png') }}" alt="Sol Ağaç" class="tree-left">
    <img src="{{ asset('assets/img/agac/sag-agac.png') }}" alt="Sağ Ağaç" class="tree-right">
    <img src="{{ asset('assets/img/baykus.png') }}" alt="Baykuş" class="owl">

    <form method="POST" action="{{ route('register') }}" class="register-form">
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

        <div class="form-group">
            <label for="password" class="form-label">Gizli Şifren</label>
            <input id="password" type="password" class="form-input @error('password') is-invalid @enderror" 
                   name="password" required autocomplete="new-password">
            @error('password')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password-confirm" class="form-label">Gizli Şifreni Tekrar Yaz</label>
            <input id="password-confirm" type="password" class="form-input" 
                   name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="register-button">
            Maceraya Başla!
        </button>
    </form>
</div>
@endsection 