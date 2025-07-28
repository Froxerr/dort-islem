document.addEventListener('DOMContentLoaded', function() {
    const centerCloud = document.getElementById('centerCloud');
    const calculator = document.getElementById('calculator');
    const decorativeClouds = document.querySelectorAll('.profile-cloud, .profile-cloud-secondary, .decoration-cloud-1, .decoration-cloud-2, .decoration-cloud-3, .decoration-cloud-4');
    const speechBubble = document.querySelector('.speech-bubble');
    const sectionTitle = document.getElementById('sectionTitle');
    const topicsGrid = document.getElementById('topicsGrid');
    const difficultyGrid = document.getElementById('difficultyGrid');
    const calculationArea = document.getElementById('calculationArea');
    const feedback = document.getElementById('feedback');
    const countdownDisplay = document.getElementById('countdown');
    const correctCountDisplay = document.getElementById('correctCount');
    const wrongCountDisplay = document.getElementById('wrongCount');
    const timerProgress = document.getElementById('timerProgress');
    const calculatorClose = document.getElementById('calculatorClose');
    const exitDialog = document.getElementById('exitDialog');
    const confirmExit = document.getElementById('confirmExit');
    const cancelExit = document.getElementById('cancelExit');

    const questionGenerator = new QuestionGenerator();

    // Kullanıcı tercihlerini al (eğer tanımlıysa)
    const settings = window.userSettings || {
        default_difficulty_id: null,
        favorite_topic_id: null,
        auto_next_question: false,
        show_correct_answers: true,
        sound_effects: true
    };

    let selectedTopicId = null;
    let selectedLevelId = null;
    let selectedTopicName = '';
    let selectedDifficultyName = '';
    let selectedDifficultyMultiplier = 1;
    let correctCount = 0;
    let wrongCount = 0;
    let countdownInterval = null;
    let timeLeft = 60;
    let isGameActive = false;
    let autoNextTimeout = null;

    // İlk konuşma baloncuğu için zamanlayıcı
    let initialTimeout = setTimeout(() => {
        speechBubble.classList.add('hide');
    }, 5000);

    // Merkez buluta tıklama
    centerCloud.addEventListener('click', function() {
        this.classList.add('hide');
        decorativeClouds.forEach(cloud => {
            cloud.classList.add('hide');
        });

        // Calculator'ı göster ve animasyonunu başlat
        calculator.style.display = 'flex';
        setTimeout(() => {
            calculator.style.transform = 'translate(-50%, -50%) scale(1)';
            calculator.style.opacity = '1';

            // Eğer favori konu ayarlanmışsa direkt o konuya geç
            if (settings.favorite_topic_id) {
                const favoriteTopicElement = document.querySelector(`[data-topic-id="${settings.favorite_topic_id}"]`);
                if (favoriteTopicElement) {
                    setTimeout(() => {
                        favoriteTopicElement.click();
                    }, 500);
                }
            }
        }, 50);
    });

    // Konu seçimi için
    const topicItems = document.querySelectorAll('.topic-item');
    const difficultyItems = document.querySelectorAll('.difficulty-item');
    let currentTimeout;

    topicItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            const topicName = this.dataset.topicName;
            clearTimeout(currentTimeout);
            clearTimeout(initialTimeout);
            speechBubble.textContent = `${topicName} konusunu seçmek ister misin?`;
            speechBubble.classList.remove('hide');
        });

        item.addEventListener('mouseleave', function() {
            currentTimeout = setTimeout(() => {
                speechBubble.classList.add('hide');
            }, 300);
        });

        item.addEventListener('click', function() {
            // Önceki seçili konuyu temizle
            document.querySelectorAll('.topic-item').forEach(t => t.classList.remove('selected'));
            // Yeni konuyu seç
            this.classList.add('selected');

            selectedTopicId = this.dataset.topicId;
            selectedTopicName = this.dataset.topicName;

            // Başlığı değiştir
            sectionTitle.classList.add('fade-out');
            topicsGrid.classList.add('fade-out');

            setTimeout(() => {
                sectionTitle.textContent = 'Zorluk Seviyesi Seçiniz';
                sectionTitle.classList.remove('fade-out');
                topicsGrid.style.display = 'none';
                topicsGrid.classList.add('hidden');
                difficultyGrid.style.display = 'grid';
                difficultyGrid.classList.remove('fade-out');

                // Eğer varsayılan zorluk ayarlanmışsa direkt o zorluğa geç
                if (settings.default_difficulty_id) {
                    const defaultDifficultyElement = document.querySelector(`[data-level-id="${settings.default_difficulty_id}"]`);
                    if (defaultDifficultyElement) {
                        setTimeout(() => {
                            defaultDifficultyElement.click();
                        }, 500);
                    }
                }
            }, 500);

            speechBubble.textContent = `${selectedTopicName} konusu için zorluk seviyesi seç!`;
            speechBubble.classList.remove('hide');
        });
    });

    // Sayaç fonksiyonu
    function startCountdown() {
        timeLeft = 60;
        isGameActive = true;
        countdownDisplay.style.color = '#2563eb';
        timerProgress.style.backgroundColor = '#3b82f6';
        updateTimerBar();

        countdownInterval = setInterval(() => {
            timeLeft--;
            countdownDisplay.textContent = timeLeft;
            updateTimerBar();

            if (timeLeft <= 10) {
                countdownDisplay.style.color = '#dc2626';
                timerProgress.style.backgroundColor = '#dc2626';
            }

            if (timeLeft <= 0) {
                endGame();
            }
        }, 1000);
    }

    function updateTimerBar() {
        const percentage = (timeLeft / 60) * 100;
        timerProgress.style.width = `${percentage}%`;
    }

    function endGame() {
        clearInterval(countdownInterval);
        isGameActive = false;
        document.getElementById('answer').disabled = true;
        document.getElementById('checkAnswer').disabled = true;
        showResultScreen();
    }

    function resetGame() {
        correctCount = 0;
        wrongCount = 0;
        correctCountDisplay.textContent = '0';
        wrongCountDisplay.textContent = '0';
        feedback.innerHTML = '';
        countdownDisplay.style.color = '#2563eb';
        timerProgress.style.backgroundColor = '#3b82f6';
        timerProgress.style.width = '100%';
        document.getElementById('answer').disabled = false;
        document.getElementById('checkAnswer').disabled = false;
    }

    function startGame() {
        resetGame();
        startCountdown();
        generateQuestion(selectedTopicId, selectedLevelId);
    }

    // Zorluk seviyesi seçimi için
    difficultyItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            const levelName = this.dataset.levelName;
            clearTimeout(currentTimeout);
            speechBubble.textContent = `${levelName} seviyesini seçmek ister misin?`;
            speechBubble.classList.remove('hide');
        });

        item.addEventListener('mouseleave', function() {
            currentTimeout = setTimeout(() => {
                speechBubble.classList.add('hide');
            }, 300);
        });

        item.addEventListener('click', function() {
            selectedLevelId = this.dataset.levelId;
            selectedDifficultyName = this.dataset.levelName;
            // xp_multiplier'ı float olarak al ve kontrol et
            selectedDifficultyMultiplier = parseFloat(this.dataset.xpMultiplier) || 1;

            difficultyGrid.classList.add('fade-out');

            setTimeout(() => {
                difficultyGrid.style.display = 'none';
                document.querySelector('.calculator-top').style.display = 'none';
                calculationArea.style.display = 'block';
                startGame();
            }, 500);

            speechBubble.textContent = `Harika! ${selectedDifficultyName} seviyesinde başlıyoruz!`;
            speechBubble.classList.remove('hide');
        });
    });

    function calculateFinalScore() {
        const totalQuestions = correctCount + wrongCount;
        const accuracyRate = (correctCount / totalQuestions) * 100;

        // Temel puan hesaplama
        let baseScore = correctCount * 10;

        // Bonus çarpanı belirleme
        let bonusMultiplier = 1;
        if (accuracyRate > 95) {
            bonusMultiplier = 1.5;
        } else if (accuracyRate > 80) {
            bonusMultiplier = 1.2;
        } else if (accuracyRate > 65) {
            bonusMultiplier = 1.1;
        } else if (accuracyRate <= 50) {
            bonusMultiplier = 1;
        }

        // Nihai puanı hesapla
        const finalScore = Math.round(baseScore * bonusMultiplier * selectedDifficultyMultiplier);

        return {
            accuracyRate,
            bonusMultiplier,
            xpMultiplier: selectedDifficultyMultiplier,
            finalScore,
            baseScore
        };
    }

    function getMotivationalMessage(accuracyRate) {
        if (accuracyRate > 95) return "Neredeyse Kusursuz! Muhteşem bir performans!";
        if (accuracyRate > 80) return "Harika Odaklanma! Çok iyi iş çıkardın!";
        if (accuracyRate > 65) return "İyi iş! Gelişmeye devam et!";
        if (accuracyRate > 50) return "Güzel çaba! Biraz daha pratik yapmalısın.";
        return "Denemeye devam et! Her pratik seni geliştirir.";
    }

    function showResultScreen() {
        const scoreData = calculateFinalScore();
        const motivationalMessage = getMotivationalMessage(scoreData.accuracyRate);

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
                            <p><strong>Konu:</strong> ${selectedTopicName}</p>
                            <p><strong>Zorluk:</strong> ${selectedDifficultyName}</p>
                        </div>
                        <div class="result-col">
                            <p><strong>Doğru:</strong> <span class="success">${correctCount}</span></p>
                            <p><strong>Yanlış:</strong> <span class="error">${wrongCount}</span></p>
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
                    <button class="restart-btn" onclick="restartQuiz()">Tekrar Başla</button>
                </div>
            </div>
        `;

        // Quiz session'ı kaydet
        saveQuizSession(scoreData);

        // Mevcut hesap makinesini kaldır ve sonuç ekranını göster
        calculationArea.style.display = 'none';
        document.querySelector('.calculator-bottom').insertAdjacentHTML('beforeend', resultHTML);

        // Sonuç ekranını göster
        const resultBubble = document.querySelector('.result-bubble');
        resultBubble.style.display = 'block';
    }

    // Yeni restart fonksiyonu
    window.restartQuiz = function() {
        // Sonuç ekranını kaldır
        const resultBubble = document.querySelector('.result-bubble');
        if (resultBubble) {
            resultBubble.remove();
        }

        // Timer'ı temizle
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        // Eğer konu ve zorluk zaten seçilmişse direkt oyuna başla
        if (selectedTopicId && selectedLevelId) {
            // Sadece oyun verilerini sıfırla
            correctCount = 0;
            wrongCount = 0;
            correctCountDisplay.textContent = '0';
            wrongCountDisplay.textContent = '0';
            timeLeft = 60;
            isGameActive = false;

            // Input ve butonları sıfırla
            document.getElementById('answer').value = '';
            document.getElementById('answer').disabled = false;
            document.getElementById('checkAnswer').disabled = false;

            // Hesaplama alanını göster ve oyunu başlat
            calculationArea.style.display = 'block';
            document.querySelector('.calculator-top').style.display = 'none';

            // Baykuş mesajını güncelle
            speechBubble.textContent = `${selectedTopicName} - ${selectedDifficultyName} ile tekrar başlıyoruz!`;
            speechBubble.classList.remove('hide');
            setTimeout(() => {
                speechBubble.classList.add('hide');
            }, 2000);

            // Oyunu başlat
            startGame();
            return;
        }

        // Eğer konu/zorluk seçilmemişse normal restart yap
        selectedTopicId = null;
        selectedLevelId = null;
        correctCount = 0;
        wrongCount = 0;
        timeLeft = 60;
        isGameActive = false;

        // Input ve butonları sıfırla
        document.getElementById('answer').value = '';
        document.getElementById('answer').disabled = false;
        document.getElementById('checkAnswer').disabled = false;

        // Görünümü sıfırla
        calculationArea.style.display = 'none';
        difficultyGrid.style.display = 'none';
        topicsGrid.style.display = 'grid';
        topicsGrid.classList.remove('fade-out', 'hidden');
        document.querySelector('.calculator-top').style.display = 'block';

        // Seçili konuyu temizle
        document.querySelectorAll('.topic-item').forEach(t => t.classList.remove('selected'));

        // Section title'ı sıfırla
        sectionTitle.textContent = 'Konu Seçiniz';
        sectionTitle.classList.remove('fade-out');

        // Baykuş mesajını güncelle
        speechBubble.textContent = 'Yeni bir konu seçmeye hazır mısın?';
        speechBubble.classList.remove('hide');
        setTimeout(() => {
            speechBubble.classList.add('hide');
        }, 3000);
    };

    async function saveQuizSession(scoreData) {
        try {

            const response = await fetch('/quiz-sessions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                credentials: 'include',
                body: JSON.stringify({
                    topic_id: selectedTopicId,
                    difficulty_level_id: selectedLevelId,
                    score: scoreData.baseScore,
                    xp_earned: scoreData.finalScore,
                    total_questions: correctCount + wrongCount,
                    correct_answers: correctCount
                })
            });

            const responseData = await response.json();

            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                throw new Error('Quiz session kaydedilemedi');
            }



            if (responseData.notifications && responseData.notifications.length > 0) {

                // Bildirimleri sıraya koy
                const notifications = [...responseData.notifications];

                // İlk bildirimi hemen göster
                showNextNotification();

                function showNextNotification() {
                    if (notifications.length === 0) return;

                    const notification = notifications.shift();

                    if (notification.type === 'App\\Notifications\\LevelUpEarned') {
                        showLevelUpNotification(notification);
                    }
                    else if (notification.type === 'App\\Notifications\\BadgeEarned') {
                        showBadgeNotification(notification);
                    }

                    // Bir sonraki bildirimi göstermek için event dinleyicisi ekle
                    const modal = document.querySelector('.achievement-modal');
                    if (modal) {
                        const continueBtn = modal.querySelector('.continue-btn');
                        if (continueBtn) {
                            const originalClick = continueBtn.onclick;
                            continueBtn.onclick = function(e) {
                                if (originalClick) originalClick.call(this, e);
                                setTimeout(showNextNotification, 500);
                            };
                        }
                    }
                }
            } else {
            }
        } catch (error) {
            console.error('Quiz session kaydetme hatası:');
            throw error;
        }
    }

    // Soru oluşturma fonksiyonu
    function generateQuestion(topicId, levelId) {
        let difficulty;
        switch(levelId) {
            case '1': difficulty = 'Kolay'; break;
            case '2': difficulty = 'Orta'; break;
            case '3': difficulty = 'Zor'; break;
            case '4': difficulty = 'Deha'; break;
            default: difficulty = 'Kolay';
        }

        const question = questionGenerator.generateQuestion(topicId, difficulty);

        document.getElementById('number1').textContent = question.num1;
        document.getElementById('operator').textContent = question.operator;
        document.getElementById('number2').textContent = question.num2;
        document.getElementById('answer').value = '';
    }

    // Cevap kontrolü için event listener
    document.getElementById('checkAnswer').addEventListener('click', checkAnswer);
    document.getElementById('answer').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && isGameActive) {
            checkAnswer();
        }
    });

    function showCorrectEffect() {
        // Body ve container için flash efekti
        document.body.classList.add('correct-flash');
        document.querySelector('.main-container').classList.add('correct-flash');
        document.querySelector('.calculation-area').classList.add('correct-flash');

        // Doğru sayacı için parlama efekti
        const correctCounter = document.querySelector('.correct-answers');
        correctCounter.classList.add('glow');

        // Efektleri temizle
        setTimeout(() => {
            document.body.classList.remove('correct-flash');
            document.querySelector('.main-container').classList.remove('correct-flash');
            document.querySelector('.calculation-area').classList.remove('correct-flash');
            correctCounter.classList.remove('glow');
        }, 500);
    }

    function showWrongEffect() {
        // Body ve container için flash efekti
        document.body.classList.add('wrong-flash');
        document.querySelector('.main-container').classList.add('wrong-flash');
        document.querySelector('.calculation-area').classList.add('wrong-flash');

        // Yanlış sayacı için parlama efekti
        const wrongCounter = document.querySelector('.wrong-answers');
        wrongCounter.classList.add('glow');

        // Efektleri temizle
        setTimeout(() => {
            document.body.classList.remove('wrong-flash');
            document.querySelector('.main-container').classList.remove('wrong-flash');
            document.querySelector('.calculation-area').classList.remove('wrong-flash');
            wrongCounter.classList.remove('glow');
        }, 500);
    }

    function checkAnswer() {
        if (!isGameActive) return;

        const num1 = parseInt(document.getElementById('number1').textContent);
        const num2 = parseInt(document.getElementById('number2').textContent);
        const operator = document.getElementById('operator').textContent;
        const userAnswer = parseInt(document.getElementById('answer').value);
        const answerInput = document.getElementById('answer');

        if (isNaN(userAnswer)) {
            return;
        }

        let correctAnswer;
        switch(operator) {
            case '+':
                correctAnswer = num1 + num2;
                break;
            case '-':
                correctAnswer = num1 - num2;
                break;
            case '×':
                correctAnswer = num1 * num2;
                break;
            case '÷':
                correctAnswer = num1 / num2;
                break;
        }

        if (userAnswer === correctAnswer) {
            correctCount++;
            correctCountDisplay.textContent = correctCount;
            speechBubble.textContent = 'Harika! Devam et!';
            showCorrectEffect();

            // Ses efekti (eğer ayarlarda açıksa)
            if (settings.sound_effects) {
                // Burada ses çalabilirsiniz
            }

            // Otomatik sonraki soru (eğer ayarlarda açıksa)
            if (settings.auto_next_question) {
                setTimeout(() => {
                    answerInput.value = '';
                    generateQuestion(selectedTopicId, selectedLevelId);
                    answerInput.focus();
                }, 1000);
            } else {
                answerInput.value = '';
                generateQuestion(selectedTopicId, selectedLevelId);
                answerInput.focus();
            }
        } else {
            wrongCount++;
            wrongCountDisplay.textContent = wrongCount;

            // Doğru cevabı göster (eğer ayarlarda açıksa)
            if (settings.show_correct_answers) {
                speechBubble.textContent = `Yanlış! Doğru cevap: ${correctAnswer}`;

                                 // Otomatik sonraki soru (eğer ayarlarda açıksa)
                 if (settings.auto_next_question) {
                     setTimeout(() => {
                         answerInput.value = '';
                         generateQuestion(selectedTopicId, selectedLevelId);
                         answerInput.focus();
                     }, 2500); // Doğru cevabı görmek için biraz daha bekle
                 } else {
                     speechBubble.textContent += ' Tekrar dene!';
                     answerInput.value = '';
                     answerInput.focus();
                 }
            } else {
                                 speechBubble.textContent = 'Tekrar dene!';
                 if (settings.auto_next_question) {
                     setTimeout(() => {
                         answerInput.value = '';
                         generateQuestion(selectedTopicId, selectedLevelId);
                         answerInput.focus();
                     }, 1500);
                 } else {
                     answerInput.value = '';
                     answerInput.focus();
                 }
            }

            showWrongEffect();
        }

        speechBubble.classList.remove('hide');
        setTimeout(() => {
            speechBubble.classList.add('hide');
        }, 2000);
    }

    // Enter tuşu ile cevap gönderme
    document.getElementById('answer').addEventListener('keyup', function(e) {
        if (e.key === 'Enter' && isGameActive) {
            checkAnswer();
        }
    });

    // Close butonu için event listener
    calculatorClose.addEventListener('click', function() {
        if (isGameActive) {
            exitDialog.style.display = 'flex';
        } else {
            closeCalculator();
        }
    });

    // Dialog butonları için event listeners
    confirmExit.addEventListener('click', function() {
        exitDialog.style.display = 'none';
        closeCalculator();
    });

    cancelExit.addEventListener('click', function() {
        exitDialog.style.display = 'none';
    });

    function closeCalculator() {
        // Timer'ı temizle
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        // Calculator'ı gizle ve transform'u sıfırla
        calculator.style.transform = 'translate(-50%, -50%) scale(0)';
        calculator.style.opacity = '0';

        // İlk setTimeout: Calculator'ın kaybolmasını bekle
        setTimeout(() => {
            calculator.style.display = 'none';

            // İkinci setTimeout: Merkez bulutu göster
            setTimeout(() => {
                // Merkez bulutu ve dekoratif bulutları göster
                centerCloud.classList.remove('hide');
                decorativeClouds.forEach(cloud => {
                    cloud.classList.remove('hide');
                });
            }, 50);

            // İçerikleri sıfırla
            calculationArea.style.display = 'none';
            difficultyGrid.style.display = 'none';
            topicsGrid.style.display = 'grid';
            topicsGrid.classList.remove('fade-out', 'hidden');
            document.querySelector('.calculator-top').style.display = 'block';

            // Sonuç ekranını temizle
            const resultBubble = document.querySelector('.result-bubble');
            if (resultBubble) {
                resultBubble.remove();
            }

            // Değişkenleri sıfırla
            selectedTopicId = null;
            selectedLevelId = null;
            correctCount = 0;
            wrongCount = 0;
            timeLeft = 60;
            isGameActive = false;

            // Input ve butonları sıfırla
            document.getElementById('answer').value = '';
            document.getElementById('answer').disabled = false;
            document.getElementById('checkAnswer').disabled = false;

            // Seçili konuyu temizle
            document.querySelectorAll('.topic-item').forEach(t => t.classList.remove('selected'));

            // Section title'ı sıfırla
            sectionTitle.textContent = 'Konu Seçiniz';
            sectionTitle.classList.remove('fade-out');
        }, 300);
    }
});
