// QuestionGenerator sınıfını global scope'a ekle
window.QuestionGenerator = class QuestionGenerator {
    constructor() {

        this.difficultyRanges = {
            'Kolay': {
                num1: { min: 10, max: 99 },    // İki basamaklı
                num2: { min: 1, max: 99 }      // Bir veya iki basamaklı
            },
            'Orta': {
                num1: { min: 100, max: 999 },  // Üç basamaklı
                num2: { min: 10, max: 999 }    // İki veya üç basamaklı
            },
            'Zor': {
                num1: { min: 1000, max: 9999 },  // Dört basamaklı
                num2: { min: 100, max: 999 }    // Üç basamaklı
            },
            'Deha': {  // 'Dahi' yerine 'Deha' olarak değiştirildi
                num1: { min: 1000, max: 9999 },  // Dört basamaklı
                num2: { min: 100, max: 999 }    // Üç basamaklı
            }
        };

        this.operators = {
            1: '+',  // Toplama
            2: '-',  // Çıkarma
            3: '×',  // Çarpma
            4: '÷'   // Bölme
        };
    }

    // Belirli bir aralıkta rastgele sayı üret
    generateRandomNumber(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    // Sayının virgülden sonra kaç basamak olduğunu kontrol et
    checkDecimalPlaces(number) {
        const decimalStr = number.toString().split('.')[1];
        return decimalStr ? decimalStr.length : 0;
    }

    // Bölme işlemi için uygun sayılar üret
    generateDivisionNumbers(difficulty) {

        const range = this.difficultyRanges[difficulty];
        if (!range) {
            console.error('Geçersiz zorluk seviyesi.');
            // Varsayılan olarak Zor seviyeyi kullan
            return this.generateDivisionNumbers('Zor');
        }

        let num1, num2, result;

        if (difficulty === 'Kolay' || difficulty === 'Orta') {
            // Kolay ve Orta seviye için basit sayılar
            if (difficulty === 'Kolay') {
                // Kolay seviye için bölenler: 2-10 arası
                num2 = this.generateRandomNumber(2, 10);
                // Bölünen için 2-5 arası çarpan kullan
                const multiplier = this.generateRandomNumber(2, 5);
                num1 = num2 * multiplier;
            } else {
                // Orta seviye için bölenler: 2-20 arası
                num2 = this.generateRandomNumber(2, 20);
                // Bölünen için 2-10 arası çarpan kullan
                const multiplier = this.generateRandomNumber(2, 10);
                num1 = num2 * multiplier;
            }

            // Sayıları aralık içinde tut
            if (num1 > range.num1.max) {
                // Aralık dışındaysa, böleni küçült
                num2 = Math.floor(range.num1.max / 5);
                num1 = num2 * this.generateRandomNumber(2, 5);
            }

            result = num1 / num2;
        } else {
            // Zor ve Deha seviyesi için daha kontrollü ondalıklı sayılar
            let attempts = 0;
            const maxAttempts = 10;

            do {
                if (difficulty === 'Zor') {
                    // Zor seviye için 2-3 basamaklı bölenler
                    num2 = this.generateRandomNumber(10, 99);
                    num1 = this.generateRandomNumber(100, 999);
                } else {
                    // Deha seviyesi için 3-4 basamaklı bölenler
                    num2 = this.generateRandomNumber(100, 999);
                    num1 = this.generateRandomNumber(1000, 9999);
                }

                result = num1 / num2;
                attempts++;

                // Virgülden sonra en fazla 2 basamak olsun
                if (this.checkDecimalPlaces(result) <= 2) break;
            } while (attempts < maxAttempts);

            // Son kontrol ve yuvarlama
            result = Number(result.toFixed(2));
        }

        return [num1, num2];
    }

    // Soru üret
    generateQuestion(topicId, difficulty) {

        const operator = this.operators[topicId];
        const range = this.difficultyRanges[difficulty];

        // Geçersiz zorluk seviyesi kontrolü
        if (!range) {
            console.error('Geçersiz zorluk seviyesi:');
            return this.generateQuestion(topicId, 'Zor'); // Varsayılan olarak Zor seviye
        }

        let num1, num2, answer;

        // Bölme işlemi için özel durum
        if (parseInt(topicId) === 4) {
            [num1, num2] = this.generateDivisionNumbers(difficulty);
            answer = num1 / num2;

            // Zor ve Deha seviyesi için virgülden sonra 2 basamakla sınırla
            if (difficulty === 'Zor' || difficulty === 'Deha') {  // 'Dahi' yerine 'Deha' olarak değiştirildi
                answer = Number(answer.toFixed(2));
            }

        } else {
            // Diğer işlemler için sayı üretimi
            if (difficulty === 'Kolay') {
                num1 = this.generateRandomNumber(10, 99);    // İki basamaklı
                num2 = this.generateRandomNumber(1, 99);     // Bir veya iki basamaklı
            } else if (difficulty === 'Orta') {
                num1 = this.generateRandomNumber(100, 999);  // Üç basamaklı
                num2 = this.generateRandomNumber(10, 999);   // İki veya üç basamaklı
            } else { // Zor ve Deha için aynı aralık
                num1 = this.generateRandomNumber(1000, 9999); // Dört basamaklı
                num2 = this.generateRandomNumber(100, 999);   // Üç basamaklı
            }

            // İşleme göre cevabı hesapla
            switch(operator) {
                case '+':
                    answer = num1 + num2;
                    break;
                case '-':
                    // Çıkarma işleminde ilk sayının daha büyük olmasını sağla
                    if (num2 > num1) {
                        [num1, num2] = [num2, num1];
                    }
                    answer = num1 - num2;
                    break;
                case '×':
                    answer = num1 * num2;
                    break;
            }
        }

        const result = {
            num1,
            num2,
            operator,
            answer,
            difficulty
        };

        return result;
    }

    // Sayıyı formatlı göster (binlik ayraç ekle)
    formatNumber(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
}
