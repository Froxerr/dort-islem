@if($searchResults->count() > 0)
    <h3 class="section-title">
        <i class="fas fa-search"></i>
        "{{ $query }}" için sonuçlar
    </h3>
    
    <div class="users-list">
        @foreach($searchResults as $index => $user)
            <div class="user-card animate-card" 
                 data-user-id="{{ $user->id }}"
                 style="animation-delay: {{ $index * 0.1 }}s">
                <div class="friend-avatar">
                    @if($user->profile_image)
                        <img src="{{ Storage::url($user->profile_image) }}" alt="{{ $user->name }}">
                    @else
                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    @endif
                    <div class="level-badge" title="Seviye {{ $user->level }}">
                        {{ $user->level }}
                    </div>
                </div>
                <div class="friend-info">
                    <div class="friend-header">
                        <h3 class="friend-name">{{ $user->name }}</h3>
                        <span class="friend-username">{{ '@' . $user->username }}</span>
                    </div>
                    <div class="friend-stats">
                        <div class="stat-item" title="XP Puanı">
                            <i class="fas fa-trophy"></i>
                            <span>{{ number_format($user->xp) }} XP</span>
                        </div>
                        @if($user->mutual_friends_count > 0)
                            <div class="stat-item" title="Ortak Arkadaşlar">
                                <i class="fas fa-users"></i>
                                <span>{{ $user->mutual_friends_count }} ortak arkadaş</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="friend-actions">
                    @if($user->friendship_status === 'none')
                        <button class="btn btn-add" onclick="sendFriendRequest({{ $user->id }})">
                            <i class="fas fa-user-plus"></i>
                            <span class="btn-text">Arkadaş Ekle</span>
                        </button>
                    @elseif($user->friendship_status === 'pending_sent')
                        <button class="btn btn-pending" disabled>
                            <i class="fas fa-clock"></i>
                            <span class="btn-text">İstek Gönderildi</span>
                        </button>
                    @elseif($user->friendship_status === 'friends')
                        <button class="btn btn-friends" disabled>
                            <i class="fas fa-check"></i>
                            <span class="btn-text">Arkadaşsınız</span>
                        </button>
                    @endif
                    
                    <a href="{{ route('friends.view', $user->id) }}" class="btn btn-profile" title="Profil Görüntüle">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="empty-state animate-fade-in">
        <div class="empty-cloud">
            <i class="fas fa-search"></i>
        </div>
        <h3>Sonuç bulunamadı</h3>
        <p>"{{ $query }}" ile eşleşen kullanıcı bulunamadı.</p>
    </div>
@endif

<style>
.users-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.user-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    border: 2px solid transparent;
    opacity: 0;
    transform: translateY(20px);
}

.animate-card {
    animation: slideIn 0.5s ease forwards;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.user-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary-color);
    box-shadow: var(--shadow-hover);
}

.friend-avatar {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--primary-color);
    flex-shrink: 0;
}

.friend-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.level-badge {
    position: absolute;
    bottom: 7px;
    right: 5px;
    background: var(--primary-color);
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: bold;
    border: 2px solid white;
    box-shadow: var(--shadow);
    z-index: 2;
}

.friend-header {
    margin-bottom: 0.5rem;
}

.friend-name {
    font-size: 1.2rem;
    color: var(--text-primary);
    margin: 0;
}

.friend-username {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.friend-stats {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.stat-item i {
    color: var(--primary-color);
}

.friend-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    border: none;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn i {
    font-size: 1rem;
}

.btn-text {
    @media (max-width: 640px) {
        display: none;
    }
}

.btn-add {
    background: var(--primary-color);
    color: white;
}

.btn-add:hover {
    background: var(--primary-dark);
    transform: scale(1.05);
}

.btn-pending {
    background: var(--warning-color);
    color: white;
    cursor: not-allowed;
}

.btn-accept {
    background: var(--success-color);
    color: white;
}

.btn-accept:hover {
    background: var(--success-dark);
    transform: scale(1.05);
}

.btn-friends {
    background: var(--secondary-color);
    color: white;
    cursor: not-allowed;
}

.btn-profile {
    background: var(--info-color);
    color: white;
    padding: 0.5rem;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-profile:hover {
    background: var(--info-dark);
    transform: scale(1.1);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    opacity: 0;
    animation: fadeIn 0.5s ease forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.empty-cloud {
    width: 80px;
    height: 80px;
    background: var(--card-bg);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    box-shadow: var(--shadow);
}

.empty-cloud i {
    font-size: 2rem;
    color: var(--text-secondary);
}

.empty-state h3 {
    font-size: 1.5rem;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--text-secondary);
    margin: 0;
}

@media (max-width: 768px) {
    .user-card {
        flex-direction: column;
        text-align: center;
        padding: 1rem;
    }

    .friend-info {
        width: 100%;
    }

    .friend-stats {
        justify-content: center;
    }

    .friend-actions {
        width: 100%;
        justify-content: center;
        margin-top: 1rem;
    }

    .btn {
        padding: 0.5rem;
        width: 36px;
        height: 36px;
    }

    .btn-text {
        display: none;
    }
}
</style> 