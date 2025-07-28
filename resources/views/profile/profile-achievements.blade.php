@extends('layouts.app')

@section('title', 'Başarımlarım')

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/profile-achievements.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection

@section('content')
<div class="achievements-container">
    <!-- Geri Dön Butonu -->
    <a href="{{ route('profile.hub') }}" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

    <!-- İstatistikler -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-value">{{ $totalBadges }}</div>
            <div class="stat-label">Toplam Rozet</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $earnedBadges }}</div>
            <div class="stat-label">Kazanılan Rozet</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $totalXP }}</div>
            <div class="stat-label">Toplam XP</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $completionRate }}%</div>
            <div class="stat-label">Tamamlanma</div>
        </div>
    </div>

    <!-- Tab Sistemi -->
    <div class="achievements-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="badges">
                <i class="fas fa-medal"></i> Rozetler
            </button>
            <button class="tab-btn" data-tab="achievements">
                <i class="fas fa-trophy"></i> Başarımlar
            </button>
        </div>

        <!-- Rozetler Tab İçeriği -->
        <div class="tab-content active" id="badges-content">
            <!-- Rozet Filtreleri -->
        <div class="filter-buttons">
                <button class="filter-btn active" data-section="badges" data-filter="all">
                    <i class="fas fa-th-large"></i> Tümü
                </button>
            @foreach($topics as $topic)
                    <button class="filter-btn" data-section="badges" data-filter="{{ $topic->id }}">
                        <i class="fas fa-{{ $topic->icon ?? 'star' }}"></i> {{ $topic->name }}
                    </button>
            @endforeach
        </div>

            <!-- Rozet Slider -->
            <div class="swiper badges-slider">
                <div class="swiper-wrapper">
            @foreach($badges as $badge)
                @php
                    $trigger = $badge->triggers->first();
                    $topicId = $trigger && $trigger->topic_id ? $trigger->topic_id : null;
                @endphp
                <div class="swiper-slide">
                    <div class="badge-card"
                         data-badge-id="{{ $badge->id }}"
                         data-topic="{{ $topicId }}"
                         data-section="badges">
                        
                        <img src="{{ asset('assets/img/badges/' . $badge->icon_filename) }}" 
                             alt="{{ $badge->name }}" 
                             class="badge-icon">
                        
                        <h3 class="badge-name">{{ $badge->name }}</h3>

                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" 
                                     data-progress="{{ $badge->progress }}"
                                     style="width: {{ $badge->progress }}%">
                                </div>
                            </div>
                            <div class="progress-text">
                                <span>{{ $badge->progress }}%</span>
                            </div>
                        </div>

                        <div class="badge-reward" data-reward="{{ $badge->achievement->xp_reward }}">
                            <span>{{ $badge->achievement->xp_reward }} XP</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
    </div>
        </div>

        <!-- Başarımlar Tab İçeriği -->
        <div class="tab-content" id="achievements-content">
            <!-- Başarım Filtreleri -->
            <div class="filter-buttons">
                <button class="filter-btn active" data-section="achievements" data-filter="all">
                    <i class="fas fa-th-large"></i> Tümü
                </button>
                <button class="filter-btn" data-section="achievements" data-filter="completed">
                    <i class="fas fa-check-circle"></i> Tamamlanan
                </button>
                <button class="filter-btn" data-section="achievements" data-filter="in-progress">
                    <i class="fas fa-hourglass-half"></i> Devam Eden
                </button>
                    </div>

            <!-- Başarım Slider -->
            <div class="swiper achievements-slider">
                <div class="swiper-wrapper">
                    @foreach($achievements as $achievement)
                        <div class="swiper-slide">
                            <div class="badge-card"
                                 data-badge-id="{{ $achievement->id }}"
                                 data-status="{{ $achievement->is_completed ? 'completed' : 'in-progress' }}"
                                 data-section="achievements">
                                
                                <h3 class="badge-name">{{ $achievement->name }}</h3>

                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress-fill" 
                                 data-progress="{{ $achievement->progress }}"
                                 style="width: {{ $achievement->progress }}%">
                            </div>
                        </div>
                        <div class="progress-text">
                            <span>{{ $achievement->progress }}%</span>
                        </div>
                    </div>

                                <div class="badge-reward" data-reward="{{ $achievement->xp_reward }}">
                        <span>{{ $achievement->xp_reward }} XP</span>
                    </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let isProcessing = false;
    let currentTab = 'badges';
    let badgesSwiper, achievementsSwiper;

    // Tab Sistemi
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    function initializeSlider(element, options) {
        return new Swiper(element, {
            ...options,
            initialSlide: 1,
            on: {
                init: function() {
                    this.slides.forEach(slide => slide.style.display = '');
                }
            }
        });
    }

    // Slider Ayarları
    const sliderOptions = {
        slidesPerView: 1,
        spaceBetween: 30,
        centeredSlides: true,
        loop: true,
        loopedSlides: 4,
        speed: 400,
        watchSlidesProgress: true,
        grabCursor: true,
        preventClicks: true,
        preventClicksPropagation: true,
        touchReleaseOnEdges: true,
        autoplay: false,
        effect: 'coverflow',
        coverflowEffect: {
            rotate: 0,
            stretch: 0,
            depth: 100,
            modifier: 2,
            slideShadows: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
            dynamicBullets: true
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
            },
            1024: {
                slidesPerView: 3,
            }
        }
    };

    // Slider'ları başlat
    badgesSwiper = initializeSlider('.badges-slider', sliderOptions);
    achievementsSwiper = initializeSlider('.achievements-slider', sliderOptions);

    function switchTab(newTab) {
        if (isProcessing || currentTab === newTab) return;
        isProcessing = true;

        const oldContent = document.getElementById(`${currentTab}-content`);
        const newContent = document.getElementById(`${newTab}-content`);
        
        // Eski içeriği gizle
        oldContent.style.display = 'none';
        oldContent.classList.remove('active');

        // Yeni içeriği göster
        newContent.style.display = 'block';
        newContent.classList.add('active');

        // Button durumlarını güncelle
        document.querySelector(`.tab-btn[data-tab="${currentTab}"]`).classList.remove('active');
        document.querySelector(`.tab-btn[data-tab="${newTab}"]`).classList.add('active');

        // Slider'ı resetle ve güncelle
        const swiper = newTab === 'badges' ? badgesSwiper : achievementsSwiper;
        swiper.el.classList.remove('filtering');
        swiper.slides.forEach(slide => {
            slide.style.display = '';
            slide.style.transform = '';
            slide.style.opacity = '';
        });

        // Swiper'ı güncelle ve sarsıntısız başlangıç
        swiper.update();
        swiper.slideTo(1, 0, false);

        // Aktif filtreyi uygula
        const activeFilter = newContent.querySelector('.filter-btn.active');
        if (activeFilter) {
            filterItems(newContent, activeFilter.dataset.filter);
        }

        currentTab = newTab;
        
        // Kısa bir süre sonra yeni tıklamalara izin ver
        setTimeout(() => {
            isProcessing = false;
        }, 200);
    }

    // Tab butonlarına tıklama olayı
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (!isProcessing) {
                switchTab(button.dataset.tab);
            }
        });
    });

    function filterItems(section, filterValue) {
        const swiper = section.id === 'badges-content' ? badgesSwiper : achievementsSwiper;
        const slides = swiper.slides;
        let visibleCount = 0;

        // Filtreleme modunu aktifleştir
        swiper.el.classList.add('filtering');

        // Tüm slide'ları sıfırla
        slides.forEach(slide => {
            slide.style.display = '';
            slide.style.transform = '';
            slide.style.opacity = '';
        });

        // Filtreleme yap
        slides.forEach(slide => {
            const card = slide.querySelector('.badge-card');
            if (!card) return;

            let willShow = false;
            if (filterValue === 'all') {
                willShow = true;
            } else if (section.id === 'achievements-content') {
                willShow = card.dataset.status === filterValue;
            } else {
                const topicId = card.dataset.topic;
                willShow = topicId === null || topicId === 'null' || topicId === filterValue;
            }

            slide.style.display = willShow ? '' : 'none';
            if (willShow) visibleCount++;
        });

        // Slider'ı güncelle
        swiper.update();
        swiper.slideTo(1, 0, false);

        // Sonuç mesajını göster/gizle
        const noResultsMessage = section.querySelector('.no-results-message');
        if (visibleCount === 0) {
            if (!noResultsMessage) {
                const message = document.createElement('div');
                message.className = 'no-results-message';
                message.innerHTML = `
                    <i class="fas fa-info-circle"></i>
                    <p>Bu kategoride henüz bir şey yok.</p>
                `;
                section.appendChild(message);
            }
            section.querySelector('.swiper-button-next').style.display = 'none';
            section.querySelector('.swiper-button-prev').style.display = 'none';
            section.querySelector('.swiper-pagination').style.display = 'none';
        } else {
            if (noResultsMessage) {
                noResultsMessage.remove();
            }
            section.querySelector('.swiper-button-next').style.display = '';
            section.querySelector('.swiper-button-prev').style.display = '';
            section.querySelector('.swiper-pagination').style.display = '';
        }

        // Filtreleme modunu kapat (eğer 'all' seçiliyse)
        if (filterValue === 'all') {
            setTimeout(() => {
                swiper.el.classList.remove('filtering');
            }, 100);
        }
    }

    // Filtre butonlarına tıklama olayı
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (isProcessing) return;

            const sectionId = this.dataset.section === 'achievements' ? 'achievements-content' : 'badges-content';
            const section = document.getElementById(sectionId);

            section.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            filterItems(section, this.dataset.filter);
        });
    });

    // Başlangıç filtrelerini uygula
    document.querySelectorAll('.tab-content').forEach(section => {
        const activeButton = section.querySelector('.filter-btn.active');
        if (activeButton) {
            filterItems(section, activeButton.dataset.filter);
        }
    });

    // Kartlara hover efekti
    document.querySelectorAll('.badge-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.closest('.swiper-slide-active')) return;
            this.style.transform = 'translateY(-10px) scale(1.02)';
            const icon = this.querySelector('.badge-icon');
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
            }
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            const icon = this.querySelector('.badge-icon');
            if (icon) {
                icon.style.transform = '';
            }
        });
    });
});
</script>
@endsection 