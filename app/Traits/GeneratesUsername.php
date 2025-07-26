<?php

namespace App\Traits;

use Illuminate\Support\Str;
use App\Models\User;

trait GeneratesUsername
{
    /**
     * Benzersiz kullanıcı adı oluştur
     */
    public function generateUniqueUsername(string $name): string
    {
        // İsmi küçük harfe çevir ve türkçe karakterleri değiştir
        $name = $this->convertTurkishChars(mb_strtolower($name));
        
        // İsimden kullanıcı adı oluştur (boşlukları kaldır)
        $username = str_replace(' ', '', $name);
        
        // Eğer bu kullanıcı adı kullanılmışsa sonuna rastgele sayı ekle
        $originalUsername = $username;
        $counter = 1;
        
        while (User::where('username', $username)->exists()) {
            $username = $originalUsername . rand(pow(10, $counter-1), pow(10, $counter)-1);
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Türkçe karakterleri ingilizce karakterlere çevir
     */
    private function convertTurkishChars(string $text): string
    {
        $search = ['ç', 'ğ', 'ı', 'i', 'ö', 'ş', 'ü'];
        $replace = ['c', 'g', 'i', 'i', 'o', 's', 'u'];
        
        return str_replace($search, $replace, $text);
    }
} 