# Keşif Matematik Oyunu 🎮

Keşif Matematik Oyunu, ilkokul öğrencilerinin matematik becerilerini eğlenceli bir şekilde geliştirmelerini sağlayan interaktif bir web uygulamasıdır.

## 🎯 Özellikler

- **Dört İşlem Desteği**
  - ➕ Toplama
  - ➖ Çıkarma
  - ✖️ Çarpma
  - ➗ Bölme

- **Dört Zorluk Seviyesi**
  - 🟢 Kolay
  - 🟡 Orta
  - 🟠 Zor
  - 🔴 Deha

- **Özel Bölme İşlemi Kuralları**
  - Kolay ve Orta seviyede tam sayı sonuçlar
  - Zor ve Deha seviyesinde en fazla 2 basamaklı ondalıklı sonuçlar

- **Kullanıcı Dostu Arayüz**
  - Animasyonlu geçişler
  - Sezgisel tasarım
  - Mobil uyumlu görünüm

- **Puan Sistemi**
  - Doğru/yanlış sayacı
  - Zorluk seviyesine göre XP çarpanı
  - Süre bazlı oyun sistemi

## 🛠️ Teknik Özellikler

### Frontend
- HTML5
- CSS3
- JavaScript (Vanilla)
- GSAP (Animasyonlar için)

### Backend
- Laravel Framework
- MySQL Veritabanı

### Veritabanı Yapısı
- Users (Kullanıcılar)
- Roles (Roller)
- Badges (Rozetler)
- Topics (Konular)
- DifficultyLevels (Zorluk Seviyeleri)
- QuizSessions (Quiz Oturumları)

## 🎮 Oyun Mekanikleri

### Zorluk Seviyeleri ve Sayı Aralıkları

#### Kolay Seviye
- İki basamaklı sayılar (10-99)
- Bölme işlemlerinde tam sayı sonuçlar

#### Orta Seviye
- Üç basamaklı sayılar (100-999)
- Bölme işlemlerinde tam sayı sonuçlar

#### Zor Seviye
- Dört basamaklı sayılar (1000-9999)
- Bölme işlemlerinde ondalıklı sonuçlar (max 2 basamak)

#### Deha Seviye
- Dört/Beş basamaklı sayılar
- Bölme işlemlerinde ondalıklı sonuçlar (max 2 basamak)

### Puan Hesaplama
- Temel puan: Doğru cevap başına 10 puan
- Bonus çarpanları:
  - %95+ doğruluk: 1.5x
  - %80+ doğruluk: 1.2x
  - %65+ doğruluk: 1.1x
  - %50+ doğruluk: 1.0x

## 🚀 Kurulum

1. Projeyi klonlayın
```bash
git clone [repo-url]
```

2. Bağımlılıkları yükleyin
```bash
composer install
npm install
```

3. .env dosyasını oluşturun
```bash
cp .env.example .env
```

4. Veritabanını oluşturun
```bash
php artisan migrate
```

5. Örnek verileri yükleyin
```bash
php artisan db:seed
```

6. Uygulamayı çalıştırın
```bash
php artisan serve
npm run dev
```

## 🎯 Gelecek Özellikler

- [✔️] Kayıt Sistemi
- [ ] Arkadaş sistemi
- [ ] Liderlik tablosu
- [✔️] Başarı rozetleri
- [ ] İstatistik paneli
- [ ] Kapsamlı ayarlar paneli
- [✔️] Seviye Sistemi
- [ ] Blog ortamı


## 🤝 İletişim

Proje Sahibi - [@Froxerr](https://https://github.com/Froxerr)
