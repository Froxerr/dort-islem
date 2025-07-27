@extends('layouts.app')

@section('title', $user->username . ' - Profil')

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/view-profil.css') }}">
    <style>
        /* Privacy Notice Styles */
        .privacy-notice {
            background: linear-gradient(145deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
            color: #856404;
        }
        
        .privacy-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ffc107, #e0a800);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .privacy-notice p {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        /* Empty State Styles */
        .empty-state {
            background: linear-gradient(145deg, rgba(108, 117, 125, 0.1), rgba(108, 117, 125, 0.05));
            border: 1px solid rgba(108, 117, 125, 0.2);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
            color: #6c757d;
        }
        
        .empty-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #6c757d, #495057);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        /* Updated Badge Styles */
        .badge-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            border: 1px solid rgba(76, 175, 80, 0.2);
            transition: all 0.3s ease;
        }
        
        .badge-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .badge-icon img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }
        
        .badge-info {
            flex: 1;
        }
        
        .badge-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .badge-date {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Updated Test Styles */
        .test-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            border: 1px solid rgba(76, 175, 80, 0.2);
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .test-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .test-date {
            font-size: 0.9rem;
            color: #666;
            min-width: 120px;
        }
        
        .test-details {
            flex: 1;
        }
        
        .test-difficulty {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .test-score {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
        }
        
        .test-score .correct {
            color: #28a745;
        }
        
        .test-score .wrong {
            color: #dc3545;
        }
        
        .test-score .score {
            color: #007bff;
            font-weight: 600;
        }
        
        .accuracy-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: conic-gradient(#28a745 var(--accuracy), #e9ecef var(--accuracy));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            color: #2c3e50;
            position: relative;
        }
        
        .accuracy-circle::before {
            content: '';
            position: absolute;
            width: 35px;
            height: 35px;
            background: white;
            border-radius: 50%;
            z-index: 1;
        }
        
        .accuracy-circle span {
            position: relative;
            z-index: 2;
        }
    </style>
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
            @if($showStats && count($stats) > 0)
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
            @else
                @if(!$showStats)
                <div class="privacy-notice">
                    <div class="privacy-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <p>Bu kullanıcı istatistiklerini paylaşmayı tercih etmiyor.</p>
                </div>
                @endif
            @endif

            @if($showAchievements && $badges->count() > 0)
            <!-- Rozetler -->
            <div class="badges-section">
                <h2><i class="fas fa-trophy"></i> Rozetler</h2>
                <div class="badges-grid">
                    @foreach($badges as $badge)
                        <div class="badge-item">
                            <div class="badge-icon">
                                <img src="{{ asset('assets/img/badges/' . $badge->icon_filename) }}" alt="{{ $badge->name }}">
                            </div>
                            <div class="badge-info">
                                <div class="badge-name">{{ $badge->name }}</div>
                                @if($badge->earned_at)
                                    <div class="badge-date">{{ $badge->earned_at->format('d.m.Y') }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @else
                @if(!$showAchievements)
                <div class="privacy-notice">
                    <div class="privacy-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <p>Bu kullanıcı başarımlarını paylaşmayı tercih etmiyor.</p>
                </div>
                @elseif($badges->count() == 0)
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <p>Henüz hiç rozet kazanılmamış.</p>
                </div>
                @endif
            @endif

            @if($showActivity && $recentQuizzes->count() > 0)
            <!-- Son Testler -->
            <div class="recent-tests-section">
                <h2><i class="fas fa-history"></i> Son Testler</h2>
                <div class="tests-list">
                    @foreach($recentQuizzes as $quiz)
                        <div class="test-item">
                            <div class="test-date">
                                {{ \Carbon\Carbon::parse($quiz['date'])->format('d.m.Y H:i') }}
                            </div>
                            <div class="test-details">
                                <div class="test-difficulty">{{ $quiz['difficulty'] }}</div>
                                <div class="test-score">
                                    <span class="correct">{{ $quiz['correct_answers'] }} doğru</span>
                                    <span class="wrong">{{ $quiz['wrong_answers'] }} yanlış</span>
                                    <span class="score">{{ $quiz['score'] }} puan</span>
                                </div>
                            </div>
                            <div class="test-accuracy">
                                <div class="accuracy-circle" style="--accuracy: {{ $quiz['accuracy'] }}%">
                                    <span>%{{ $quiz['accuracy'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @else
                @if(!$showActivity)
                <div class="privacy-notice">
                    <div class="privacy-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <p>Bu kullanıcı aktivitelerini paylaşmayı tercih etmiyor.</p>
                </div>
                @elseif($recentQuizzes->count() == 0)
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <p>Henüz test çözülmemiş.</p>
                </div>
                @endif
            @endif
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
