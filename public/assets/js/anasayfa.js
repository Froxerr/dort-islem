// Mevcut animasyonlar
const isMobile = window.innerWidth <= 768;
const duration = isMobile ? 0.7 : 1;

// Bulutların animasyonu
document.querySelectorAll('.cloud').forEach((cloud, index) => {
    gsap.to(cloud, {
        x: "+=30",
        duration: (isMobile ? 8 : 10) + index * 2,
        repeat: -1,
        yoyo: true,
        ease: "power1.inOut"
    });
});

// Ağaçların animasyonu
document.querySelectorAll('.tree').forEach(tree => {
    gsap.to(tree, {
        rotation: isMobile ? 0.5 : 1,
        duration: 3,
        repeat: -1,
        yoyo: true,
        transformOrigin: "bottom",
        ease: "power1.inOut"
    });
});

// Uygulama verileri
const appData = JSON.parse(document.getElementById('app-data').textContent);
const { topics, difficultyLevels } = appData;

// Seçilen konu
let selectedTopic = null;

// Konuşma metinleri
const topicMessages = {
    1: "Toplama işlemi ile başlayalım! Bu çok eğlenceli olacak!",
    2: "Çıkarma işlemlerinde ustalaşma zamanı!",
    3: "Çarpma işlemi benim favorim! Hadi başlayalım!",
    4: "Bölme işlemini birlikte öğreneceğiz!"
};

// Zorluk seviyesi mesajları
const difficultyMessages = {
    "Kolay": "Temel seviyeden başlayalım!",
    "Orta": "Biraz zorlayıcı olabilir!",
    "Zor": "İşte gerçek bir meydan okuma!",
    "Dahi": "Sen bir dahisin!"
};

// Animasyon yönetimi için global değişkenler
let currentSpeechAnimation = null;
let autoHideTimeout = null;
let isHovering = false;

// Global değişkenler
let currentTimer = null;
let currentQuestion = null;
let isAnimating = false; // Global animasyon durumu için eklendi
let scores = {
    correct: 0,
    wrong: 0
};
let gameActive = false;
let gameStartTime = null;
let timerAnimation = null;
let timerInterval = null;
let globalTimerInterval = null;
let sessionData = {
    selectedTopic: null,
    selectedDifficulty: null,
    topicName: '',
    difficultyName: '',
    xpMultiplier: 1,
    scores: {
        correct: 0,
        wrong: 0
    }
};

// Puan hesaplama fonksiyonu
function calculateScore() {
    const totalQuestions = sessionData.scores.correct + sessionData.scores.wrong;
    if (totalQuestions === 0) {
        return {
            baseScore: 0,
            accuracyRate: 0,
            bonusMultiplier: 1,
            bonusMessage: 'Henüz Başlangıç!',
            xpMultiplier: sessionData.xpMultiplier || 1,
            finalScore: 0
        };
    }

    // Temel puan hesaplama
    const basePuan = sessionData.scores.correct * 10;
    
    // Doğruluk oranı hesaplama
    const accuracyRate = sessionData.scores.correct / totalQuestions;
    
    // Bonus çarpanı belirleme
    let bonusMultiplier = 0;
    let bonusMessage = '';
    
    if (accuracyRate >= 0.95) {
        bonusMultiplier = 1.5;
        bonusMessage = 'Muhteşem Performans! Sen Bir Dahisin!';
    } else if (accuracyRate >= 0.80) {
        bonusMultiplier = 1.2;
        bonusMessage = 'İnanılmaz Başarı! Böyle Devam Et!';
    } else if (accuracyRate >= 0.65) {
        bonusMultiplier = 1.1;
        bonusMessage = 'Harika İlerleme! Potansiyelin Yüksek!';
    } else if (accuracyRate >= 0.50) {
        bonusMultiplier = 1;
        bonusMessage = 'İyi Gidiyorsun! Kendini Geliştirmeye Devam Et!';
    } else {
        bonusMultiplier = 1;
        bonusMessage = 'Her Başarısızlık Yeni Bir Öğrenme Fırsatı!';
    }

    // Nihai puan hesaplama
    const finalScore = Math.round((basePuan * bonusMultiplier) * (sessionData.xpMultiplier || 1));

    return {
        baseScore: basePuan,
        accuracyRate: accuracyRate * 100,
        bonusMultiplier: bonusMultiplier,
        bonusMessage: bonusMessage,
        xpMultiplier: sessionData.xpMultiplier || 1,
        finalScore: finalScore
    };
}

// Konuşma balonu animasyonlarını yönet
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

