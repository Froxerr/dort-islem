@extends('layouts.app')

@section('title', $user->username . ' - Profil')

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/view-profil.css') }}">
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/friends.css') }}">

<div class="friends-container">
    <!-- Geri Butonu -->
    <a href="{{ url()->previous() }}" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="profile-view-container">
        <!-- Profil Başlığı -->
        <div class="profile-header">
            <div class="profile-avatar">
                @if($user->profile_image)
                    <img src="{{ Storage::url($user->profile_image) }}" alt="{{ $user->name }}">
                @else
                    <div class="avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                @endif
            </div>
            <div class="profile-info">
                <h1 class="profile-name">{{ $user->name }}</h1>
                <div class="profile-username">{{ '@' . $user->username }}</div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <i class="fas fa-trophy"></i>
                        <span>{{ $user->xp }} XP</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-star"></i>
                        <span>Seviye {{ $user->level }}</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span>{{ $mutualFriendsCount }} Ortak Arkadaş</span>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                @if($friendshipStatus === 'none')
                    <button class="btn btn-primary add-friend-btn">
                        <i class="fas fa-user-plus"></i> Arkadaş Ekle
                    </button>
                @elseif($friendshipStatus === 'pending_sent')
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-clock"></i> İstek Gönderildi
                    </button>
                @elseif($friendshipStatus === 'pending_received')
                    <button class="btn btn-success accept-friend-btn" data-user-id="{{ $user->id }}">
                        <i class="fas fa-check"></i> İsteği Kabul Et
                    </button>
                @else
                    <button class="btn btn-success" disabled>
                        <i class="fas fa-user-check"></i> Arkadaşsınız
                    </button>
                @endif
            </div>
        </div>

        <!-- Profil İçeriği -->
        <div class="profile-content">
            <!-- İstatistikler -->
            <div class="stats-section">
                <h2><i class="fas fa-chart-bar"></i> İstatistikler</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">{{ number_format($stats['totalTests']) }}</div>
                            <div class="stat-label">Toplam Test</div>
                        </div>
                        <div class="stat-progress" style="--progress: {{ min(100, ($stats['totalTests'] / 100) * 100) }}%"></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">%{{ number_format($stats['averageAccuracy'], 1) }}</div>
                            <div class="stat-label">Ortalama Başarı</div>
                        </div>
                        <div class="stat-progress" style="--progress: {{ $stats['averageAccuracy'] }}%"></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">{{ number_format($stats['highestScore']) }}</div>
                            <div class="stat-label">En Yüksek Skor</div>
                        </div>
                        <div class="stat-progress" style="--progress: {{ min(100, ($stats['highestScore'] / 1000) * 100) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Rozetler -->
            <div class="badges-section">
                <h3><i class="fas fa-award"></i> Rozetler</h3>
                <div class="badges-grid">
                    @forelse($badges as $badge)
                        <div class="badge-item">
                            <img src="{{ asset('assets/img/badges/' . $badge->icon_filename) }}"
                                 alt="{{ $badge->name }}"
                                 title="{{ $badge->name }}">
                            <span class="badge-name">{{ $badge->name }}</span>
                        </div>
                    @empty
                        <div class="empty-state">
                            <h4>Henüz Badge Yok</h4>
                            <p>Kullanıcı henüz hiç badge kazanmamış.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Son Aktiviteler -->
            <div class="recent-activities">
                <h3><i class="fas fa-history"></i> Son Aktiviteler</h3>
                <div class="activities-list">
                    @forelse($recentQuizzes as $quiz)
                        <div class="quiz-result">
                            <div class="quiz-icon">
                                <i class="fas fa-pen"></i>
                            </div>
                            <div class="quiz-details">
                                <div class="quiz-header">
                                    <span class="quiz-difficulty">{{ $quiz['difficulty'] }}</span>
                                    <span class="quiz-date">{{ $quiz['date']->diffForHumans() }}</span>
                                </div>
                                <div class="quiz-stats">
                                    <span class="stat correct">
                                        <i class="fas fa-check"></i> {{ $quiz['correct_answers'] }} doğru
                                    </span>
                                    <span class="stat wrong">
                                        <i class="fas fa-times"></i> {{ $quiz['wrong_answers'] }} yanlış
                                    </span>
                                    <span class="stat score">
                                        <i class="fas fa-star"></i> {{ $quiz['score'] }} puan
                                    </span>
                                    <span class="stat accuracy">
                                        <i class="fas fa-bullseye"></i> %{{ $quiz['accuracy'] }} başarı
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <h4>Henüz Test Çözülmemiş</h4>
                            <p>Kullanıcı henüz hiç test çözmemiş.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Arkadaş Ekleme Onay Modalı -->
