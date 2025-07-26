@extends('layouts.app')

@section('title', 'Liderlik Tablosu')

@section('content')
@vite(['resources/css/app.css', 'resources/js/app.js'])

    <a href="{{ route('main') }}" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

<div class="min-h-screen flex flex-col items-center justify-center px-4" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding-top: 40px; padding-bottom: 40px;">


    <div class="bg-white/70 backdrop-blur-sm rounded-[2.5rem] px-16 py-8 min-w-[400px] shadow-xl border border-gray-200 inline-block text-center" style="margin-bottom: 30px;">
        <h1 class="text-5xl font-extrabold text-slate-700 drop-shadow-sm">
        <span class="bg-gradient-to-r from-indigo-500 to-teal-500 bg-clip-text text-transparent">
            Liderlik Tablosu
        </span>
        </h1>
    </div>




    <div class="w-full max-w-5xl flex flex-col items-center">
        <h2 class="text-3xl font-extrabold text-slate-700 text-center mb-8 flex items-center justify-center gap-3 drop-shadow-sm" style="margin-bottom: 30px;">
            <i class="fa-solid fa-medal text-4xl text-amber-500"></i>
            Şampiyonlar Podyumu
            <i class="fa-solid fa-award text-4xl text-amber-500"></i>
        </h2>
        <div class="flex flex-col md:flex-row items-end justify-center gap-8 w-full" style="margin-bottom: 30px;">
            @foreach($topUsers->take(3) as $index => $user)
                <div class="relative group flex-1 flex flex-col items-center">
                    @if($index == 1) {{-- 2. Kullanıcı --}}
                        <div class="bg-gradient-to-b from-slate-50 to-gray-100 rounded-3xl p-6 shadow-xl border-2 border-slate-300 w-64 h-80 flex flex-col items-center justify-center cartoon-card transition-all duration-300 group-hover:scale-105 group-hover:-translate-y-2">
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 text-6xl"><i class="fa-solid fa-medal text-slate-400"></i></div>
                            <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-slate-300 shadow-lg mb-4 bg-white flex items-center justify-center">
                                @if($user->profile_image)
                                    <img src="{{ Storage::url($user->profile_image) }}" alt="{{ $user->username }}" class="object-cover w-full h-full">
                                @else
                                    <i class="fa-solid fa-user text-5xl text-slate-400"></i>
                                @endif
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-1">{{ $user->name }}</h3>
                            <p class="text-slate-500 text-sm mb-3">{{ $user->username }}</p>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="bg-slate-200 text-slate-600 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-star text-amber-500"></i> Seviye {{ $user->level }}
                                </span>
                            </div>
                            <div class="bg-indigo-100 text-indigo-600 px-3 py-1 rounded-full text-sm font-bold">
                                <i class="fa-solid fa-bolt"></i> {{ number_format($user->xp) }} XP
                            </div>
                        </div>
                    @elseif($index == 0) {{-- 1. Kullanıcı --}}
                        <div class="bg-gradient-to-b from-amber-50 to-yellow-100 rounded-3xl p-8 shadow-2xl border-2 border-amber-400 w-72 h-96 flex flex-col items-center justify-center relative cartoon-card-winner transition-all duration-300 group-hover:scale-110 group-hover:-translate-y-4">
                            <div class="absolute -top-12 left-1/2 -translate-x-1/2 text-8xl animate-bounce"><i class="fa-solid fa-crown text-amber-500"></i></div>
                            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-amber-400 shadow-xl mb-6 bg-white flex items-center justify-center">
                                @if($user->profile_image)
                                    <img src="{{ Storage::url($user->profile_image) }}" alt="{{ $user->username }}" class="object-cover w-full h-full">
                                @else
                                    <i class="fa-solid fa-user text-6xl text-slate-400"></i>
                                @endif
                            </div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-2">{{ $user->name }}</h3>
                            <p class="text-slate-500 text-sm mb-4">{{ $user->username }}</p>
                            <div class="flex items-center gap-2 mb-4">
                                <span class="bg-amber-200 text-amber-800 px-4 py-2 rounded-full text-sm font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-star text-amber-500"></i> Seviye {{ $user->level }}
                                </span>
                            </div>
                            <div class="bg-indigo-100 text-indigo-600 px-4 py-2 rounded-full text-sm font-bold">
                                <i class="fa-solid fa-bolt"></i> {{ number_format($user->xp) }} XP
                            </div>
                        </div>
                    @else {{-- 3. Kullanıcı --}}
                        <div class="bg-gradient-to-b from-orange-50 to-amber-100 rounded-3xl p-6 shadow-xl border-2 border-orange-300 w-64 h-80 flex flex-col items-center justify-center cartoon-card transition-all duration-300 group-hover:scale-105 group-hover:-translate-y-2">
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 text-6xl"><i class="fa-solid fa-medal text-orange-400"></i></div>
                            <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-orange-300 shadow-lg mb-4 bg-white flex items-center justify-center">
                                @if($user->profile_image)
                                    <img src="{{ Storage::url($user->profile_image) }}" alt="{{ $user->username }}" class="object-cover w-full h-full">
                                @else
                                    <i class="fa-solid fa-user text-5xl text-slate-400"></i>
                                @endif
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-1">{{ $user->name }}</h3>
                            <p class="text-slate-500 text-sm mb-3">{{ $user->username }}</p>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="bg-orange-200 text-orange-800 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-star text-amber-500"></i> Seviye {{ $user->level }}
                                </span>
                            </div>
                            <div class="bg-indigo-100 text-indigo-600 px-3 py-1 rounded-full text-sm font-bold">
                                <i class="fa-solid fa-bolt"></i> {{ number_format($user->xp) }} XP
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="w-full max-w-5xl bg-white/80 backdrop-blur-sm rounded-[2.5rem] shadow-xl border border-gray-200 overflow-hidden mb-16" style="margin-bottom: 30px;">
        <div class="bg-gradient-to-r from-indigo-500 to-teal-500 p-5 w-full flex items-center justify-center">
            <h2 class="text-2xl font-extrabold text-white flex items-center gap-3 drop-shadow-sm">
                <i class="fa-solid fa-table-list text-3xl"></i>
                Tam Liderlik Tablosu
            </h2>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-600 uppercase tracking-wider">Sıra</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-600 uppercase tracking-wider">Kaşif</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-600 uppercase tracking-wider">Seviye</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-600 uppercase tracking-wider">XP</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-600 uppercase tracking-wider">Testler</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-600 uppercase tracking-wider">Rozetler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($topUsers->take(10) as $user)
                    <tr class="hover:bg-indigo-50 transition-colors duration-200 @if($user->rank <= 3) bg-amber-50/50 @endif">

                        <td class="px-6 py-4 text-center">
                            <div class="h-full flex items-center justify-center">
                                @if($user->rank == 1)
                                    <i class="fa-solid fa-crown text-2xl text-amber-500 animate-bounce"></i>
                                @elseif($user->rank == 2)
                                    <i class="fa-solid fa-medal text-2xl text-slate-400"></i>
                                @elseif($user->rank == 3)
                                    <i class="fa-solid fa-medal text-2xl text-orange-400"></i>
                                @else
                                    <span class="bg-gray-200 text-slate-600 font-bold rounded-full w-8 h-8 flex items-center justify-center text-sm">{{ $user->rank }}</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex flex-col items-center text-center gap-1">
                                <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-indigo-200 shadow-lg bg-white flex items-center justify-center mx-auto">
                                    @if($user->profile_image)
                                        <img src="{{ Storage::url($user->profile_image) }}" alt="{{ $user->name }}" class="object-cover w-full h-full">
                                    @else
                                        <i class="fa-solid fa-user text-xl text-slate-400"></i>
                                    @endif
                                </div>
                                <div class="font-bold text-slate-800 text-base">{{ $user->name }}</div>
                                <div class="text-slate-500 text-xs">@ {{ $user->username }}</div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="h-full flex items-center justify-center">
                    <span class="bg-slate-200 text-slate-600 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                        <i class="fa-solid fa-star text-amber-500"></i> {{ $user->level }}
                    </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="h-full flex items-center justify-center">
                    <span class="bg-indigo-100 text-indigo-600 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                        <i class="fa-solid fa-bolt"></i> {{ number_format($user->xp) }}
                    </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="h-full flex items-center justify-center">
                    <span class="bg-teal-100 text-teal-600 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                        <i class="fa-solid fa-brain"></i> {{ $user->total_tests }}
                    </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="h-full flex items-center justify-center">
                    <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                        <i class="fa-solid fa-award"></i> {{ $user->badge_count }}
                    </span>
                            </div>
                        </td>

                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($weeklyActive->count() > 0)
        <div class="w-full max-w-5xl mt-16 flex flex-col items-center">
            <div class="w-full flex flex-col items-center">
                <h2 class="text-2xl font-extrabold text-slate-700 mb-6 flex items-center gap-3 drop-shadow-sm" style="margin-bottom: 30px">
                    <i class="fa-solid fa-fire text-3xl animate-pulse text-red-500"></i>
                    Bu Haftanın Aktif Kaşifleri
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 w-full">
                    @foreach($weeklyActive as $index => $user)
                        <div class="bg-white/70 backdrop-blur-sm rounded-3xl p-6 border border-gray-200 shadow-lg flex flex-col items-center cartoon-card-weekly transition-all duration-300 hover:scale-105 hover:-translate-y-2 relative">
                            @if($index == 0)
                                <div class="absolute -top-8 left-1/2 -translate-x-1/2 text-4xl animate-bounce"><i class="fa-solid fa-crown text-red-500"></i></div>
                            @endif
                            <div class="w-20 h-20 rounded-full overflow-hidden border-4 border-white shadow-lg mb-4 bg-white flex items-center justify-center">
                                @if($user->profile_image)
                                    <img src="{{ Storage::url($user->profile_image) }}" alt="{{ $user->username }}" class="object-cover w-full h-full">
                                @else
                                    <i class="fa-solid fa-user text-4xl text-slate-400"></i>
                                @endif
                            </div>
                            <h3 class="font-bold text-slate-800 text-lg mb-2">{{ $user->username }}</h3>
                            <div class="flex gap-4 text-slate-600 text-sm items-center justify-center">
                                <span class="flex items-center gap-1 bg-teal-100 text-teal-700 px-3 py-1 rounded-full font-bold">
                                    <i class="fa-solid fa-brain"></i> {{ $user->weekly_tests }} test
                                </span>
                                <span class="flex items-center gap-1 bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-bold">
                                    <i class="fa-solid fa-bolt"></i> {{ number_format($user->weekly_score) }} puan
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    .cartoon-card, .cartoon-card-winner, .cartoon-card-weekly {
        box-shadow: 0 8px 32px 0 rgba(100, 116, 139, 0.1);
        transition: all 0.3s ease-in-out;
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
</style>
@endsection
