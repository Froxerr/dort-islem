<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\DifficultyLevel;

class IndexController extends Controller
{
    public function index()
    {
        $activeTopics = Topic::where('is_active', 1)
            ->select('id', 'name', 'icon_path')
            ->get()
            ->map(function ($topic) {
                $topic->icon_path = str_replace('public/', '', $topic->icon_path);
                return $topic;
            });

        $difficultyLevels = DifficultyLevel::select('id', 'name')
            ->get()
            ->map(function ($level) {
                // Seviye adına göre resim yolunu ayarla
                $level->image_path = 'assets/img/levels/' . strtolower($level->name) . '.png';
                return $level;
            });

        return view('index', compact('activeTopics', 'difficultyLevels'));
    }
}