// Sahne geçiş animasyonu
function transitionToMathScene() {
    // Önce HTML'i ekle
    createMathElements();

    // DOM elementlerini seç
    const calculator = document.querySelector('.calculator-container');
    const topicButtons = document.querySelectorAll('.topic-button');

    const timeline = gsap.timeline({
        onComplete: () => {
            setupTopicHoverEffects();
        }
    });

    // Performans için animasyonları grupla
    timeline
        .to('.cloud', {
            opacity: 0,
            scale: 0,
            duration: 0.5,
            stagger: 0.1,
            ease: "back.in(1.7)"
        })
        .to('.nature-bg', {
            backgroundColor: '#f0f9ff',
            duration: 0.5,
            ease: "power2.inOut"
        }, "-=0.3");

    const buboContainer = document.querySelector('.bubo-container');
    const buboImage = buboContainer.querySelector('img');

    // Baykuş animasyonlarını grupla
    timeline
        .to(buboContainer, {
            right: "100%",
            duration: 0.4,
            ease: "power1.in",
            onComplete: () => {
                buboImage.src = '/assets/img/dalkus-right.png';
                buboContainer.classList.add('left-side');
                gsap.set(buboContainer, { 
                    clearProps: "right",
                    left: "0px"
                });
            }
        })
        .to(buboContainer, {
            left: "-100px",
            duration: 0.4,
            ease: "power1.out"
        });

    // Ağaç animasyonlarını grupla
    timeline
        .to(['.tree.left', '.tree.right'], {
            x: (index) => index === 0 ? "-=30" : "+=30",
            duration: 0.5,
            ease: "power2.inOut",
            stagger: 0.1
        }, "-=0.4");

    // Hesap makinesi ve butonları grupla
    timeline
        .fromTo(calculator, 
            {
                opacity: 0,
                scale: 0,
                visibility: 'visible'
            },
            {
                opacity: 1,
                scale: 1,
                duration: 0.5,
                ease: "back.out(1.7)"
            }
        )
        .fromTo(topicButtons, 
            {
                opacity: 0,
                scale: 0,
                visibility: 'visible'
            },
            {
                opacity: 1,
                scale: 1,
                duration: 0.5,
                stagger: 0.1,
                ease: "back.out(1.7)",
                clearProps: "all"
            }
        );
}

// Matematik elementlerini oluştur
function createMathElements() {
    // Eğer zaten animasyon devam ediyorsa, yeni element oluşturmayı engelle
    if (document.querySelector('.calculator-container')) return;

    const mathHTML = `
        <div class="calculator-container" style="visibility: hidden;">
            <div class="calculator">
                <div class="calculator-screen">
                    <div class="screen-decoration">
                        <div class="solar-panel"></div>
                        <div class="brand">Keşif Hesap</div>
                    </div>
                    <div class="screen-display">
                        Konuyu Seç
                    </div>
                </div>
                <div class="calculator-body">
                    <div class="topic-grid">
                        ${topics.map(topic => `
                            <button class="topic-button" data-topic="${topic.id}">
                                <img src="/assets/img/${topic.icon_path}" alt="${topic.name}">
                                <span>${topic.name}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.querySelector('.nature-bg').insertAdjacentHTML('beforeend', mathHTML);
}

// Zorluk seviyesi elementlerini oluştur
function createDifficultyElements() {
    const difficultyHTML = `
        <div class="calculator-container" style="visibility: hidden;">
            <div class="calculator">
                <div class="calculator-screen">
                    <div class="screen-decoration">
                        <div class="solar-panel"></div>
                        <div class="brand">Keşif Hesap</div>
                    </div>
                    <div class="screen-display">
                        Zorluk Seviyesini Seç
                    </div>
                </div>
                <div class="calculator-body">
                    <div class="difficulty-grid">
                        ${difficultyLevels.map(level => `
                            <button class="difficulty-button" data-level-id="${level.id}" data-level-name="${level.name}">
                                <img src="/${level.image_path}" alt="${level.name}">
                                <span>${level.name}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.querySelector('.nature-bg').insertAdjacentHTML('beforeend', difficultyHTML);
}

// Konu seçimi
function handleTopicSelection(topicId, topicName) {
    if (isAnimating) return;
    isAnimating = true;

    console.log('Konu seçildi:', {
        'Konu ID': topicId,
        'Konu Adı': topicName
    });

    selectedTopic = topicId;
    sessionData.selectedTopic = topicId;
    sessionData.topicName = topicName;
    
    // Mevcut hesap makinesini kaldır
    const currentCalculator = document.querySelector('.calculator-container');
    if (!currentCalculator) {
        isAnimating = false;
        return;
    }

    gsap.to(currentCalculator, {
        opacity: 0,
        scale: 0,
        duration: 0.3,
        ease: "back.in(1.7)",
        onComplete: () => {
            currentCalculator.remove();
            
            // Zorluk seviyelerini göster
            createDifficultyElements();
            const newCalculator = document.querySelector('.calculator-container');
            
            gsap.fromTo(newCalculator,
                {
                    opacity: 0,
                    scale: 0,
                    visibility: 'visible'
                },
                {
                    opacity: 1,
                    scale: 1,
                    duration: 0.5,
                    ease: "back.out(1.7)",
                    onComplete: () => {
                        setupDifficultyHoverEffects();
                        isAnimating = false;
                    }
                }
            );
        }
    });
}

// Zorluk seviyesi hover efektlerini ayarla
function setupDifficultyHoverEffects() {
    const speechBubble = document.querySelector('.speech-bubble');
    
    document.querySelectorAll('.difficulty-button').forEach(button => {
        const levelName = button.dataset.levelName;
        const message = difficultyMessages[levelName];

        button.addEventListener('mouseenter', () => {
            animateSpeechBubble(speechBubble, true, message);
        });

        button.addEventListener('mouseleave', () => {
            animateSpeechBubble(speechBubble, false);
        });

        // Tıklama olayı
        button.addEventListener('click', () => {
            const levelId = button.dataset.levelId;
            const xpMultiplier = button.dataset.xpMultiplier; // Assuming data-xp-multiplier is added to HTML
            handleDifficultySelection(levelId, levelName, parseFloat(xpMultiplier));
        });
    });
}

// Soru üretici sınıfını oluştur
console.log('QuestionGenerator örneği oluşturuluyor...');
const questionGenerator = new QuestionGenerator();
console.log('QuestionGenerator örneği oluşturuldu!');

