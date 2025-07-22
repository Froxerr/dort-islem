<?php

namespace Database\Seeders;

use App\Models\quizSession;
use App\Models\User;
use Database\Factories\QuizSessionFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Önce 300 adet rastgele test oturumu oluşturalım
        QuizSession::factory()->count(300)->create();

        // 2. (Önemli) Her kullanıcının toplam XP'sini ve seviyesini güncelleyelim
        // Bu işlem, verilerin tutarlı olmasını sağlar.
        $users = User::all();
        foreach ($users as $user) {
            // Her kullanıcının quizSession tablosundaki tüm xp_earned değerlerini topla
            $totalXp = $user->quizSessions()->sum('xp_earned');

            // levels tablosuna bakarak bu XP'nin hangi seviyeye denk geldiğini bul
            // (Bu mantığı daha sonra bir servise taşınabilir)
            $level = DB::table('levels')->where('xp_required_for_next_level', '<=', $totalXp)->max('level');

            // Kullanıcının bilgilerini güncelle
            $user->xp = $totalXp;
            $user->level = $level ?? 1; // Eğer hiç seviye atlayamadıysa 1. seviye
            $user->save();
        }
    }
}
