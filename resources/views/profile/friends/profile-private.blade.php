@extends('layouts.app')

@section('title', 'Profil Görüntülenemiyor')

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/friends.css') }}">
    <style>
        .private-profile-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            text-align: center;
        }
        
        .private-profile-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), #ffffff);
            border-radius: 25px;
            padding: 3rem 2rem;
            box-shadow: 
                12px 12px 24px rgba(0,0,0,0.1),
                -4px -4px 24px rgba(255,255,255,0.8);
            border: 2px solid rgba(76, 175, 80, 0.2);
        }
        
        .private-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #ff6b9d 0%, #c44569 100%);
            border-radius: 50%;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            border: 4px solid rgba(255, 255, 255, 0.9);
        }
        
        .private-title {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .private-username {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }
        
        .private-message {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .back-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }
        
        .suggestion-text {
            font-size: 0.95rem;
            color: #777;
            margin-top: 1rem;
        }
    </style>
@endsection

@section('content')
<div class="friends-container">
    <!-- Geri Butonu -->
    <a href="{{ url()->previous() }}" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="private-profile-container">
        <div class="private-profile-card">
            <div class="private-icon">
                <i class="fas fa-lock"></i>
            </div>
            
            <h1 class="private-title">Profil Görüntülenemiyor</h1>
            
            <div class="private-username">{{ '@' . $user->username }}</div>
            
            <div class="private-message">
                {{ $reason }}
            </div>
            
            @if($user->profile_visibility === 'friends')
                <div class="suggestion-text">
                    Bu kullanıcıya arkadaşlık isteği gönderip profilini görüntüleyebilirsiniz.
                </div>
            @endif
            
            <div style="margin-top: 2rem;">
                <a href="{{ route('friends.search') }}" class="back-btn">
                    <i class="fas fa-search"></i>
                    Arkadaş Ara
                </a>
            </div>
        </div>
    </div>
</div>
@endsection 