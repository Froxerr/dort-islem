@extends('layouts.app')

@section('title', 'Quiz Geçmişi')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-color: #4CAF50;
        --primary-dark: #2E7D32;
        --primary-light: #8BC34A;
        --secondary-color: #FFD700;
        --background-start: #f0f7f4;
        --background-end: #e8f5e9;
        --text-primary: #2c3e50;
        --text-secondary: #666666;
        --card-background: rgba(255, 255, 255, 0.95);
        --success-color: #4CAF50;
        --warning-color: #FF9800;
        --danger-color: #F44336;
        --info-color: #2196F3;
    }

    body {
        background: linear-gradient(135deg, var(--background-start) 0%, var(--background-end) 100%);
        min-height: 100vh;
        margin: 0;
        font-family: 'Comic Sans MS', 'Chalkboard SE', 'Marker Felt', sans-serif;
        color: var(--text-primary);
        line-height: 1.6;
    }

    .history-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2rem;
        position: relative;
    }

    /* Geri Dönme Butonu */
    .back-button {
        position: fixed;
        top: 2rem;
        left: 2rem;
        background: #4CAF50;
        color: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 25px;
        cursor: pointer;
        box-shadow: 
            0 4px 15px rgba(76, 175, 80, 0.3),
            0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .back-button:hover {
        transform: translateY(-2px) scale(1.1);
        box-shadow: 
            0 6px 20px rgba(76, 175, 80, 0.4),
            0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .back-button i {
        font-size: 1.5rem;
        transition: transform 0.3s ease;
    }

    .back-button:hover i {
        transform: translateX(-2px);
    }

    /* Başlık */
    .page-title {
        text-align: center;
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--primary-color);
        margin-bottom: 2rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }

    /* İstatistik Kartları */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--card-background);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 
            8px 8px 16px rgba(0,0,0,0.1),
            -4px -4px 16px rgba(255,255,255,0.8);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 
            12px 12px 24px rgba(0,0,0,0.15),
            -6px -6px 24px rgba(255,255,255,0.9);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 600;
    }

    /* Filtre Bölümü */
    .filters-container {
        background: var(--card-background);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 
            8px 8px 16px rgba(0,0,0,0.1),
            -4px -4px 16px rgba(255,255,255,0.8);
    }

    .filters-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .filter-select, .filter-input {
        padding: 0.75rem;
        border: 2px solid rgba(76, 175, 80, 0.3);
        border-radius: 10px;
        background: white;
        font-family: inherit;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .filter-select:focus, .filter-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
    }

    .filter-button {
        padding: 0.75rem 1.5rem;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-family: inherit;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .filter-button:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    /* Quiz Geçmişi Kartları */
    .history-grid {
        display: grid;
        gap: 1.5rem;
    }

    .quiz-card {
        background: var(--card-background);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 
            8px 8px 16px rgba(0,0,0,0.1),
            -4px -4px 16px rgba(255,255,255,0.8);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .quiz-card:hover {
        transform: translateY(-3px);
        box-shadow: 
            12px 12px 24px rgba(0,0,0,0.15),
            -6px -6px 24px rgba(255,255,255,0.9);
    }

    .quiz-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    }

    .quiz-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .quiz-info h3 {
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
        font-size: 1.2rem;
    }

    .quiz-meta {
        font-size: 0.9rem;
        color: var(--text-secondary);
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .quiz-score {
        text-align: right;
    }

    .score-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }

    .score-excellent { color: var(--success-color); }
    .score-good { color: var(--info-color); }
    .score-average { color: var(--warning-color); }
    .score-poor { color: var(--danger-color); }

    .quiz-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 0.8rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0,0,0,0.1);
    }

    .quiz-stat {
        text-align: center;
    }

    .quiz-stat-value {
        font-size: 1rem;
        font-weight: bold;
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        flex-wrap: wrap;
    }

    .quiz-stat-label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: 0.25rem;
    }

    /* Difficulty Badge */
    .difficulty-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .difficulty-kolay { background: #E8F5E8; color: #2E7D32; }
    .difficulty-orta { background: #FFF3E0; color: #F57C00; }
    .difficulty-zor { background: #FFEBEE; color: #D32F2F; }
    .difficulty-deha { background: #F3E5F5; color: #7B1FA2; }

    /* Pagination */
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
    }

    .pagination {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .pagination a, .pagination span {
        padding: 0.75rem 1rem;
        border: 2px solid rgba(76, 175, 80, 0.3);
        border-radius: 10px;
        background: white;
        color: var(--text-primary);
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .pagination a:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }

    .pagination .active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state h3 {
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .history-container {
            padding: 1rem;
        }

        .filters-row {
            grid-template-columns: 1fr;
        }

        .quiz-header {
            flex-direction: column;
            gap: 1rem;
        }

        .quiz-score {
            text-align: left;
        }

        .back-button {
            top: 1rem;
            left: 1rem;
        }
    }
</style>
@endsection

@section('content')
<a href="{{ route('profile.hub') }}" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="history-container">
    <h1 class="page-title">
        <i class="fas fa-history"></i>
        Quiz Geçmişi
    </h1>

    <!-- İstatistikler -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-value">{{ $totalQuizzes }}</div>
            <div class="stat-label">Toplam Quiz</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($averageScore, 1) }}</div>
            <div class="stat-label">Ortalama Skor</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($averageAccuracy, 1) }}%</div>
            <div class="stat-label">Ortalama Başarı</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $totalXP }}</div>
            <div class="stat-label">Toplam XP</div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="filters-container">
        <form method="GET" action="{{ route('profile.history') }}">
            <div class="filters-row">
                <div class="filter-group">
                    <label class="filter-label">Konu</label>
                    <select name="topic" class="filter-select">
                        <option value="">Tüm Konular</option>
                        @foreach($topics as $topic)
                            <option value="{{ $topic->id }}" {{ request('topic') == $topic->id ? 'selected' : '' }}>
                                {{ $topic->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Zorluk</label>
                    <select name="difficulty" class="filter-select">
                        <option value="">Tüm Zorluklar</option>
                        @foreach($difficulties as $difficulty)
                            <option value="{{ $difficulty->id }}" {{ request('difficulty') == $difficulty->id ? 'selected' : '' }}>
                                {{ $difficulty->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Tarih Aralığı</label>
                    <select name="date_range" class="filter-select">
                        <option value="">Tüm Zamanlar</option>
                        <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Bugün</option>
                        <option value="week" {{ request('date_range') == 'week' ? 'selected' : '' }}>Bu Hafta</option>
                        <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>Bu Ay</option>
                        <option value="3months" {{ request('date_range') == '3months' ? 'selected' : '' }}>Son 3 Ay</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="filter-button">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Quiz Geçmişi -->
    <div class="history-grid">
        @forelse($quizSessions as $session)
            <div class="quiz-card">
                <div class="quiz-header">
                    <div class="quiz-info">
                        <h3>{{ $session->topic->name ?? 'Bilinmeyen Konu' }}</h3>
                        <div class="quiz-meta">
                            <span><i class="fas fa-calendar"></i> {{ $session->created_at->format('d.m.Y H:i') }}</span>
                            <span class="difficulty-badge difficulty-{{ strtolower($session->difficultyLevel->name ?? 'kolay') }}">
                                {{ $session->difficultyLevel->name ?? 'Kolay' }}
                            </span>
                        </div>
                    </div>
                    <div class="quiz-score">
                        <div class="score-value {{ 
                            $session->score >= 90 ? 'score-excellent' : 
                            ($session->score >= 70 ? 'score-good' : 
                            ($session->score >= 50 ? 'score-average' : 'score-poor')) 
                        }}">
                            {{ $session->score }}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">puan</div>
                    </div>
                </div>

                <div class="quiz-stats">
                    <div class="quiz-stat">
                        <div class="quiz-stat-value">{{ $session->correct_answers }}/{{ $session->total_questions }}</div>
                        <div class="quiz-stat-label">Doğru Cevap</div>
                    </div>
                    <div class="quiz-stat">
                        <div class="quiz-stat-value">{{ number_format(($session->correct_answers / $session->total_questions) * 100, 1) }}%</div>
                        <div class="quiz-stat-label">Başarı Oranı</div>
                    </div>
                    <div class="quiz-stat">
                        <div class="quiz-stat-value">{{ $session->xp_earned }}</div>
                        <div class="quiz-stat-label">Kazanılan XP</div>
                    </div>
                    <div class="quiz-stat">
                        <div class="quiz-stat-value">{{ $session->total_questions - $session->correct_answers }}</div>
                        <div class="quiz-stat-label">Yanlış Cevap</div>
                    </div>
                    <div class="quiz-stat">
                        <div class="quiz-stat-value">
                            @if($session->score >= 90)
                                <i class="fas fa-star" style="color: #FFD700;"></i> Mükemmel
                            @elseif($session->score >= 70)
                                <i class="fas fa-thumbs-up" style="color: #4CAF50;"></i> İyi
                            @elseif($session->score >= 50)
                                <i class="fas fa-check" style="color: #FF9800;"></i> Orta
                            @else
                                <i class="fas fa-times" style="color: #F44336;"></i> Zayıf
                            @endif
                        </div>
                        <div class="quiz-stat-label">Performans</div>
                    </div>

                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Henüz Quiz Geçmişi Yok</h3>
                <p>İlk quizini tamamladığında burada görünecek!</p>
                <div style="margin-top: 1.5rem;">
                    <a href="{{ route('main') }}" style="
                        padding: 0.75rem 1.5rem;
                        background: var(--primary-color);
                        color: white;
                        text-decoration: none;
                        border-radius: 10px;
                        font-weight: 600;
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.background='var(--primary-dark)'; this.style.transform='translateY(-2px)'" 
                       onmouseout="this.style.background='var(--primary-color)'; this.style.transform='translateY(0)'">
                        <i class="fas fa-play"></i>
                        İlk Quizini Başlat
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($quizSessions->hasPages())
        <div class="pagination-container">
            <div class="pagination">
                {{-- Previous Page Link --}}
                @if ($quizSessions->onFirstPage())
                    <span><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $quizSessions->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($quizSessions->getUrlRange(1, $quizSessions->lastPage()) as $page => $url)
                    @if ($page == $quizSessions->currentPage())
                        <span class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($quizSessions->hasMorePages())
                    <a href="{{ $quizSessions->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection 