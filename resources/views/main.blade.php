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
<!-- tsParticles Plexus Effect -->
<div id="tsparticles"></div>

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

    <img src="{{ asset('assets/img/bulut/start.png') }}" alt="Merkez Bulut" class="center-cloud" id="centerCloud">

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
    <a href="{{ route('leaderboard.index') }}">
        <img src="{{ asset('assets/img/bulut/leaderboard.png') }}" alt="Leaderboard Bulut" class="cloud decoration-cloud-2">
        </a>
    <a href="{{ route('profile.settings') }}">
        <img src="{{ asset('assets/img/bulut/ayarlar.png') }}" alt="Ayarlar Bulut" class="cloud decoration-cloud-3">
    </a>
    <a href="{{ route('friends.index') }}">
        <img src="{{ asset('assets/img/bulut/arkadaslarim.png') }}" alt="Arkadaslarim Bulut" class="cloud decoration-cloud-4">
    </a>

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
<script>
    // Kullanıcı tercihleri
    window.userSettings = @json($userSettings);
</script>
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/js/questionGenerator.js') }}"></script>
<script src="{{ asset('assets/js/achievements.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<!-- tsParticles Library (v2 - more stable) -->
<script src="https://cdn.jsdelivr.net/npm/tsparticles-engine@2.12.0/tsparticles.engine.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>

<!-- tsParticles Plexus Effect Configuration -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Debug: Check if container exists
    const container = document.getElementById('tsparticles');

    // Initialize tsParticles with v2 API
    tsParticles.load("tsparticles", {
        background: {
            color: {
                value: "transparent"
            }
        },
        fpsLimit: 120,
        particles: {
            color: {
                value: "#ffffff"
            },
            links: {
                color: "#ffffff",
                distance: 150,
                enable: true,
                opacity: 0.5,
                width: 1
            },
            move: {
                direction: "none",
                enable: true,
                outModes: {
                    default: "bounce"
                },
                random: false,
                speed: 2,
                straight: false
            },
            number: {
                density: {
                    enable: true,
                    area: 800
                },
                value: 80
            },
            opacity: {
                value: 0.8
            },
            shape: {
                type: "circle"
            },
            size: {
                value: { min: 1, max: 3 }
            }
        },
        interactivity: {
            events: {
                onClick: {
                    enable: false, // true'dan false'a değiştirdik
                    mode: "push"
                },
                onHover: {
                    enable: true,
                    mode: "grab"
                },
                resize: true
            },
            modes: {
                push: {
                    quantity: 4
                },
                grab: {
                    distance: 200,
                    links: {
                        opacity: 1
                    }
                }
            }
        },
        detectRetina: true
    }).then(container => {
        // Manuel partikül sayısını kontrol et
        setTimeout(() => {
        }, 2000);
    }).catch(error => {
        console.error("❌ tsParticles initialization error:", error);
    });
});
</script>
@endsection