// Zorluk seçimini işle
function handleDifficultySelection(difficultyId, difficultyName, xpMultiplier) {
    if (isAnimating) return;
    isAnimating = true;

    // Önceki durumu temizle
    const oldCalculator = document.querySelector('.calculator-container');
    if (!oldCalculator) {
        isAnimating = false;
        return;
    }

    // Event listener'ları temizle
    const oldButtons = oldCalculator.querySelectorAll('button');
    oldButtons.forEach(button => {
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
    });

    // Yeni durumu ayarla
    sessionData.selectedDifficulty = difficultyId;
    sessionData.difficultyName = difficultyName;
    sessionData.xpMultiplier = xpMultiplier;

    console.log('Soru üretiliyor...', {
        'Seçilen Konu': sessionData.selectedTopic,
        'Zorluk Seviyesi': difficultyName
    });

    // Soru üret
    const question = questionGenerator.generateQuestion(sessionData.selectedTopic, difficultyName);
    console.log('Üretilen Soru:', question);

    // Mevcut hesap makinesini kaldır
    gsap.to(oldCalculator, {
        opacity: 0,
        scale: 0,
        duration: 0.3,
        ease: "back.in(1.7)",
        onComplete: () => {
            oldCalculator.remove();

            // Soru ekranını göster
            createQuestionElements(question);
            const newCalculator = document.querySelector('.calculator-container');

            gsap.fromTo(newCalculator,
                {
                    opacity: 0,
                    scale: 0,
                    visibility: 'visible'
                },
                {
                    opacity: 1,
                    scale: 1,
                    duration: 0.5,
                    ease: "back.out(1.7)",
                    onComplete: () => {
                        isAnimating = false;
                    }
                }
            );

            // Baykuş mesajını güncelle
            const speechBubble = document.querySelector('.speech-bubble');
            if (speechBubble) {
                speechBubble.style.display = 'block';
                speechBubble.style.opacity = '1';
                speechBubble.style.transform = 'none';
                speechBubble.textContent = "İşlemi çöz bakalım!";

                // 2.5 saniye sonra mesajı gizle
                setTimeout(() => {
                    gsap.to(speechBubble, {
                        opacity: 0,
                        y: -10,
                        duration: 0.3,
                        ease: "power2.out",
                        onComplete: () => {
                            speechBubble.style.display = 'none';
                            speechBubble.style.transform = 'none';
                        }
                    });
                }, 2500);
            }

            // Zamanlayıcıyı başlat
            startTimer();
        }
    });
}

