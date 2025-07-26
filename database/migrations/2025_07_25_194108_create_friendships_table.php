<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            
            // Arkadaşlık isteği gönderen kullanıcı
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('Arkadaşlık isteği gönderen kullanıcı');
            
            // Arkadaşlık isteği alan kullanıcı  
            $table->foreignId('friend_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('Arkadaşlık isteği alan kullanıcı');
            
            // Arkadaşlık durumu
            $table->enum('status', ['pending', 'accepted', 'blocked'])
                  ->default('pending')
                  ->comment('pending: Bekliyor, accepted: Kabul edildi, blocked: Engellendi');
            
            // Kabul edilme tarihi (opsiyonel)
            $table->timestamp('accepted_at')->nullable()->comment('Arkadaşlık kabul edilme tarihi');
            
            $table->timestamps();
            
            // Unique constraint - Aynı kişiye birden fazla istek gönderilemesin
            $table->unique(['user_id', 'friend_id'], 'unique_friendship');
            
            // Index'ler - Hızlı sorgu için
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['friend_id', 'status'], 'idx_friend_status');
            $table->index('status', 'idx_status');
            $table->index('accepted_at', 'idx_accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