<div id="friendRequestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Arkadaşlık İsteği</h4>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p><strong>{{ $user->name }}</strong> kullanıcısına arkadaşlık isteği göndermek istediğinize emin misiniz?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary close-modal">Vazgeç</button>
            <button class="btn btn-primary" id="confirmFriendRequest">Evet, İstek Gönder</button>
        </div>
    </div>
</div>

@endsection

@section('js')

<script>
// Profil sayfası için özel bildirim fonksiyonu
function showProfileNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `profile-notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Animasyon ile göster
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    // 3 saniye sonra kaldır
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Arkadaşlık isteklerini yönetme fonksiyonları
function handleFriendshipResponse(response, successCallback) {
    if (response.ok) {
        return response.json().then(data => {
            if (data.success) {
                showProfileNotification(data.message, 'success');
                if (successCallback) successCallback(data);
            } else {
                showProfileNotification(data.message, 'error');
            }
        });
    } else {
        throw new Error('İstek başarısız oldu');
    }
}

function updateProfileActionButtons(newStatus) {
    const actionsContainer = document.querySelector('.profile-actions');
    if (!actionsContainer) return;

    let newHtml = '';
    const userId = actionsContainer.dataset.userId;

    switch (newStatus) {
        case 'pending_sent':
            newHtml = `
                <button class="btn btn-pending" disabled>
                    <i class="fas fa-clock"></i>
                    İstek Gönderildi
                </button>
            `;
            break;
        case 'friends':
            newHtml = `
                <div class="action-group">
                    <button class="btn btn-friends" disabled>
                        <i class="fas fa-check"></i>
                        Arkadaşsınız
                    </button>
                    <button class="btn btn-remove" onclick="removeFriend(${userId})">
                        <i class="fas fa-user-minus"></i>
                        Arkadaşı Kaldır
                    </button>
                </div>
            `;
            break;
        case 'none':
            newHtml = `
                <button class="btn btn-add" onclick="sendFriendRequest(${userId})">
                    <i class="fas fa-user-plus"></i>
                    Arkadaş Ekle
                </button>
            `;
            break;
    }

    actionsContainer.innerHTML = newHtml;
}
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('friendRequestModal');
    const addFriendBtn = document.querySelector('.add-friend-btn');
    const confirmBtn = document.getElementById('confirmFriendRequest');
    const closeButtons = document.querySelectorAll('.close-modal');

    // Modal'ı aç
    if (addFriendBtn) {
        addFriendBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'block';
        });
    }

    // Modal'ı kapat
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });

    // Modal dışına tıklandığında kapat
    window.addEventListener('click', function(e) {
        if (e.target == modal) {
            modal.style.display = 'none';
        }
    });

    // Arkadaşlık isteği gönder
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function() {
            const userId = '{{ $user->id }}';
            const button = this;
            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner';
            button.disabled = true;
            button.appendChild(spinner);
            spinner.style.display = 'inline-block';

            try {
                const response = await fetch('{{ route("friends.send-request") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await response.json();

                if (data.success) {
                    // İstek başarılı
                    addFriendBtn.textContent = 'İstek Gönderildi';
                    addFriendBtn.classList.remove('btn-primary');
                    addFriendBtn.classList.add('btn-secondary');
                    addFriendBtn.disabled = true;
                    modal.style.display = 'none';
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                button.disabled = false;
                spinner.remove();
            }
        });
    }
});
</script>
@endsection
