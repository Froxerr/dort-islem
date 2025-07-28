@php use Illuminate\Support\Facades\Storage; @endphp

@extends('layouts.app')

@section('title', 'Arkadaş Ara')

@section('css')
    <style>
        .search-input-group {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
        }

        .search-spinner {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }

        .search-input {
            width: 100%;
            padding: 1rem 3rem 1rem 1.5rem;
            border: 2px solid transparent;
            border-radius: 25px;
            font-size: 1.1rem;
            background: var(--card-bg);
            color: var(--text-primary);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: var(--shadow-hover);
        }

        .search-input::placeholder {
            color: var(--text-secondary);
        }

        .search-results {
            margin-top: 2rem;
            min-height: 200px;
        }
    </style>
    @endsection

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/friends.css') }}">

<div class="friends-container">
    <!-- Geri Butonu -->
    <a href="{{ route('friends.index') }}" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

    <!-- Ana Başlık -->
    <div class="friends-header">
        <div class="friends-cloud">
            <i class="fas fa-search"></i>
        </div>
        <h1 class="friends-title">Arkadaş Ara</h1>
        <p class="friends-subtitle">Yeni arkadaşlar keşfedin!</p>
    </div>

    <!-- Arama Formu -->
    <div class="search-form">
        <div class="search-input-group">
            <input type="text"
                   name="q"
                   id="searchInput"
                   value="{{ $query }}"
                   placeholder="İsim veya kullanıcı adı ile ara..."
                   class="search-input"
                   autocomplete="off">
            <i class="fas fa-search search-icon"></i>
            <div class="search-spinner" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Arama Sonuçları -->
    <div class="search-results"></div>

    <!-- Önerilen Arkadaşlar -->
    @if(empty($query) && $suggestedFriends->count() > 0)
        <div class="suggested-friends">
            <h3 class="section-title">
                <i class="fas fa-lightbulb"></i>
                Önerilen Arkadaşlar
            </h3>
            <div class="users-list">
                @foreach($suggestedFriends as $user)
                    <div class="user-card" data-user-id="{{ $user->id }}">
                            <div class="friend-avatar">
                                @if($user->profile_image)
                                    <img src="{{ Storage::url($user->profile_image) }}" alt="{{ $user->name }}">
                                @else
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="friend-info">
                                <h3 class="friend-name">{{ $user->name ?? $user->username }}</h3>
                                <div class="friend-stats">
                                    <span class="level">Lv.{{ $user->level }}</span>
                                    <span class="xp"><i class="fas fa-trophy"></i> {{ $user->xp }} XP</span>
                                </div>
                                <div class="friend-meta">
                                    <span class="mutual">{{ $user->mutual_friends_count }} ortak arkadaş</span>
                                </div>
                            </div>
                            <div class="friend-actions">
                                <button class="btn btn-add" onclick="sendFriendRequest({{ $user->id }})">
                                    <i class="fas fa-user-plus"></i>
                                    Ekle
                                </button>
                                <button class="btn btn-profile" title="Profil Görüntüle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.querySelector('.search-results');
    const searchIcon = document.querySelector('.search-icon');
    const searchSpinner = document.querySelector('.search-spinner');
    const suggestedFriends = document.querySelector('.suggested-friends');
    let searchTimeout;

    function performSearch(query) {
        // Spinner'ı göster, ikonu gizle
        searchIcon.style.display = 'none';
        searchSpinner.style.display = 'block';

        fetch(`/profile/friends/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            searchResults.innerHTML = html;

            // Önerilen arkadaşları göster/gizle
            if (suggestedFriends) {
                suggestedFriends.style.display = query.length > 0 ? 'none' : 'block';
            }

            // İkonu göster, spinner'ı gizle
            searchIcon.style.display = 'block';
            searchSpinner.style.display = 'none';
        })
        .catch(error => {
            console.error('Search error:', error);
            searchResults.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    Arama sırasında bir hata oluştu.
                </div>
            `;

            // İkonu göster, spinner'ı gizle
            searchIcon.style.display = 'block';
            searchSpinner.style.display = 'none';
        });
    }

    // Input değiştiğinde arama yap
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        // Önceki timeout'u temizle
        clearTimeout(searchTimeout);

        // En az 2 karakter girilmişse arama yap
        if (query.length >= 2) {
            // 300ms bekle ve arama yap
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            // Arama sonuçlarını temizle
            searchResults.innerHTML = '';
            if (suggestedFriends) {
                suggestedFriends.style.display = 'block';
            }
            searchIcon.style.display = 'block';
            searchSpinner.style.display = 'none';
        }
    });

    // Sayfa yüklendiğinde query varsa arama yap
    if (searchInput.value.trim().length >= 2) {
        performSearch(searchInput.value.trim());
    }
});
</script>
@endsection

