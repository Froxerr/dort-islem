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

        return view('main', compact('topics', 'difficultyLevels'));
    }
}
