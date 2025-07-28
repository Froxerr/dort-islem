@extends('layouts.app')

@section('title', 'Keşif Haritası | Maceraya Başla')

@section('css')
    <link rel="stylesheet" href="{{asset('assets/css/anasayfa.css')}}">
@endsection

@section('content')
<div class="nature-bg">
    <div class="stars"></div>
    <div class="twinkling"></div>

    <div class="content-wrapper">
        <div class="button-wrapper">
            <button class="start-button">Keşfe Başla!</button>
            <a href="{{ route('login') }}" ><button class="login-button">Giriş Yap</button></a>
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
