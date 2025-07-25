<?php

namespace Database\Seeders;

use App\Models\BadgeTrigger;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BadgeTriggerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonData = $this->getDecode("badge_triggers");
        foreach ($jsonData as $data)
        {
            BadgeTrigger::create([
                'trigger_type' => $data->trigger_type,
                'required_count' => $data->required_count,
                'required_score' => $data->required_score,
                'time_limit' => $data->time_limit ?? null,
                'badge_id' => $data->badge_id,
                'topic_id' => $data->topic_id,

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
