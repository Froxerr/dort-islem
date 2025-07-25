<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonData = $this->getDecode("achievements");
        foreach ($jsonData as $data)
        {
            Achievement::create([
                'name' => $data->name,
                'description' => $data->description,
                'category' => $data->category,
                'requirement_type' => $data->requirement_type,
                'requirement_value' => $data->requirement_value,
                'xp_reward' => $data->xp_reward,
                'has_badge' => $data->has_badge,
                'topic_id' => $data->topic_id ?? null // Eğer topic_id yoksa null olacak
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
