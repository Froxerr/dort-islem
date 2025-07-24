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