// Soru ekranını oluştur
function createQuestionElements(question) {
    currentQuestion = question;
    const questionHTML = `
        <div class="calculator-container" style="visibility: hidden;">
            <div class="calculator">
                <div class="calculator-screen">
                    <div class="screen-decoration">
                        <div class="solar-panel"></div>
                        <div class="brand">Keşif Hesap</div>
                        <div class="score-display">
                            <div class="correct-score">✓ ${scores.correct}</div>
                            <div class="wrong-score">✗ ${scores.wrong}</div>
                        </div>
                        <div class="timer-container">
                            <div class="timer-text">60</div>
                        </div>
                        <div class="timer-bar-container">
                            <div class="timer-bar"></div>
                        </div>
                    </div>
                    <div class="screen-display">
                        <div class="question-display">
                            <span class="number">${questionGenerator.formatNumber(question.num1)}</span>
                            <span class="operator">${question.operator}</span>
                            <span class="number">${questionGenerator.formatNumber(question.num2)}</span>
                            <span class="equals">=</span>
                            <span class="answer">?</span>
                        </div>
                    </div>
                </div>
                <div class="calculator-body">
                    <div class="number-pad">
                        <div class="number-input">
                            <input type="text" id="answer-input" placeholder="Cevabı buraya yaz" 
                                   data-correct-answer="${question.answer}">
                        </div>
                        <button class="check-answer">Kontrol Et</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.querySelector('.nature-bg').insertAdjacentHTML('beforeend', questionHTML);
    setupAnswerCheck();

    // İlk soru ise zamanlayıcıyı başlat, değilse güncelle
    if (!gameStartTime) {
        startTimer();
    } else {
        // Sayfa görünürlüğünü kontrol et
        document.addEventListener('visibilitychange', () => {
            if (!gameStartTime) return;

            if (document.visibilityState === 'visible' && gameActive) {
                // Sayfa görünür olduğunda zamanlayıcıyı güncelle
                const now = Date.now();
                const elapsedSeconds = Math.floor((now - gameStartTime) / 1000);
                const remainingSeconds = Math.max(60 - elapsedSeconds, 0);

                if (remainingSeconds === 0) {
                    gameActive = false;
                    handleTimeUp();
                } else {
                    // Zamanlayıcıyı yeniden başlat
                    if (timerAnimation) timerAnimation.kill();
                    const timerBar = document.querySelector('.timer-bar');
                    if (timerBar) {
                        timerAnimation = gsap.fromTo(timerBar,
                            { scaleX: remainingSeconds / 60 },
                            {
                                scaleX: 0,
                                duration: remainingSeconds,
                                ease: "none"
                            }
                        );
                    }
                }
            }
        });
    }
}

// Yeni soru oluştur
function generateNewQuestion() {
    if (!gameActive || isAnimating) return;
    
    isAnimating = true;
    console.log('Yeni soru üretiliyor...', {
        'Konu': sessionData.selectedTopic,
        'Zorluk': sessionData.difficultyName
    });

    const currentCalculator = document.querySelector('.calculator-container');
    
    // Yeni soruyu hazırla
    const question = questionGenerator.generateQuestion(selectedTopic, currentQuestion.difficulty);
    console.log('Yeni üretilen soru:', question);
    
    gsap.to(currentCalculator, {
        opacity: 0,
        scale: 0.8,
        duration: 0.2,
        ease: "back.in(1.7)",
        onComplete: () => {
            currentCalculator.remove();
            createQuestionElements(question);
            const newCalculator = document.querySelector('.calculator-container');
            
            gsap.fromTo(newCalculator,
                {
                    opacity: 0,
                    scale: 0.8,
                    visibility: 'visible'
                },
                {
                    opacity: 1,
                    scale: 1,
                    duration: 0.2,
                    ease: "back.out(1.7)",
                    onComplete: () => {
                        isAnimating = false;
                    }
                }
            );
        }
    });
}

// Zamanlayıcıyı güncelle
function updateTimer() {
    if (!gameActive || !gameStartTime) return;

    const now = Date.now();
    const elapsedSeconds = Math.floor((now - gameStartTime) / 1000);
    const remainingSeconds = Math.max(60 - elapsedSeconds, 0);

    const timerBar = document.querySelector('.timer-bar');
    const timerText = document.querySelector('.timer-text');

    // Metin güncelleme
    timerText.textContent = remainingSeconds;

    // Çubuk animasyonu güncelleme
    if (timerAnimation) timerAnimation.kill();

    if (remainingSeconds > 0) {
        timerAnimation = gsap.fromTo(timerBar,
            { scaleX: remainingSeconds / 60 },
            {
                scaleX: 0,
                duration: remainingSeconds,
                ease: "none"
            }
        );
    } else {
        gameActive = false;
        handleTimeUp();
    }
}

// Zamanlayıcıyı başlat
function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    if (globalTimerInterval) clearInterval(globalTimerInterval);
    if (timerAnimation) timerAnimation.kill();

    gameActive = true;
    gameStartTime = Date.now();

    const timerBar = document.querySelector('.timer-bar');
    if (timerBar) {
        timerBar.style.transform = 'scaleX(1)';
    }

    // Global zamanlayıcı - sürekli güncelleme için
    function updateGlobalTimer() {
        if (!gameActive) {
            clearInterval(globalTimerInterval);
            return;
        }

        const now = Date.now();
        const elapsedSeconds = Math.floor((now - gameStartTime) / 1000);
        const remainingSeconds = Math.max(60 - elapsedSeconds, 0);

        // Timer text'i güncelle
        const timerText = document.querySelector('.timer-text');
        if (timerText) {
            timerText.textContent = remainingSeconds;
        }

        // Timer bar'ı güncelle
        const timerBar = document.querySelector('.timer-bar');
        if (timerBar) {
            const progress = remainingSeconds / 60;
            timerBar.style.transform = `scaleX(${progress})`;
        }

        // Süre dolduğunda
        if (remainingSeconds === 0 && gameActive) {
            clearInterval(globalTimerInterval);
            gameActive = false;
            handleTimeUp();
        }
    }

    // Her 50ms'de bir güncelle - daha akıcı animasyon için
    globalTimerInterval = setInterval(updateGlobalTimer, 50);
    updateGlobalTimer(); // İlk değeri hemen ayarla
}

// Süre dolduğunda
function handleTimeUp() {
    if (timerInterval) clearInterval(timerInterval);
    if (globalTimerInterval) clearInterval(globalTimerInterval);
    if (timerAnimation) timerAnimation.kill();

    const scoreData = calculateScore();

    // Motivasyonel mesaj seçimi
    let motivationalMessage = '';
    if (scoreData.accuracyRate >= 80) {
        motivationalMessage = 'İnanılmaz bir performans gösterdin! Seninle gurur duyuyorum!';
    } else if (scoreData.accuracyRate >= 60) {
        motivationalMessage = 'Harika bir çaba! Her gün biraz daha iyiye gidiyorsun!';
    } else if (scoreData.accuracyRate >= 40) {
        motivationalMessage = 'İyi iş çıkardın! Pratik yaptıkça daha da başarılı olacaksın!';
    } else {
        motivationalMessage = 'Bu sadece bir başlangıç! Bir sonraki denemende çok daha iyi olacağına eminim!';
    }

    // Baykuşu animasyonlu bir şekilde gizle
    const buboContainer = document.querySelector('.bubo-container');
    if (buboContainer) {
        gsap.to(buboContainer, {
            opacity: 0,
            y: 50,
            duration: 0.5,
            ease: "back.in(1.7)",
            onComplete: () => {
                buboContainer.style.display = 'none';
                // Opacity ve y pozisyonunu sıfırla (sonraki gösterim için)
                gsap.set(buboContainer, {
                    opacity: 1,
                    y: 0
                });
            }
        });
    }

    // Büyük sonuç baloncuğu için HTML
    const resultHTML = `
        <div class="result-bubble">
            <h2>Süre Doldu!</h2>
            <div class="message-container">
                <p class="motivational-message">${motivationalMessage}</p>
                <span class="message-signature">- Bubo</span>
            </div>
            <div class="result-details">
                <div class="result-row">
                    <div class="result-col">
                        <p><strong>Konu:</strong> ${sessionData.topicName}</p>
                        <p><strong>Zorluk:</strong> ${sessionData.difficultyName}</p>
                    </div>
                    <div class="result-col">
                        <p><strong>Doğru:</strong> <span class="success">${sessionData.scores.correct}</span></p>
                        <p><strong>Yanlış:</strong> <span class="error">${sessionData.scores.wrong}</span></p>
                    </div>
                </div>
                <div class="score-summary">
                    <div class="summary-item">
                        <strong>Doğruluk:</strong>
                        <span class="highlight">%${scoreData.accuracyRate.toFixed(1)}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Bonus:</strong>
                        <span class="success">x${scoreData.bonusMultiplier}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Zorluk:</strong>
                        <span class="success">x${scoreData.xpMultiplier}</span>
                    </div>
                </div>
                <div class="final-score">
                    <div class="xp-label">Kazanılan XP</div>
                    <div class="xp-value">${scoreData.finalScore}</div>
                </div>
            </div>
            <div class="result-actions">
                <button class="restart-btn" onclick="restartGame()">Tekrar Başla</button>
                <button class="register-btn" onclick="showRegisterPrompt()">Skorunu Kaydet</button>
            </div>
        </div>
    `;

    // Mevcut hesap makinesini kaldır ve sonuç ekranını göster
    const currentCalculator = document.querySelector('.calculator-container');
    gsap.to(currentCalculator, {
        opacity: 0,
        scale: 0.8,
        duration: 0.2,
        ease: "back.in(1.7)",
        onComplete: () => {
            currentCalculator.remove();
            document.querySelector('.nature-bg').insertAdjacentHTML('beforeend', resultHTML);
            
            // Sonuç ekranını göster
            const resultBubble = document.querySelector('.result-bubble');
            gsap.fromTo(resultBubble,
                {
                    opacity: 0,
                    scale: 0.8,
                    y: 50
                },
                {
                    opacity: 1,
                    scale: 1,
                    y: 0,
                    duration: 0.5,
                    ease: "back.out(1.7)"
                }
            );
        }
    });
}

// Sayfa yüklendiğinde çalışacak kod
document.addEventListener('DOMContentLoaded', () => {
    // İlk appData yüklemesi
    const appDataScript = document.getElementById('app-data');
    if (appDataScript) {
        try {
            window.appData = JSON.parse(appDataScript.textContent);
            console.log('İlk appData yüklemesi:', window.appData);
        } catch (e) {
            console.error('İlk appData yüklemesi hatası:', e);
        }
    }

    // Başlat butonuna tıklama olayı ekle
    const startButton = document.querySelector('.start-button');
    if (startButton) {
        startButton.addEventListener('click', handleStartButtonClick);
    }
});

// Kayıt formunu göster
function showRegisterPrompt() {
    // Eğer animasyon devam ediyorsa veya zaten bir prompt varsa, işlemi engelle
    if (isAnimating || document.querySelector('.register-prompt')) return;
    
    isAnimating = true;

    const registerHTML = `
        <div class="register-prompt">
            <h3>Skorunu Kaydetmek İster misin?</h3>
            <p>Üye olarak skorlarını kaydedebilir ve gelişimini takip edebilirsin!</p>
            <div class="register-actions">
                <a href="/register" class="register-link">Üye Ol</a>
                <button onclick="closeRegisterPrompt()" class="cancel-btn">Vazgeç</button>
            </div>
        </div>
    `;

    const resultBubble = document.querySelector('.result-bubble');
    if (resultBubble) {
        resultBubble.insertAdjacentHTML('beforeend', registerHTML);
        
        // Prompt'u animasyonla göster
        const prompt = resultBubble.querySelector('.register-prompt');
        gsap.fromTo(prompt,
            {
                opacity: 0,
                scale: 0.8,
                y: 20
            },
            {
                opacity: 1,
                scale: 1,
                y: 0,
                duration: 0.3,
                ease: "back.out(1.7)",
                onComplete: () => {
                    isAnimating = false;
                }
            }
        );
    } else {
        isAnimating = false;
    }
}

// Kayıt formunu kapat
function closeRegisterPrompt() {
    if (isAnimating) return;
    isAnimating = true;

    const prompt = document.querySelector('.register-prompt');
    if (prompt) {
        gsap.to(prompt, {
            opacity: 0,
            scale: 0.8,
            y: 20,
            duration: 0.3,
            ease: "back.in(1.7)",
            onComplete: () => {
                prompt.remove();
                isAnimating = false;
            }
        });
    } else {
        isAnimating = false;
    }
}

// Cevap kontrolü için event listener'ları ayarla
function setupAnswerCheck() {
    const checkButton = document.querySelector('.check-answer');
    const answerInput = document.querySelector('#answer-input');
    const speechBubble = document.querySelector('.speech-bubble');

    // Sayısal input kontrolü ve formatlama
    answerInput.addEventListener('input', (e) => {
        if (!gameActive) return;
        
        let value = e.target.value;
        
        if (sessionData.difficultyName === 'Zor' || sessionData.difficultyName === 'Dahi') {
            // Virgülü noktaya çevir (kullanıcı virgül girerse)
            value = value.replace(/,/g, '.');
            
            // Sadece rakamlar ve bir adet nokta izin ver
            value = value.replace(/[^\d.]/g, '');
            
            // Birden fazla nokta varsa ilkini tut
            const dots = value.match(/\./g);
            if (dots && dots.length > 1) {
                value = value.substring(0, value.lastIndexOf('.'));
            }

            // Noktadan sonra en fazla 2 basamak olsun
            if (value.includes('.')) {
                const [whole, decimal] = value.split('.');
                if (decimal && decimal.length > 2) {
                    value = whole + '.' + decimal.substring(0, 2);
                }
            }

            e.target.value = value;
        } else {
            // Kolay ve Orta seviye için sadece tam sayılar
            value = value.replace(/[^\d]/g, '');
            if (value) {
                // Binlik ayracı ekle
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                e.target.value = value;
            }
        }
    });

    // Cevap kontrolü için click event
    checkButton.addEventListener('click', () => {
        if (!gameActive || isAnimating) return;

        let userAnswer;
        const inputValue = answerInput.value;
        
        if (sessionData.difficultyName === 'Zor' || sessionData.difficultyName === 'Dahi') {
            // Virgülü noktaya çevir ve parseFloat kullan
            userAnswer = parseFloat(inputValue.replace(/,/g, '.'));
        } else {
            // Binlik ayraçları kaldır ve parseInt kullan
            userAnswer = parseInt(inputValue.replace(/\./g, ''));
        }
        
        const correctAnswer = parseFloat(answerInput.dataset.correctAnswer);

        // Input'u hemen temizle
        answerInput.value = '';

        // Cevabı kontrol et (yuvarlama farklarını tolere et)
        if (!isNaN(userAnswer) && Math.abs(userAnswer - correctAnswer) < 0.01) {
            scores.correct++;
            sessionData.scores.correct++;
            animateSpeechBubble(speechBubble, true, "Harika! Doğru cevap!");
            updateScoreDisplay();
            generateNewQuestion();
        } else {
            scores.wrong++;
            sessionData.scores.wrong++;
            animateSpeechBubble(speechBubble, true, "Tekrar dene!");
            updateScoreDisplay();
        }
    });

    // Enter tuşu ile kontrol
    answerInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && gameActive) {
            checkButton.click();
        }
    });
}

// Skor göstergesini güncelle
function updateScoreDisplay() {
    const correctScore = document.querySelector('.correct-score');
    const wrongScore = document.querySelector('.wrong-score');
    
    correctScore.textContent = `✓ ${scores.correct}`;
    wrongScore.textContent = `✗ ${scores.wrong}`;
}

// Konu butonları için hover efektlerini ayarla
function setupTopicHoverEffects() {
    const speechBubble = document.querySelector('.speech-bubble');
    
    document.querySelectorAll('.topic-button').forEach(button => {
        const topicId = button.dataset.topic;
        const topicName = button.textContent.replace(' <img', '').trim(); // Extract topic name from button text
        const message = topicMessages[topicId];

        button.addEventListener('mouseenter', () => {
            animateSpeechBubble(speechBubble, true, message);
        });

        button.addEventListener('mouseleave', () => {
            animateSpeechBubble(speechBubble, false);
        });

        // Tıklama olayı ekle
        button.addEventListener('click', () => {
            handleTopicSelection(topicId, topicName);
        });
    });
}

// Konu butonları için olay dinleyicileri
function setupTopicButtons() {
    document.querySelectorAll('.topic-button').forEach(button => {
        const topicId = parseInt(button.dataset.topicId);
        const topic = appData.topics.find(t => t.id === topicId);
        if (!topic) return;

        // Hover olayları
        button.addEventListener('mouseenter', () => {
            const speechBubble = document.querySelector('.speech-bubble');
            if (speechBubble) {
                speechBubble.style.display = 'block';
                speechBubble.style.opacity = '1';
                speechBubble.style.transform = 'none';
                speechBubble.textContent = `Haydi ${topic.name} işlemini öğrenelim!`;
            }
        });

        button.addEventListener('mouseleave', () => {
            const speechBubble = document.querySelector('.speech-bubble');
            if (speechBubble) {
                gsap.to(speechBubble, {
                    opacity: 0,
                    y: -10,
                    duration: 0.3,
                    ease: "power2.out",
                    onComplete: () => {
                        speechBubble.style.display = 'none';
                        speechBubble.style.transform = 'none';
                    }
                });
            }
        });

        // Tıklama olayı
        button.addEventListener('click', () => {
            handleTopicSelection(topicId, topic.name);
        });
    });
}

// Başlat butonuna tıklama olayı
function handleStartButtonClick() {
    // Eğer animasyon devam ediyorsa, tıklamayı engelle
    if (isAnimating) return;
    
    // Animasyon başladığını işaretle
    isAnimating = true;
    
    const buttonWrapper = document.querySelector('.button-wrapper');
    if (!buttonWrapper) return;

    gsap.to(buttonWrapper, {
        opacity: 0,
        scale: 0,
        duration: 0.3,
        ease: "back.in(1.7)",
        onComplete: () => {
            buttonWrapper.remove(); // Butonu DOM'dan kaldır
            transitionToMathScene();
            isAnimating = false;
        }
    });
}

// Sayfa yüklendiğinde başlangıç animasyonları
window.addEventListener('load', () => {
    const isMobile = window.innerWidth <= 768;
    const duration = isMobile ? 0.7 : 1;

    // DOM elementlerini önbelleğe al
    const speechBubble = document.querySelector('.speech-bubble');
    const buboContainer = document.querySelector('.bubo-container');
    const trees = document.querySelectorAll('.tree');

    // Başlangıçta konuşma balonunu gizle
    speechBubble.style.opacity = 0;
    speechBubble.style.visibility = 'hidden';
    speechBubble.style.display = 'none';

    // Ağaçların başlangıç animasyonu
    trees.forEach(tree => {
        gsap.to(tree, {
            rotation: 1,
            duration: 3,
            repeat: -1,
            yoyo: true,
            ease: "power1.inOut",
            transformOrigin: "bottom center"
        });
    });

    // Başlangıç animasyonları
    const startTimeline = gsap.timeline({
        onComplete: () => {
            // Baykuş animasyonu tamamlandıktan sonra konuşma balonunu göster
            setTimeout(() => {
                animateSpeechBubble(speechBubble, true, "Merhaba küçük kaşif! Maceraya hazır mısın?");
                
                // 6 saniye sonra konuşma balonunu otomatik kaldır
                autoHideTimeout = setTimeout(() => {
                    if (!isHovering) {
                        animateSpeechBubble(speechBubble, false);
                    }
                }, 6000);
            }, 500);
        }
    });

    startTimeline
        .from('.cloud', {
            opacity: 0,
            x: "-=30",
            duration: duration,
            stagger: 0.2,
            ease: "power2.out"
        })
        .from('.tree', {
            y: 50,
            opacity: 0,
            duration: duration,
            stagger: 0.3,
            ease: "power3.out"
        }, "-=0.5")
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
                // Baykuş gelir gelmez konuşma balonunu göster
                animateSpeechBubble(speechBubble, true, "Merhaba küçük kaşif! Maceraya hazır mısın?");
                
                // 6 saniye sonra konuşma balonunu otomatik kaldır
                autoHideTimeout = setTimeout(() => {
                    if (!isHovering) {
                        animateSpeechBubble(speechBubble, false);
                    }
                }, 6000);
            }
        })
        .from('.button-wrapper', {
            scale: 0,
            opacity: 0,
            duration: duration * 1.2,
            ease: "elastic.out(1, 0.5)",
            delay: 0.5
        });
});

// Sayfa görünürlüğünü kontrol et
document.addEventListener('visibilitychange', () => {
    if (!gameStartTime) return;

    if (document.visibilityState === 'visible' && gameActive) {
        // Sayfa görünür olduğunda zamanlayıcıyı güncelle
        const now = Date.now();
        const elapsedSeconds = Math.floor((now - gameStartTime) / 1000);
        const remainingSeconds = Math.max(60 - elapsedSeconds, 0);

        if (remainingSeconds === 0) {
            gameActive = false;
            handleTimeUp();
        } else {
            const timerBar = document.querySelector('.timer-bar');
            if (timerBar) {
                const progress = remainingSeconds / 60;
                timerBar.style.transform = `scaleX(${progress})`;
            }
        }
    }
});

// Ekran boyutu değiştiğinde animasyonları güncelle
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

window.addEventListener('resize', debounce(() => {
    const isMobile = window.innerWidth <= 768;
    const buboContainer = document.querySelector('.bubo-container');
    
    if (buboContainer) {
        gsap.to(buboContainer, {
            rotation: isMobile ? 2 : 3,
            duration: 2,
            repeat: -1,
            yoyo: true,
            ease: "power1.inOut",
            transformOrigin: "top center"
        });
        }
}, 250));

// Global değişkenler
let bubbleTimeout = null;
let bubbleAnimation = null;

// Oyun durumunu tamamen sıfırla
function resetGameState() {
    // Tüm zamanlayıcıları temizle
    if (timerInterval) clearInterval(timerInterval);
    if (globalTimerInterval) clearInterval(globalTimerInterval);
    if (timerAnimation) timerAnimation.kill();
    if (bubbleTimeout) clearTimeout(bubbleTimeout);
    if (bubbleAnimation) bubbleAnimation.kill();

    // Sadece oyunla ilgili GSAP animasyonları durdur
    gsap.killTweensOf(".calculator-container");
    gsap.killTweensOf(".timer-bar");
    gsap.killTweensOf(".question-display");
    gsap.killTweensOf(".score-display");

    // Oyun durumunu sıfırla
    scores = { correct: 0, wrong: 0 };
    sessionData = {
        selectedTopic: null,
        selectedDifficulty: null,
        topicName: '',
        difficultyName: '',
        xpMultiplier: 1,
        scores: { correct: 0, wrong: 0 }
    };
    gameActive = false;
    gameStartTime = null;

    // Tüm event listener'ları temizle
    const oldButtons = document.querySelectorAll('.calculator-container button, .result-bubble button');
    oldButtons.forEach(button => {
        const newButton = button.cloneNode(true);
        if (button.parentNode) {
            button.parentNode.replaceChild(newButton, button);
        }
    });

    // Tüm oyun elementlerini temizle
    const calculatorContainer = document.querySelector('.calculator-container');
    if (calculatorContainer) calculatorContainer.remove();

    const resultBubble = document.querySelector('.result-bubble');
    if (resultBubble) resultBubble.remove();

    // Diğer olası elementleri temizle
    const questionDisplay = document.querySelector('.question-display');
    if (questionDisplay) questionDisplay.remove();

    const timerContainer = document.querySelector('.timer-container');
    if (timerContainer) timerContainer.remove();

    const scoreDisplay = document.querySelector('.score-display');
    if (scoreDisplay) scoreDisplay.remove();
}

// Konuşma balonunu göster
function showSpeechBubble(message, duration = 5000) {
    const speechBubble = document.querySelector('.speech-bubble');
    if (!speechBubble) return;

    // Önceki timeout ve animasyonları temizle
    if (bubbleTimeout) clearTimeout(bubbleTimeout);
    if (bubbleAnimation) bubbleAnimation.kill();
    gsap.killTweensOf(speechBubble);

    // Baloncuğu sıfırla
    speechBubble.style.display = 'block';
    speechBubble.style.opacity = '0';
    speechBubble.style.transform = 'translateY(20px)';
    speechBubble.textContent = message;

    // Yeni animasyon oluştur
    bubbleAnimation = gsap.timeline()
        .to(speechBubble, {
            opacity: 1,
            y: 0,
            duration: 0.3, // Hover için daha hızlı
            ease: "back.out(1.7)"
        });

    // Eğer süre belirtilmişse, o süre sonra gizle
    if (duration > 0) {
        bubbleTimeout = setTimeout(() => {
            bubbleAnimation = gsap.to(speechBubble, {
                opacity: 0,
                y: -10,
                duration: 0.3,
                ease: "power2.out",
                onComplete: () => {
                    speechBubble.style.display = 'none';
                }
            });
        }, duration);
    }
}

// Oyunu yeniden başlat
function restartGame() {
    // Önce tüm durumu sıfırla
    resetGameState();

    // Kuş ve konuşma balonunu sıfırla
    const buboContainer = document.querySelector('.bubo-container');
    const speechBubble = document.querySelector('.speech-bubble');

    if (buboContainer && speechBubble) {
        // Mevcut animasyonları temizle
        gsap.killTweensOf(buboContainer);
        gsap.killTweensOf(speechBubble);

        // Kuşu göster ve sallanma animasyonunu başlat
        buboContainer.style.display = 'block';
        buboContainer.style.opacity = '1';
        buboContainer.style.transform = 'none';

        // Yeni sallanma animasyonu
        gsap.to(buboContainer, {
            rotation: 3,
            duration: 2,
            ease: "sine.inOut",
            yoyo: true,
            repeat: -1
        });

        // Konuşma balonunu göster
        showSpeechBubble("Merhaba küçük kaşif! Tekrardan hazır mısın?");
    }

    // appData'yı yeniden yükle ve topic elementlerini oluştur
    const appDataScript = document.getElementById('app-data');
    if (appDataScript) {
        try {
            window.appData = JSON.parse(appDataScript.textContent);
            
            // Yeni topic elementlerini oluştur
            setTimeout(() => {
                createTopicElements();
            }, 300);
        } catch (error) {
            console.error('AppData yüklenirken hata:', error);
        }
    }
}

// Topic elementlerini oluştur
function createTopicElements() {
    // appData kontrolü
    if (!window.appData || !window.appData.topics) {
        console.error('AppData bulunamadı');
        return;
    }

    // İsim dönüşüm fonksiyonu
    function convertToImageName(name) {
        const conversions = {
            'Toplama': 'plus',
            'Çıkarma': 'minus',
            'Çarpma': 'multiply',
            'Bölme': 'divide'
        };
        return conversions[name] || name.toLowerCase();
    }

    // Ana yapıyı oluştur
    const container = document.createElement('div');
    container.className = 'calculator-container';
    container.innerHTML = `
        <div class="calculator">
            <div class="calculator-screen">
                <div class="screen-decoration">
                    <div class="solar-panel"></div>
                    <div class="brand">Keşif Hesap</div>
                </div>
                <div class="screen-display">
                    Konuyu Seç
                </div>
            </div>
                <div class="calculator-body">
                    <div class="topic-grid"></div>
                </div>
        </div>
    `;

    // Topic grid'i bul
    const topicGrid = container.querySelector('.topic-grid');

    // Topic butonlarını oluştur
    window.appData.topics.forEach(topic => {
        const button = document.createElement('button');
        button.className = 'topic-button';
        button.dataset.topicId = topic.id;

        const img = document.createElement('img');
        const imageName = convertToImageName(topic.name);
        img.src = `/assets/img/icons/${imageName}.png`;
        img.alt = topic.name;
        button.appendChild(img);

        const span = document.createElement('span');
        span.textContent = topic.name;
        button.appendChild(span);

        // Hover olayları
        let isHovering = false;
        button.addEventListener('mouseenter', () => {
            isHovering = true;
            showSpeechBubble(`Haydi ${topic.name} işlemini öğrenelim!`, 2500);
        });

        button.addEventListener('mouseleave', () => {
            isHovering = false;
            const speechBubble = document.querySelector('.speech-bubble');
            if (speechBubble) {
                // Mevcut animasyonları temizle
                if (bubbleTimeout) clearTimeout(bubbleTimeout);
                if (bubbleAnimation) bubbleAnimation.kill();
                gsap.killTweensOf(speechBubble);

                // Çıkış animasyonu
                bubbleAnimation = gsap.to(speechBubble, {
                    opacity: 0,
                    y: -10,
                    duration: 0.3,
                    ease: "power2.out",
                    onComplete: () => {
                        if (!isHovering) {
                            speechBubble.style.display = 'none';
                        }
                    }
                });
            }
        });

        // Tıklama olayı
        button.addEventListener('click', () => {
            handleTopicSelection(topic.id, topic.name);
        });

        topicGrid.appendChild(button);
    });

    // Container'ı ekle
    document.body.appendChild(container);

    // Görünürlük ayarla ve animasyon ile göster
    container.style.opacity = '0';
    container.style.display = 'flex';

    gsap.to(container, {
        opacity: 1,
        duration: 0.5,
        ease: "power2.out"
    });
}
