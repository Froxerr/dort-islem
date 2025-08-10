let currentSpeechAnimation = null;
let autoHideTimeout = null;
let isHovering = false;
function animateSpeechBubble(speechBubble, show, message = null) {
    // Önceki animasyonu ve timeout'u temizle
    if (currentSpeechAnimation) {
        currentSpeechAnimation.kill();
        currentSpeechAnimation = null;
    }
    if (autoHideTimeout) {
        clearTimeout(autoHideTimeout);
        autoHideTimeout = null;
    }

    // Eğer hover durumundaysa ve gizleme isteği geldiyse işlemi iptal et
    if (isHovering && !show) return;

    // Görünürlük ve içerik yönetimi
    if (show) {
        // Mesajı güncelle ve balonu göster
        if (message) {
            speechBubble.textContent = message;
        }

        // Önce display'i ayarla
        speechBubble.style.display = 'block';

        // Bir sonraki frame'de animasyonu başlat
        requestAnimationFrame(() => {
            speechBubble.style.visibility = 'visible';
            currentSpeechAnimation = gsap.to(speechBubble, {
                opacity: 1,
                y: 0,
                duration: 0.3,
                ease: "power2.out"
            });
        });
    } else {
        // Gizleme animasyonu
        currentSpeechAnimation = gsap.to(speechBubble, {
            opacity: 0,
            y: -20,
            duration: 0.3,
            ease: "power2.in",
            onComplete: () => {
                speechBubble.style.visibility = 'hidden';
                speechBubble.style.display = 'none';
                currentSpeechAnimation = null;
            }
        });
    }
}

function createStars() {
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;

    let staticShadows = [];
    let twinklingShadows = [];

    // Statik yıldızlar
    for (let i = 0; i < 150; i++) {
        const x = Math.random() * windowWidth;
        const y = Math.random() * windowHeight;
        const size = Math.random() * 2 + 0.5;
        const opacity = Math.random() * 0.8 + 0.2;

        staticShadows.push(`${x}px ${y}px 0 ${size}px rgba(255,255,255,${opacity})`);
    }

    // Parıldayan yıldızlar
    for (let i = 0; i < 50; i++) {
        const x = Math.random() * windowWidth;
        const y = Math.random() * windowHeight;
        const size = Math.random() * 3 + 1;
        const opacity = Math.random() * 0.6 + 0.4;

        twinklingShadows.push(`${x}px ${y}px 0 ${size}px rgba(255,255,255,${opacity})`);
    }

    // CSS kuralları oluştur
    const style = document.createElement('style');
    style.textContent = `
        .stars::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 1px;
            height: 1px;
            box-shadow: ${staticShadows.join(', ')};
        }

        .twinkling::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 1px;
            height: 1px;
            box-shadow: ${twinklingShadows.join(', ')};
            animation: twinkle 3s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }
    `;
    document.head.appendChild(style);
}

// Login sayfası için özel JavaScript
document.addEventListener('DOMContentLoaded', () => {
    // Takım yıldızları olmadan yıldızları oluştur
    createStars();

    // Baykuş animasyonları
    const isMobile = window.innerWidth <= 768;
    const duration = isMobile ? 0.7 : 1;

    const speechBubble = document.querySelector('.speech-bubble');
    const buboContainer = document.querySelector('.bubo-container');

    // Başlangıçta konuşma balonunu gizle
    speechBubble.style.opacity = 0;
    speechBubble.style.visibility = 'hidden';
    speechBubble.style.display = 'none';

    // Başlangıç animasyonları
    const startTimeline = gsap.timeline({
        onComplete: () => {
            setTimeout(() => {
                animateSpeechBubble(speechBubble, true, "Tekrar hoş geldin kaşif! Maceralarına kaldığın yerden devam edebilirsin.");

                setTimeout(() => {
                    animateSpeechBubble(speechBubble, false);
                }, 5000);
            }, 500);
        }
    });

    startTimeline
        .from(buboContainer, {
            x: 100,
            opacity: 0,
            duration: duration * 1.5,
            ease: "back.out(1.7)",
            onComplete: () => {
                gsap.to(buboContainer, {
                    rotation: isMobile ? 2 : 3,
                    duration: 2,
                    repeat: -1,
                    yoyo: true,
                    ease: "power1.inOut",
                    transformOrigin: "top center"
                });
            }
        })
        .from('.auth-form', {
            scale: 0,
            opacity: 0,
            duration: duration * 1.2,
            ease: "elastic.out(1, 0.5)",
            delay: 0.3
        });
});
