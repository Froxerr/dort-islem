@php use Illuminate\Support\Facades\Storage; @endphp

@extends('layouts.app')

@section('title', 'Arkadaşlarım')

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('css')
    <style>
    .floating-search-button {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 60px;
    height: 60px;
    border-radius: 30px;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
    transition: all 0.3s ease;
    z-index: 1000;
    text-decoration: none;
    }

    .floating-search-button i {
    font-size: 1.5rem;
    }

    .floating-search-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.6);
    color: white;
    text-decoration: none;
    }

    @media (max-width: 768px) {
    .floating-search-button {
    bottom: 1.5rem;
    right: 1.5rem;
    width: 50px;
    height: 50px;
    }
    }

    /* Modal Stilleri */
    .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
    }

    .modal-content {
    position: relative;
    background-color: #fff;
    margin: 10% auto;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    animation: slideIn 0.3s;
    }

    .modal-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8f9fa;
    border-radius: 15px 15px 0 0;
    }

    .modal-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.2em;
    display: flex;
    align-items: center;
    gap: 10px;
    }

    .modal-header h4 i {
    color: #3498db;
    }

    .modal-body {
    padding: 25px;
    font-size: 1.1em;
    line-height: 1.5;
    }

    .modal-body p {
    margin: 0;
    color: #2c3e50;
    }

    .modal-footer {
    padding: 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    background: #f8f9fa;
    border-radius: 0 0 15px 15px;
    }

    .close-modal {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
    padding: 0 5px;
    }

    .close-modal:hover {
    color: #666;
    }

    /* Button Stilleri */
    .btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    }

    .btn i {
    font-size: 16px;
    }

    .btn-success {
    background-color: #2ecc71;
    color: white;
    }

    .btn-success:hover {
    background-color: #27ae60;
    }

    .btn-danger {
    background-color: #e74c3c;
    color: white;
    }

    .btn-danger:hover {
    background-color: #c0392b;
    }

    .btn-secondary {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #ddd;
    }

    .btn-secondary:hover {
    background-color: #e9ecef;
    }

    /* Admin Badge */
    .admin-badge {
    display: inline-flex;
    align-items: center;
    margin-left: 5px;
    color: #f1c40f;
    }

    .admin-badge i {
    font-size: 14px;
    }

    /* Animasyonlar */
    @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
    }

    @keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
    }

    /* Link Stilleri */
    .friend-name {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
    }

    .friend-name:hover {
    color: #3498db;
    text-decoration: underline;
    }
    </style>
@endsection



@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/friends.css') }}">

