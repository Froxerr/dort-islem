// Intersection Observer için yapılandırma
const observerOptions = {
    root: null,
    rootMargin: '50px',
    threshold: 0.1
};

// Lazy loading için Intersection Observer
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Throttle fonksiyonu
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Progress bar animasyonlarını yönet
function initProgressBars() {
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.dataset.progress;
        bar.style.width = '0';
        requestAnimationFrame(() => {
            bar.style.width = width + '%';
        });
    });
}

// Filtre butonlarını yönet
function initFilterButtons() {
    const filterContainers = document.querySelectorAll('.filter-buttons');
    
    filterContainers.forEach(container => {
        container.addEventListener('click', (e) => {
            const filterBtn = e.target.closest('.filter-btn');
            if (!filterBtn) return;

            // Aktif butonu güncelle
            container.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            filterBtn.classList.add('active');

            // Filtreleme işlemini yap
            const filterValue = filterBtn.dataset.filter;
            const itemsContainer = container.nextElementSibling;
            const items = itemsContainer.children;

            if (filterValue === 'all') {
                Array.from(items).forEach(item => {
                    item.style.display = 'block';
                    requestAnimationFrame(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    });
                });
            } else {
                Array.from(items).forEach(item => {
                    const matches = item.dataset.category === filterValue;
                    item.style.display = matches ? 'block' : 'none';
                    if (matches) {
                        requestAnimationFrame(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        });
                    }
                });
            }
        });
    });
}

// Kartları lazy load ile yükle
function initLazyLoading() {
    const cards = document.querySelectorAll('.badge-card, .stat-card');
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        observer.observe(card);
    });
}

// Scroll olayını optimize et
const optimizedScroll = throttle(() => {
    // Scroll işlemleri
}, 100);

window.addEventListener('scroll', optimizedScroll);

// Sayfa yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', () => {
    initProgressBars();
    initFilterButtons();
    initLazyLoading();
});

// Rozet detaylarını göster
function showBadgeDetails(badgeId) {
    const badge = document.querySelector(`[data-badge-id="${badgeId}"]`);
    if (!badge) return;

    // Badge detaylarını al
    const details = {
        name: badge.querySelector('.badge-name').textContent,
        description: badge.querySelector('.badge-description').textContent,
        progress: badge.querySelector('.progress-fill').dataset.progress,
        reward: badge.querySelector('.badge-reward').dataset.reward
    };

    // Modal içeriğini oluştur
    const modalContent = `
        <div class="badge-details">
            <img src="${badge.querySelector('.badge-icon').src}" alt="${details.name}" class="badge-icon-large">
            <h3>${details.name}</h3>
            <p>${details.description}</p>
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${details.progress}%"></div>
                </div>
                <div class="progress-text">
                    <span>İlerleme: ${details.progress}%</span>
                </div>
            </div>
            <div class="badge-reward">
                <i class="fas fa-gem"></i>
                <span>${details.reward} XP</span>
            </div>
        </div>
    `;

    // Modal'ı göster
    showModal(modalContent);
}

// Modal göster/gizle
function showModal(content) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            ${content}
        </div>
    `;

    document.body.appendChild(modal);

    // Animasyon için setTimeout kullan
    requestAnimationFrame(() => {
        modal.classList.add('show');
    });

    // Kapatma olaylarını ekle
    modal.querySelector('.modal-close').addEventListener('click', () => {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    });

    modal.querySelector('.modal-overlay').addEventListener('click', () => {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    });
}

// Başarım detaylarını göster
function showAchievementDetails(achievementId) {
    const achievement = document.querySelector(`[data-achievement-id="${achievementId}"]`);
    if (!achievement) return;

    // Achievement detaylarını al
    const details = {
        name: achievement.querySelector('.achievement-name').textContent,
        description: achievement.querySelector('.achievement-description').textContent,
        progress: achievement.querySelector('.progress-fill').dataset.progress,
        reward: achievement.querySelector('.achievement-reward').dataset.reward,
        requirements: achievement.dataset.requirements
    };

    // Modal içeriğini oluştur
    const modalContent = `
        <div class="achievement-details">
            <div class="achievement-icon-large ${achievement.classList.contains('completed') ? 'completed' : ''}">
                <i class="fas fa-trophy"></i>
            </div>
            <h3>${details.name}</h3>
            <p>${details.description}</p>
            <div class="requirements">
                <h4>Gereksinimler:</h4>
                <p>${details.requirements}</p>
            </div>
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${details.progress}%"></div>
                </div>
                <div class="progress-text">
                    <span>İlerleme: ${details.progress}%</span>
                </div>
            </div>
            <div class="achievement-reward">
                <i class="fas fa-gem"></i>
                <span>${details.reward} XP</span>
            </div>
        </div>
    `;

    // Modal'ı göster
    showModal(modalContent);
} 