@extends('layouts.app')

@section('title', 'Kaşif Haritası - Ana Sayfa')

@section('css')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{asset('assets/css/main.css')}}">
<link rel="stylesheet" href="{{asset('assets/css/achievements.css')}}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection

@section('content')
<div class="main-container">
    @if(session('success'))
        <div class="notification">
            {{ session('success') }}
        </div>
    @endif

    <div class="exit-dialog" id="exitDialog" style="display: none;">
        <div class="dialog-content">
            <h3>Çıkmak istediğinize emin misiniz?</h3>
            <p>İlerlemeniz kaydedilmeyecek!</p>
            <div class="dialog-buttons">
                <button id="confirmExit" class="dialog-btn confirm">Evet, Çık</button>
                <button id="cancelExit" class="dialog-btn cancel">Hayır, Devam Et</button>
            </div>
        </div>
    </div>

    <img src="{{ asset('assets/img/bulut/playbulut1.png') }}" alt="Merkez Bulut" class="center-cloud" id="centerCloud">
    
    <form method="POST" action="{{ route('logout') }}" style="position: absolute; top: 20px; right: 20px; z-index: 2;">
        @csrf
        <button type="submit" style="background: none; border: none; padding: 0; margin: 0;">
            <img src="{{ asset('assets/img/bulut/cikis-bulut.png') }}" alt="Çıkış Bulutu" class="profile-cloud cloud">
        </button>
    </form>

    <a href="{{ route('profile.hub') }}" style="position: absolute; top: 90px; right: 20px; z-index: 2;">
        <img src="{{ asset('assets/img/bulut/profil-bulut.png') }}" alt="Profil Bulutu" class="profile-cloud-secondary cloud">
    </a>

    <img src="{{ asset('assets/img/dalkus-right.png') }}" alt="Rehber Baykuş" class="owl-guide">
    <div class="speech-bubble">
        Merhaba! Ben senin rehberin olacağım. Haydi birlikte öğrenelim!
    </div>

    <img src="{{ asset('assets/img/bulut/bulut4.png') }}" alt="Dekoratif Bulut 2" class="cloud decoration-cloud-2">
    <img src="{{ asset('assets/img/bulut/neselibulut.png') }}" alt="Dekoratif Bulut 3" class="cloud decoration-cloud-3">
    <img src="{{ asset('assets/img/bulut/bulut2.png') }}" alt="Dekoratif Bulut 4" class="cloud decoration-cloud-4">

    <div class="calculator-wrapper" id="calculator">
        <button class="calculator-close" id="calculatorClose"></button>
        
        <div class="calculator-top">
            <h2 class="section-title" id="sectionTitle">Konu Seçiniz</h2>
        </div>
        
        <div class="calculator-bottom">
            <div class="topics-grid" id="topicsGrid">
                @foreach($topics as $topic)
                    <div class="topic-item" data-topic-id="{{ $topic->id }}" data-topic-name="{{ $topic->name }}">
                        <img src="{{ asset('assets/img/' . $topic->icon_path) }}" alt="{{ $topic->name }}" class="topic-icon">
                    </div>
                @endforeach
            </div>

            <div class="difficulty-grid" id="difficultyGrid" style="display: none;">
                @foreach($difficultyLevels as $level)
                    <div class="difficulty-item" 
                        data-level-id="{{ $level->id }}" 
                        data-level-name="{{ $level->name }}"
                        data-xp-multiplier="{{ $level->xp_multiplier }}">
                        <img src="{{ asset($level->image_path) }}" alt="{{ $level->name }}" class="difficulty-icon">
                    </div>
                @endforeach
            </div>

            <div class="calculation-area" id="calculationArea" style="display: none;">
                <div class="game-header">
                    <div class="timer-container">
                        <div class="timer">
                            <i class="fas fa-clock"></i>
                            <span id="countdown">60</span>
                        </div>
                        <div class="timer-bar">
                            <div class="timer-progress" id="timerProgress"></div>
                        </div>
                    </div>
                    <div class="score">
                        <div class="correct-answers">
                            <i class="fas fa-check"></i>
                            <span id="correctCount">0</span>
                        </div>
                        <div class="wrong-answers">
                            <i class="fas fa-times"></i>
                            <span id="wrongCount">0</span>
                        </div>
                    </div>
                </div>

                <div class="question-container">
                    <div class="number-box">
                        <span id="number1" class="number"></span>
                    </div>
                    <span id="operator" class="operator"></span>
                    <div class="number-box">
                        <span id="number2" class="number"></span>
                    </div>
                    <span class="equals">=</span>
                    <div class="number-box answer-box">
                        <span class="question-mark">?</span>
                    </div>
                </div>

                <div class="answer-container">
                    <input type="number" id="answer" class="answer-input" placeholder="Cevabı buraya yazın">
                    <button id="checkAnswer" class="check-button">Kontrol Et</button>
                </div>

                <div class="feedback" id="feedback"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/js/questionGenerator.js') }}"></script>
<script src="{{ asset('assets/js/achievements.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
@endsection
