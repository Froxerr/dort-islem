<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Badge;
use App\Models\Topic;
use App\Models\User;


class UserBadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Daha az veritabanı sorgusu için tüm rozetleri ve konuları başta alalım
         $badges = Badge::all()->keyBy('name');
         $topics = Topic::all()->keyBy('name');
         $users = User::with('quizSessions')->get(); // quizSessions ilişkisini de yükle
 
         foreach ($users as $user) {
             // --- Kural 1: "Kaşif Ruhu" Rozeti ---
             // En az 1 test tamamladıysa ver.
             if ($user->quizSessions->count() > 0) {
                 $user->badges()->syncWithoutDetaching($badges['Kaşif Ruhu']->id);
             }
 
             // --- Kural 2: "Karınca Çalışkanlığı" Rozeti ---
             // Toplamda 100 veya daha fazla test tamamladıysa ver.
             if ($user->quizSessions->count() >= 100) {
                 $user->badges()->syncWithoutDetaching($badges['Karınca Çalışkanlığı']->id);
             }
 
             // --- Kural 3: "Toplama Ustası" Rozeti ---
             // Sadece Toplama konusunda 5000'den fazla puan aldıysa ver.
             $toplamaTopicId = $topics['Toplama']->id;
             $toplamaScore = $user->quizSessions->where('topic_id', $toplamaTopicId)->sum('score');
             if ($toplamaScore > 5000) {
                 $user->badges()->syncWithoutDetaching($badges['Toplama Ustası']->id);
             }
             
         }
    }
}
