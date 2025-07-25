@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}">
@endsection

@section('content')
<a href="{{ route('profile.hub') }}" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="profile-container">
    <!-- Sol Bölüm -->
    <div class="left-section profile-section">
        <div class="user-info">
            <h2>{{ $user->name }}</h2>
            <p>Seviye {{ $user->level }}</p>
            <p>{{ $user->xp }} XP</p>
        </div>

        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-value">{{ $totalTests }}</div>
                <div class="stat-label">Toplam Test</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $averageAccuracy }}%</div>
                <div class="stat-label">Ortalama Başarı</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $totalXP }}</div>
                <div class="stat-label">Toplam XP</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $highestScore }}</div>
                <div class="stat-label">En Yüksek Skor</div>
            </div>
        </div>
    </div>

    <!-- Orta Bölüm -->
    <div class="center-section profile-section">
        <div class="tree-container">
            <div class="tree-sparkles"></div>
            <div class="levelup-animation">
                <div class="levelup-shine"></div>
            </div>
            <img src="{{ asset('assets/img/trees/level_' . min(9, floor($user->level/10)) . '.png') }}" 
                 alt="Seviye Ağacı" 
                 class="tree-image">
        </div>
        
        <div class="xp-progress">
            <div class="xp-bar" style="width: {{ $xpProgressPercentage }}%"></div>
        </div>
        <p>Sonraki seviyeye {{ $xpNeeded }} XP kaldı</p>
    </div>

    <!-- Sağ Bölüm -->
    <div class="right-section profile-section">
        <h3>Rozetlerim</h3>
        <div class="badge-grid">
            @foreach($userBadges as $badge)
            <div class="badge-item badge-glow {{ $badge->is_new ? 'new' : '' }}" 
                 data-earned="{{ $badge->earned_at }}"
                 title="{{ $badge->description }}">
                <img src="{{ asset('assets/img/badges/' . ($badge->image ?? 'default.png')) }}" 
                     alt="{{ $badge->name }}">
                <div class="badge-name">{{ $badge->name }}</div>
            </div>
            @endforeach
        </div>

        <div class="activities-section">
            <h3 class="activities-title">Son Aktiviteler</h3>
            <div class="activities">
                @foreach($recentActivities as $activity)
                <div class="activity-item">
                    <p>{{ $activity->description }}</p>
                    <small>{{ $activity->created_at->diffForHumans() }}</small>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pırıltı efekti oluşturma
    function createSparkle() {
        const sparkle = document.createElement('div');
        sparkle.className = 'sparkle';
        
        // Rastgele pozisyon
        const treeContainer = document.querySelector('.tree-sparkles');
        const x = Math.random() * treeContainer.offsetWidth;
        const y = Math.random() * treeContainer.offsetHeight;
        
        sparkle.style.left = x + 'px';
        sparkle.style.top = y + 'px';
        
        treeContainer.appendChild(sparkle);
        
        // Animasyon bitince elementi kaldır
        sparkle.addEventListener('animationend', () => {
            sparkle.remove();
        });
    }

    // Düzenli aralıklarla pırıltı oluştur
    setInterval(createSparkle, 300);

    // Level up animasyonu
    function playLevelUpAnimation() {
        const shine = document.querySelector('.levelup-shine');
        
        gsap.fromTo(shine, 
            { opacity: 0, scale: 0.8 },
            { 
                opacity: 1, 
                scale: 1.2, 
                duration: 1,
                ease: "power2.out",
                onComplete: () => {
                    gsap.to(shine, {
                        opacity: 0,
                        scale: 1.4,
                        duration: 0.5
                    });
                }
            }
        );
    }

    // Yeni rozet animasyonu
    const newBadges = document.querySelectorAll('.badge-item.new');
    newBadges.forEach(badge => {
        gsap.from(badge, {
            scale: 0.5,
            opacity: 0,
            duration: 0.5,
            ease: "back.out(1.7)"
        });
    });

    // Aktivite kartları için giriş animasyonu
    gsap.from('.activity-item', {
        y: 50,
        opacity: 0,
        duration: 0.5,
        stagger: 0.1,
        ease: "power2.out"
    });

    @if(session('levelup'))
    playLevelUpAnimation();
    @endif
});
</script>
@endsection 