function showLevelUpNotification(notification) {
    const data = notification.data;
    showAchievement({
        type: 'level-up',
        icon: '⭐',
        title: 'Seviye Atladın!',
        description: data.message,
        stats: [
            { value: data.data.xp_earned, label: 'Kazanılan XP' },
            { value: data.data.next_level_xp, label: 'Sonraki Seviye' }
        ],
        progress: (data.data.xp_earned / data.data.next_level_xp) * 100
    });
}

function showBadgeNotification(notification) {
    const data = notification.data;
    showAchievement({
        type: 'badge',
        icon: `/assets/img/badges/${data.data.badge_icon}`,
        title: 'Yeni Rozet Kazandın!',
        description: data.message,
        stats: [
            { value: data.data.xp_reward, label: 'Bonus XP' },
            { value: `${data.data.progress.current}/${data.data.progress.required}`, label: 'İlerleme' }
        ],
        progress: (data.data.progress.current / data.data.progress.required) * 100
    });
}

function showAchievement(data) {
    // Confetti efekti - daha kısa süre
    const duration = 1500; // 3000'den 1500'e düşürdük
    const animationEnd = Date.now() + duration;
    const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 1001 };

    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    const interval = setInterval(function() {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        const particleCount = 50 * (timeLeft / duration);

        confetti(Object.assign({}, defaults, {
            particleCount,
            origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
        }));
        confetti(Object.assign({}, defaults, {
            particleCount,
            origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
        }));
    }, 200); // 250'den 200'e düşürdük

    // Overlay ve modal oluştur
    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    
    const modal = document.createElement('div');
    modal.className = `achievement-modal ${data.type}-modal`;
    
    const iconContent = data.type === 'badge' 
        ? `<img src="${data.icon}" alt="Badge Icon">` 
        : data.icon;

    modal.innerHTML = `
        <div class="achievement-glow"></div>
        <div class="achievement-icon">${iconContent}</div>
        <h2 class="achievement-title">${data.title}</h2>
        <p class="achievement-description">${data.description}</p>
        <div class="stats-container">
            ${data.stats.map(stat => `
                <div class="stat-item">
                    <div class="stat-value">${stat.value}</div>
                    <div class="stat-label">${stat.label}</div>
                </div>
            `).join('')}
        </div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        <div class="action-buttons">
            <button class="continue-btn" onclick="closeAchievement(this)">Devam Et</button>
            <button class="share-btn" onclick="shareAchievement()">
                <i class="fas fa-share-alt"></i> Paylaş
            </button>
        </div>
    `;

    document.body.appendChild(overlay);
    document.body.appendChild(modal);

    // Progress bar animasyonu - gecikmeyi kaldırdık
    setTimeout(() => {
        modal.querySelector('.progress-fill').style.width = data.progress + '%';
    }, 100); // 500'den 100'e düşürdük
}

function closeAchievement(button) {
    const modal = button.closest('.achievement-modal');
    const overlay = document.querySelector('.overlay');
    
    modal.style.animation = 'fadeOut 0.3s forwards';
    overlay.style.animation = 'fadeOut 0.3s forwards';
    
    setTimeout(() => {
        modal.remove();
        overlay.remove();
    }, 300);
}

function shareAchievement() {
    // Paylaşım fonksiyonu buraya eklenebilir
    alert('Paylaşım özelliği yakında!');
} 