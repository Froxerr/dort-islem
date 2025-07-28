@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .hub-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
        position: relative;
        overflow: hidden;
    }

    /* Arkaplan deseni */
    .hub-container::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.1;
    }

    .hub-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        max-width: 1000px;
        width: 100%;
        padding: 2rem;
        position: relative;
        z-index: 1;
    }

    .hub-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 2rem 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 3px solid transparent;
        box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 220px;
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }

    .hub-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(45deg, rgba(76, 175, 80, 0.1), rgba(139, 195, 74, 0.1));
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .hub-card:hover {
        transform: translateY(-8px) scale(1.02);
        border-color: #4CAF50;
        box-shadow: 
            0 20px 40px rgba(76, 175, 80, 0.2),
            0 0 20px rgba(76, 175, 80, 0.1) inset;
    }

    .hub-card:hover::before {
        opacity: 1;
    }

    .hub-card i {
        font-size: 2.8rem;
        margin-bottom: 1rem;
        color: #4CAF50;
        transition: all 0.3s ease;
        animation: float 3s ease-in-out infinite;
    }

    .hub-card:hover i {
        transform: scale(1.2);
        color: #2E7D32;
    }

    .hub-card h2 {
        font-size: 1.5rem;
        margin-bottom: 0.8rem;
        color: #2c3e50;
        font-family: 'Comic Sans MS', cursive;
        position: relative;
    }

    .hub-card h2::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background: linear-gradient(45deg, #4CAF50, #8BC34A);
        border-radius: 2px;
        transition: width 0.3s ease;
    }

    .hub-card:hover h2::after {
        width: 100px;
    }

    .hub-card p {
        color: #666;
        line-height: 1.5;
        font-size: 0.95rem;
        margin-top: 0.8rem;
    }

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

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    @media (max-width: 1024px) {
        .hub-grid {
            gap: 1.2rem;
            padding: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .hub-card {
            padding: 1.5rem 1.2rem;
            min-height: 200px;
        }

        .hub-card h2 {
            font-size: 1.4rem;
        }
        
        .hub-card i {
            font-size: 2.5rem;
        }
    }

    @media (max-width: 768px) {
        .hub-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
            padding: 1rem;
        }
        
        .hub-card {
            min-height: 180px;
            padding: 1.2rem 1rem;
        }
        
        .hub-card i {
            font-size: 2.2rem;
        }

        .back-button {
            top: 1rem;
            left: 1rem;
        }
    }
</style>
@endsection

@section('content')
<a href="{{ route('main') }}" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="hub-container">
    <div class="hub-grid">
        <a href="{{ route('profile.details') }}" class="hub-card">
            <i class="fas fa-user-circle"></i>
            <h2>Profil Detayları</h2>
            <p>Seviye durumunu, rozetlerini ve istatistiklerini görüntüle</p>
        </a>
        
        <a href="{{ route('profile.achievements') }}" class="hub-card">
            <i class="fas fa-trophy"></i>
            <h2>Başarılarım</h2>
            <p>Kazandığın başarıları ve ilerleme durumunu incele</p>
        </a>
        
        <a href="{{ route('profile.history') }}" class="hub-card">
            <i class="fas fa-history"></i>
            <h2>Quiz Geçmişi</h2>
            <p>Geçmiş quiz performansını ve sonuçlarını görüntüle</p>
        </a>
    </div>
</div>
@endsection 