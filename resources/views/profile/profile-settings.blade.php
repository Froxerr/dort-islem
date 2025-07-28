@extends('layouts.app')

@section('title', 'Ayarlar')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@php
    use Illuminate\Support\Facades\Storage;
@endphp
<style>
    :root {
        --primary-color: #4CAF50;
        --primary-dark: #2E7D32;
        --primary-light: #8BC34A;
        --secondary-color: #FFD700;
        --background-start: #f0f7f4;
        --background-end: #e8f5e9;
        --text-primary: #2c3e50;
        --text-secondary: #666666;
        --card-background: rgba(255, 255, 255, 0.95);
        --success-color: #4CAF50;
        --warning-color: #FF9800;
        --danger-color: #F44336;
        --info-color: #2196F3;
    }

    body {
        background: linear-gradient(135deg, var(--background-start) 0%, var(--background-end) 100%);
        min-height: 100vh;
        margin: 0;
        font-family: 'Comic Sans MS', 'Chalkboard SE', 'Marker Felt', sans-serif;
        color: var(--text-primary);
        line-height: 1.6;
    }

    .settings-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 2rem;
        position: relative;
    }

    /* Geri Dönme Butonu */
    .back-button {
        position: fixed;
        top: 2rem;
        left: 2rem;
        background: #4CAF50;
        color: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 25px;
        cursor: pointer;
        box-shadow: 
            0 4px 15px rgba(76, 175, 80, 0.3),
            0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .back-button:hover {
        transform: translateY(-2px) scale(1.1);
        box-shadow: 
            0 6px 20px rgba(76, 175, 80, 0.4),
            0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .back-button i {
        font-size: 1.5rem;
        transition: transform 0.3s ease;
    }

    .back-button:hover i {
        transform: translateX(-2px);
    }

    /* Başlık */
    .page-title {
        text-align: center;
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--primary-color);
        margin-bottom: 2rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }

    /* Tab Sistemi */
    .settings-tabs {
        background: var(--card-background);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 25px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 
            12px 12px 24px rgba(0,0,0,0.1),
            -4px -4px 24px rgba(255,255,255,0.8);
        position: relative;
        overflow: hidden;
    }

    .tab-buttons {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        position: relative;
        z-index: 10;
    }

    .tab-btn {
        padding: 0.75rem 1.5rem;
        border: 2px solid var(--primary-color);
        border-radius: 15px;
        background: white;
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 
            4px 4px 8px rgba(0,0,0,0.1),
            -2px -2px 8px rgba(255,255,255,0.8);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tab-btn:hover {
        transform: translateY(-2px);
        box-shadow: 
            6px 6px 12px rgba(0,0,0,0.15),
            -4px -4px 12px rgba(255,255,255,0.9);
    }

    .tab-btn.active {
        background: linear-gradient(145deg, var(--primary-color), var(--primary-dark));
        color: white;
        transform: translateY(1px);
        box-shadow: inset 2px 2px 4px rgba(0,0,0,0.2);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Form Stili */
    .settings-form {
        display: grid;
        gap: 2rem;
    }

    .form-section {
        background: rgba(255, 255, 255, 0.7);
        border-radius: 15px;
        padding: 1.5rem;
        border: 1px solid rgba(76, 175, 80, 0.2);
    }

    .form-section h3 {
        margin: 0 0 1rem 0;
        color: var(--primary-color);
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid rgba(76, 175, 80, 0.3);
        border-radius: 10px;
        background: white;
        font-family: inherit;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 100px;
    }

    /* Toggle Switch */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 30px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 30px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }

    input:checked + .toggle-slider {
        background-color: var(--primary-color);
    }

    input:checked + .toggle-slider:before {
        transform: translateX(30px);
    }

    /* Checkbox ve Radio Stili */
    .form-checkbox, .form-radio {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        cursor: pointer;
    }

    .form-checkbox input, .form-radio input {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
    }

    /* Buton Stili */
    .btn {
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-family: inherit;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        justify-content: center;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: var(--text-secondary);
        color: white;
    }

    .btn-secondary:hover {
        background: #555;
        transform: translateY(-2px);
    }

    .btn-danger {
        background: var(--danger-color);
        color: white;
    }

    .btn-danger:hover {
        background: #d32f2f;
        transform: translateY(-2px);
    }

    .btn-group {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
    }

    /* Alert Messages */
    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.1);
        border: 1px solid var(--success-color);
        color: var(--success-color);
    }

    .alert-danger {
        background: rgba(244, 67, 54, 0.1);
        border: 1px solid var(--danger-color);
        color: var(--danger-color);
    }

    .alert-info {
        background: rgba(33, 150, 243, 0.1);
        border: 1px solid var(--info-color);
        color: var(--info-color);
    }

    /* Profil Resmi */
    .profile-image-section {
        text-align: center;
        margin-bottom: 2rem;
    }

    .profile-image-preview {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid var(--primary-color);
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, #f0f0f0, #e0e0e0);
        font-size: 3rem;
        color: var(--text-secondary);
    }

    .profile-image-upload {
        position: relative;
        display: inline-block;
    }

    .profile-image-upload input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .settings-container {
            padding: 1rem;
        }

        .tab-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn-group {
            flex-direction: column;
        }

        .back-button {
            top: 1rem;
            left: 1rem;
        }
    }
    
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    .modal-content {
        background: linear-gradient(145deg, var(--card-background), #ffffff);
        border-radius: 20px;
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        box-shadow: 
            0 20px 60px rgba(0, 0, 0, 0.3),
            0 8px 20px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.5);
        transform: scale(0.8) translateY(30px);
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        position: relative;
        isolation: isolate;
    }
    
    .modal-overlay.active .modal-content {
        transform: scale(1) translateY(0);
    }
    
    .modal-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .modal-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
    }
    
    .modal-icon.danger {
        background: linear-gradient(135deg, var(--danger-color), #d32f2f);
    }
    
    .modal-icon.warning {
        background: linear-gradient(135deg, var(--warning-color), #f57c00);
    }
    
    .modal-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--text-primary);
        margin: 0 0 0.5rem 0;
    }
    
    .modal-description {
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }
    
    .modal-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .modal-btn {
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-family: inherit;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        justify-content: center;
        min-width: 120px;
    }
    
    .modal-btn-cancel {
        background: var(--text-secondary);
        color: white;
    }
    
    .modal-btn-cancel:hover {
        background: #555;
        transform: translateY(-2px);
    }
    
    .modal-btn-danger {
        background: var(--danger-color);
        color: white;
    }
    
    .modal-btn-danger:hover {
        background: #d32f2f;
        transform: translateY(-2px);
    }
    
    .modal-btn-warning {
        background: var(--warning-color);
        color: white;
    }
    
    .modal-btn-warning:hover {
        background: #f57c00;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .modal-content {
            padding: 1.5rem;
            margin: 1rem;
        }
        
        .modal-actions {
            flex-direction: column;
        }
        
        .modal-btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<a href="{{ route('main') }}" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="settings-container">
    <h1 class="page-title">
        <i class="fas fa-cog"></i>
        Ayarlar
    </h1>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="settings-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="profile">
                <i class="fas fa-user"></i> Profil
            </button>
            <button class="tab-btn" data-tab="account">
                <i class="fas fa-shield-alt"></i> Hesap
            </button>
            <button class="tab-btn" data-tab="preferences">
                <i class="fas fa-sliders-h"></i> Tercihler
            </button>
            <button class="tab-btn" data-tab="notifications">
                <i class="fas fa-bell"></i> Bildirimler
            </button>
            <button class="tab-btn" data-tab="privacy">
                <i class="fas fa-lock"></i> Gizlilik
            </button>
        </div>

        <!-- Profil Tab -->
        <div class="tab-content active" id="profile-content">
            <form method="POST" action="{{ route('profile.settings.update') }}" enctype="multipart/form-data" class="settings-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="profile">

                <div class="form-section">
                    <h3><i class="fas fa-user-circle"></i> Profil Bilgileri</h3>
                    
                    <div class="profile-image-section">
                        <div class="profile-image-preview">
                            @if($user->profile_image)
                                <img src="{{ Storage::url($user->profile_image) }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" alt="Profil Resmi">
                            @else
                                <i class="fas fa-user"></i>
                            @endif
                        </div>
                        <div class="profile-image-upload">
                            <input type="file" name="profile_image" accept="image/*" id="profile-image-input" style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('profile-image-input').click()">
                                <i class="fas fa-camera"></i> Profil Resmi Değiştir
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-input" value="{{ old('username', $user->username) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-textarea" placeholder="Kendin hakkında birkaç kelime...">{{ old('bio', $user->bio ?? '') }}</textarea>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>

        <!-- Hesap Tab -->
        <div class="tab-content" id="account-content">
            <form method="POST" action="{{ route('profile.settings.update') }}" class="settings-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="account">

                <div class="form-section">
                    <h3><i class="fas fa-key"></i> Şifre Değiştir</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Mevcut Şifre</label>
                        <input type="password" name="current_password" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Yeni Şifre</label>
                        <input type="password" name="password" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Yeni Şifre (Tekrar)</label>
                        <input type="password" name="password_confirmation" class="form-input">
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-shield-alt"></i> Hesap Güvenliği</h3>
                    
                    <div class="form-group">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            İki faktörlü kimlik doğrulama özelliği geçici olarak devre dışı bırakıldı.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="login_notifications" {{ ($user->login_notifications ?? false) ? 'checked' : '' }}>
                            Giriş bildirimlerini e-posta ile gönder
                        </label>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>

        <!-- Tercihler Tab -->
        <div class="tab-content" id="preferences-content">
            <form method="POST" action="{{ route('profile.settings.update') }}" class="settings-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="preferences">

                <div class="form-section">
                    <h3><i class="fas fa-gamepad"></i> Oyun Tercihleri</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Varsayılan Zorluk Seviyesi</label>
                        <select name="default_difficulty" class="form-select">
                            <option value="">Seçim yok</option>
                            @foreach($difficulties as $difficulty)
                                <option value="{{ $difficulty->id }}" {{ ($user->default_difficulty_id ?? '') == $difficulty->id ? 'selected' : '' }}>
                                    {{ $difficulty->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Favori Konu</label>
                        <select name="favorite_topic" class="form-select">
                            <option value="">Seçim yok</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}" {{ ($user->favorite_topic_id ?? '') == $topic->id ? 'selected' : '' }}>
                                    {{ $topic->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="auto_next_question" {{ ($user->auto_next_question ?? false) ? 'checked' : '' }}>
                            Otomatik sonraki soruya geç
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="show_correct_answers" {{ ($user->show_correct_answers ?? true) ? 'checked' : '' }}>
                            Yanlış cevaplardan sonra doğru cevabı göster
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-volume-up"></i> Ses ve Görsel</h3>
                    
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="sound_effects" {{ ($user->sound_effects ?? true) ? 'checked' : '' }}>
                            Ses efektlerini etkinleştir
                        </label>
                    </div>



                    <div class="form-group">
                        <label class="form-label">Tema</label>
                        <select name="theme" class="form-select">
                            <option value="light" {{ ($user->theme ?? 'light') == 'light' ? 'selected' : '' }}>Açık Tema</option>
                            <option value="dark" {{ ($user->theme ?? 'light') == 'dark' ? 'selected' : '' }}>Koyu Tema</option>
                            <option value="auto" {{ ($user->theme ?? 'light') == 'auto' ? 'selected' : '' }}>Otomatik</option>
                        </select>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>

        <!-- Bildirimler Tab -->
        <div class="tab-content" id="notifications-content">
            <form method="POST" action="{{ route('profile.settings.update') }}" class="settings-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="notifications">

                <div class="form-section">
                    <h3><i class="fas fa-envelope"></i> E-posta Bildirimleri</h3>
                    
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="email_achievements" {{ ($user->email_achievements ?? true) ? 'checked' : '' }}>
                            Yeni başarım kazandığında e-posta gönder
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="email_level_up" {{ ($user->email_level_up ?? true) ? 'checked' : '' }}>
                            Seviye atladığında e-posta gönder
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="email_weekly_summary" {{ ($user->email_weekly_summary ?? false) ? 'checked' : '' }}>
                            Haftalık özet e-postası gönder
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="email_reminders" {{ ($user->email_reminders ?? false) ? 'checked' : '' }}>
                            Quiz hatırlatma e-postaları gönder
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-bell"></i> Uygulama Bildirimleri</h3>
                    
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="push_achievements" {{ ($user->push_achievements ?? true) ? 'checked' : '' }}>
                            Başarım bildirimlerini göster
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="push_level_up" {{ ($user->push_level_up ?? true) ? 'checked' : '' }}>
                            Seviye atlama bildirimlerini göster
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="push_quiz_complete" {{ ($user->push_quiz_complete ?? true) ? 'checked' : '' }}>
                            Quiz tamamlama bildirimlerini göster
                        </label>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>

        <!-- Gizlilik Tab -->
        <div class="tab-content" id="privacy-content">
            <form method="POST" action="{{ route('profile.settings.update') }}" class="settings-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="privacy">

                <div class="form-section">
                    <h3><i class="fas fa-eye"></i> Profil Görünürlüğü</h3>
                    
                    <div class="form-group">
                        <label class="form-radio">
                            <input type="radio" name="profile_visibility" value="public" {{ ($user->profile_visibility ?? 'public') == 'public' ? 'checked' : '' }}>
                            Herkese açık - Profilim herkese görünür
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-radio">
                            <input type="radio" name="profile_visibility" value="friends" {{ ($user->profile_visibility ?? 'public') == 'friends' ? 'checked' : '' }}>
                            Sadece arkadaşlar - Profilim sadece arkadaşlarıma görünür
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-radio">
                            <input type="radio" name="profile_visibility" value="private" {{ ($user->profile_visibility ?? 'public') == 'private' ? 'checked' : '' }}>
                            Özel - Profilim sadece bana görünür
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-chart-bar"></i> İstatistik Paylaşımı</h3>
                    
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="show_stats" {{ ($user->show_stats ?? true) ? 'checked' : '' }}>
                            İstatistiklerimi diğer kullanıcılara göster
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="show_achievements" {{ ($user->show_achievements ?? true) ? 'checked' : '' }}>
                            Başarımlarımı diğer kullanıcılara göster
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="show_activity" {{ ($user->show_activity ?? true) ? 'checked' : '' }}>
                            Aktivitelerimi diğer kullanıcılara göster
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-trash-alt"></i> Tehlikeli Alan</h3>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Bu işlemler geri alınamaz. Lütfen dikkatli olun.
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-secondary" onclick="openResetModal()">
                            <i class="fas fa-undo"></i> Tüm İlerlemeyi Sıfırla
                        </button>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-danger" onclick="openDeleteModal()">
                            <i class="fas fa-user-times"></i> Hesabı Sil
                        </button>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Progress Modal -->
<div class="modal-overlay" id="resetModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon warning">
                <i class="fas fa-undo"></i>
            </div>
            <h2 class="modal-title">İlerleme Sıfırlama</h2>
            <p class="modal-description">
                Tüm quiz geçmişiniz, XP'niz, seviyeniz, rozetleriniz ve başarımlarınız silinecek. 
                Bu işlem <strong>geri alınamaz</strong>!
                <br><br>
                Devam etmek istediğinizden emin misiniz?
            </p>
        </div>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="closeResetModal()">
                <i class="fas fa-times"></i> Vazgeç
            </button>
            <button class="modal-btn modal-btn-warning" onclick="confirmReset()">
                <i class="fas fa-undo"></i> Evet, Sıfırla
            </button>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon danger">
                <i class="fas fa-user-times"></i>
            </div>
            <h2 class="modal-title">Hesap Silme</h2>
            <p class="modal-description">
                Bu işlem hesabınızı <strong>kalıcı olarak</strong> silecektir. 
                Tüm verileriniz, arkadaşlıklarınız, mesajlarınız ve ilerlemeniz kaybolacak.
                <br><br>
                <strong>Bu işlem kesinlikle geri alınamaz!</strong>
                <br><br>
                Hesabınızı gerçekten silmek istiyor musunuz?
            </p>
        </div>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i> Vazgeç
            </button>
            <button class="modal-btn modal-btn-danger" onclick="confirmDelete()">
                <i class="fas fa-trash"></i> Evet, Hesabı Sil
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.dataset.tab;
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            document.getElementById(targetTab + '-content').classList.add('active');
        });
    });
    
    // Profile image upload
    const profileImageUpload = document.getElementById('profile-image-input');
    const profileImagePreview = document.querySelector('.profile-image-preview');
    
    if (profileImageUpload) {
        profileImageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Lütfen geçerli bir resim dosyası seçin (JPEG, PNG, JPG, GIF, WEBP)');
                    this.value = '';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2048 * 1024) {
                    alert('Resim dosyası 2MB\'dan küçük olmalıdır');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImagePreview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" alt="Profil Resmi">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

function openResetModal() {
    document.getElementById('resetModal').classList.add('active');
}

function closeResetModal() {
    document.getElementById('resetModal').classList.remove('active');
}

function confirmReset() {
    if (confirm('Tüm ilerlemenizi sıfırlamak istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
        // AJAX call to clear progress
        fetch('{{ route("profile.settings.clear-progress") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        }).then(response => {
            if (response.ok) {
                alert('İlerlemeniz başarıyla sıfırlandı.');
                location.reload();
            } else {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            }
        });
        closeResetModal();
    }
}

function openDeleteModal() {
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

function confirmDelete() {
    if (confirm('Hesabınızı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz ve tüm verileriniz silinecek!')) {
        if (confirm('Son kez soruyorum: Hesabınızı gerçekten silmek istiyor musunuz?')) {
            // AJAX call to delete account
            fetch('{{ route("profile.settings.delete-account") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            }).then(response => {
                if (response.ok) {
                    alert('Hesabınız başarıyla silindi. Ana sayfaya yönlendiriliyorsunuz.');
                    window.location.href = '{{ route("index") }}';
                } else {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            });
        }
    }
    closeDeleteModal();
}

// 2FA özelliği geçici olarak devre dışı
</script>
@endsection 