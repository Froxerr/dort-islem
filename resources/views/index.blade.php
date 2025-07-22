@extends('layouts.app')

@section('title', 'Keşif Haritası | Maceraya Başla')

@section('css')
    <link rel="stylesheet" href="{{asset('assets/css/anasayfa.css')}}">
@endsection

@section('content')
<div class="nature-bg">
    <!-- Bulutlar -->
    <img src="/assets/img/bulut/bulut1.png" alt="Bulut 1" class="cloud cloud1">
    <img src="/assets/img/bulut/bulut2.png" alt="Bulut 2" class="cloud cloud2">
    <img src="/assets/img/bulut/bulut3.png" alt="Bulut 3" class="cloud cloud3">
    <img src="/assets/img/bulut/bulut4.png" alt="Bulut 4" class="cloud cloud4">

    <!-- Ağaçlar -->
    <img src="/assets/img/agac/sol-agac.png" alt="Sol Ağaç" class="tree left">
    <img src="/assets/img/agac/sag-agac.png" alt="Sağ Ağaç" class="tree right">

    <div class="content-wrapper">
        <div class="button-wrapper">
            <div class="button-glow"></div>
            <button class="start-button">Keşfe Başla!</button>
        </div>
    </div>

    <div class="bubo-container">
        <img src="/assets/img/dalkus-left.png" alt="Bilge Baykuş Bubo">
        <div class="speech-bubble">Merhaba küçük kaşif! Maceraya hazır mısın?</div>
    </div>
</div>

<!-- Verileri JSON olarak sakla -->
<script id="app-data" type="application/json">
    {
        "topics": {!! json_encode($activeTopics) !!},
        "difficultyLevels": {!! json_encode($difficultyLevels) !!}
    }
</script>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="{{ asset('assets/js/questionGenerator.js') }}"></script>
<script src="{{ asset('assets/js/anasayfa.js') }}"></script>
@endsection
