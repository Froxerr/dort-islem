<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonData = $this->getDecode("roles");
        foreach ($jsonData as $data) {
            Role::create([
                'name' => $data->name,
                'description' => $data->description
            ]);
        }

    }

    public function getDecode(string $rote): array
    {
        $jsonPath = database_path('data/' . $rote . '.json');
        $prioritiesJson = file_get_contents($jsonPath);
        $decodedData = json_decode($prioritiesJson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON çözümleme hatası: ' . json_last_error_msg());
        }

        return $decodedData;
    }
}