<div class="friends-container">
    <!-- Geri Butonu -->
    <a href="{{ route('profile.hub') }}" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

    <!-- Arkadaş Ara Floating Button -->
    <a href="{{ route('friends.search') }}" class="floating-search-button">
        <i class="fas fa-search"></i>
    </a>

    <!-- Ana Başlık -->
    <div class="friends-header">
        <div class="friends-cloud">
            <i class="fas fa-users"></i>
        </div>
        <h1 class="friends-title"><i class="fas fa-users"></i> Arkadaşlarım</h1>
        <p class="friends-subtitle">Arkadaşlarınızla birlikte matematik öğrenin!</p>
    </div>

    <!-- İstatistikler -->
    <div class="friends-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number">{{ $stats['friends_count'] }}</div>
            <div class="stat-label">Arkadaş</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-inbox"></i></div>
            <div class="stat-number">{{ $stats['pending_requests_count'] }}</div>
            <div class="stat-label">Gelen İstek</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-paper-plane"></i></div>
            <div class="stat-number">{{ $stats['sent_requests_count'] }}</div>
            <div class="stat-label">Gönderilen</div>
        </div>
    </div>

    <!-- Tab Butonları -->
    <div class="tab-buttons">
        <button class="tab-btn active" data-tab="friends">
            <i class="fas fa-users"></i>
            Arkadaşlarım
        </button>
        <button class="tab-btn" data-tab="requests">
            <i class="fas fa-inbox"></i>
            Gelen İstekler
            @if($stats['pending_requests_count'] > 0)
                <span class="badge">{{ $stats['pending_requests_count'] }}</span>
            @endif
        </button>
    </div>

    <!-- Arkadaş Listesi -->
    <div class="tab-content active" id="friends-content">
        @if($friends->count() > 0)
            <div class="friends-list">
                @foreach($friends as $friend)
                    <div class="friend-card">
                        <div class="friend-avatar">
                            @if($friend->profile_image)
                                <img src="{{ Storage::url($friend->profile_image) }}" alt="{{ $friend->name }}">
                            @else
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            @endif
                        </div>
                        <div class="friend-info">
                            <h3 class="friend-name">{{ $friend->name ?? $friend->username }}</h3>
                            <div class="friend-stats">
                                <span class="level">Lv.{{ $friend->level }}</span>
                                <span class="xp"><i class="fas fa-trophy"></i> {{ $friend->xp }} XP</span>
                            </div>
                            <div class="friend-meta">
                                <span class="joined">{{ $friend->pivot->accepted_at->diffForHumans() }} arkadaş</span>
                            </div>
                        </div>
                        <div class="friend-actions">
                            <button class="btn btn-message" title="Mesaj Gönder">
                                <i class="fas fa-comment"></i>
                            </button>
                            <a href="{{ route('friends.view', $friend->id) }}" class="btn btn-profile" title="Profil Görüntüle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-remove" onclick="showRemoveFriendModal({{ $friend->id }}, '{{ $friend->name }}')" title="Arkadaşı Kaldır">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <div class="empty-cloud">
                    <i class="fas fa-user-friends"></i>
                </div>
                <h3>Henüz arkadaşınız yok</h3>
                <p>Yeni arkadaşlar edinmek için arama yapın!</p>
            </div>
        @endif
    </div>

    <!-- Gelen İstekler -->
    <div class="tab-content" id="requests-content">
        @if($pendingRequests->count() > 0)
            <div class="requests-list">
                @foreach($pendingRequests as $request)
                    <div class="request-card" data-request-id="{{ $request->id }}">
                        <div class="friend-avatar">
                            @if($request->user->profile_image)
                                <img src="{{ Storage::url($request->user->profile_image) }}" alt="{{ $request->user->name }}">
                            @else
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            @endif
                        </div>
                        <div class="friend-info">
                            <h3 class="friend-name">{{ $request->user->name ?? $request->user->username }}</h3>
                            <div class="friend-stats">
                                <span class="level">Lv.{{ $request->user->level }}</span>
                                <span class="xp"><i class="fas fa-trophy"></i> {{ $request->user->xp }} XP</span>
                            </div>
                            <div class="friend-meta">
                                <span class="time">{{ $request->created_at->diffForHumans() }}</span>
                                <span class="mutual">{{ auth()->user()->getMutualFriendsCount($request->user->id) }} ortak arkadaş</span>
                            </div>
                        </div>
                        <div class="request-actions">
                            <button class="btn btn-accept" onclick="showAcceptModal('{{ $request->user->name }}', {{ $request->id }})">
                                <i class="fas fa-check"></i>
                                Kabul Et
                            </button>
                            <button class="btn btn-reject" onclick="showRejectModal('{{ $request->user->name }}', {{ $request->id }})">
                                <i class="fas fa-times"></i>
                                Reddet
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <div class="empty-cloud">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>Gelen istek yok</h3>
                <p>Henüz size arkadaşlık isteği gönderen olmadı.</p>
            </div>
        @endif
    </div>
</div>

<!-- Arkadaşlık İsteği Kabul Modalı -->
<div id="acceptRequestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-user-check"></i> Arkadaşlık İsteğini Kabul Et</h4>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p><strong><span id="requestSenderName"></span></strong> kullanıcısının arkadaşlık isteğini kabul etmek istediğinize emin misiniz?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary close-modal">
                <i class="fas fa-times"></i> Vazgeç
            </button>
            <button class="btn btn-success" id="confirmAcceptRequest">
                <i class="fas fa-check"></i> Evet, Kabul Et
            </button>
        </div>
    </div>
</div>

<!-- Arkadaşlık İsteği Reddet Modalı -->
<div id="rejectRequestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-user-times"></i> Arkadaşlık İsteğini Reddet</h4>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p><strong><span id="rejectRequestSenderName"></span></strong> kullanıcısının arkadaşlık isteğini reddetmek istediğinize emin misiniz?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary close-modal">
                <i class="fas fa-times"></i> Vazgeç
            </button>
            <button class="btn btn-danger" id="confirmRejectRequest">
                <i class="fas fa-times"></i> Evet, Reddet
            </button>
        </div>
    </div>
</div>

<!-- Arkadaş Silme Modalı -->
<div id="removeFriendModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-user-minus"></i> Arkadaşı Kaldır</h4>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p><strong><span id="removeFriendName"></span></strong> adlı kullanıcıyı arkadaş listenizden kaldırmak istediğinize emin misiniz?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary close-modal">
                <i class="fas fa-times"></i> Vazgeç
            </button>
            <button class="btn btn-danger" id="confirmRemoveFriend">
                <i class="fas fa-user-minus"></i> Evet, Kaldır
            </button>
        </div>
    </div>
</div>

@endsection

@section('js')
    <script src="{{ asset('assets/js/friends.js') }}"></script>
@endsection
