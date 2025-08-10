// QuestionGenerator sınıfını global scope'a ekle
window.QuestionGenerator = class QuestionGenerator {
    constructor() {
        this.operators = {
            1: '+',  // Toplama
            2: '-',  // Çıkarma
            3: '×',  // Çarpma
            4: '÷'   // Bölme
        };
    }

    /**
     * Belirli bir aralıkta rastgele bir tam sayı üretir.
     */
    _getRandomNumber(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    /**
     * Ana soru üretme fonksiyonu.
     */
    generateQuestion(topicId, difficulty) {
        const operator = this.operators[topicId];
        let num1, num2, answer;

        switch (String(topicId)) {

            case '1': // TOPLAMA
                switch (difficulty) {
                    case 'Kolay': num1 = this._getRandomNumber(1, 20); num2 = this._getRandomNumber(1, 20); break;
                    case 'Orta': num1 = this._getRandomNumber(20, 100); num2 = this._getRandomNumber(20, 100); break;
                    case 'Zor': num1 = this._getRandomNumber(100, 500); num2 = this._getRandomNumber(100, 500); break;
                    case 'Deha': num1 = this._getRandomNumber(500, 2000); num2 = this._getRandomNumber(500, 2000); break;
                }
                answer = num1 + num2;
                break;

            case '2': // ÇIKARMA
                switch (difficulty) {
                    case 'Kolay': num2 = this._getRandomNumber(1, 20); num1 = this._getRandomNumber(num2 + 1, 40); break;
                    case 'Orta': num2 = this._getRandomNumber(20, 100); num1 = this._getRandomNumber(num2 + 1, 200); break;
                    case 'Zor': num2 = this._getRandomNumber(100, 500); num1 = this._getRandomNumber(num2 + 1, 1000); break;
                    case 'Deha': num2 = this._getRandomNumber(500, 1000); num1 = this._getRandomNumber(num2 + 1, 2000); break;
                }
                answer = num1 - num2;
                break;

            case '3': // ÇARPMA
                switch (difficulty) {
                    case 'Kolay': num1 = this._getRandomNumber(2, 9); num2 = this._getRandomNumber(2, 9); break;
                    case 'Orta': num1 = this._getRandomNumber(10, 30); num2 = this._getRandomNumber(2, 9); break;
                    case 'Zor': num1 = this._getRandomNumber(11, 99); num2 = this._getRandomNumber(11, 99); break;
                    case 'Deha': num1 = this._getRandomNumber(100, 999); num2 = this._getRandomNumber(10, 99); break;
                }
                answer = num1 * num2;
                break;

            case '4': // BÖLME (HER SEVİYEDE GARANTİLİ TAM SAYI SORU VE CEVAP)
                switch (difficulty) {
                    case 'Kolay':
                        num2 = this._getRandomNumber(2, 9);
                        answer = this._getRandomNumber(2, 9);
                        num1 = num2 * answer;
                        break;
                    case 'Orta':
                        num2 = this._getRandomNumber(3, 12);
                        answer = this._getRandomNumber(10, 30);
                        num1 = num2 * answer;
                        break;
                    case 'Zor':
                        num2 = this._getRandomNumber(5, 25);
                        answer = this._getRandomNumber(10, 50);
                        num1 = num2 * answer;
                        break;
                    case 'Deha':
                        num2 = this._getRandomNumber(10, 50);
                        answer = this._getRandomNumber(25, 100);
                        num1 = num2 * answer;
                        break;
                }
                // 'answer' zaten yukarıda belirlendiği için tekrar hesaplamaya gerek yok.
                break;
        }

        return { num1, num2, operator, answer };
    }
};
