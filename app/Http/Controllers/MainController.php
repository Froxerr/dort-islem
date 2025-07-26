<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\DifficultyLevel;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function main()
    {
        if (!auth()->check()) {
            return redirect()->route('index');
        }
        
        $user = auth()->user();
        
        $topics = Topic::where('is_active', 1)
            ->select('id', 'name', 'icon_path')
            ->get();

        $difficultyLevels = DifficultyLevel::select('id', 'name', 'xp_multiplier')
            ->get()
            ->map(function ($level) {
                // Seviye adına göre resim yolunu ayarla
                $level->image_path = 'assets/img/levels/' . strtolower($level->name) . '.png';
                return $level;
            });

        // Kullanıcı tercihlerini al
        $userSettings = [
            'default_difficulty_id' => $user->default_difficulty_id,
            'favorite_topic_id' => $user->favorite_topic_id,
            'auto_next_question' => $user->auto_next_question ?? false,
            'show_correct_answers' => $user->show_correct_answers ?? true,
            'sound_effects' => $user->sound_effects ?? true,
            'animations' => $user->animations ?? true,
        ];

        return view('main', compact('topics', 'difficultyLevels', 'userSettings'));
    }
}
