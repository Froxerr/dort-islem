<?php

namespace Database\Seeders;

use App\Models\UserAchievement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserAchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonData = $this->getDecode("user_achievements");
        foreach ($jsonData as $data)
        {
            UserAchievement::create([
                'progress' => $data->progress,
                'completed' => $data->completed,
                'completed_at' => $data->completed_at,
                'user_id' => $data->user_id,
                'achievement_id' => $data->achievement_id
            ]);
        }
    }
    public function getDecode(string $rote): array
    {
        $jsonPath = database_path('data/'.$rote.'.json');
        $prioritiesJson = file_get_contents($jsonPath);
        $decodedData = json_decode($prioritiesJson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON çözümleme hatası: ' . json_last_error_msg());
        }

        return $decodedData;
    }
}
