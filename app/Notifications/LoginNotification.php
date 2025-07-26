<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $loginData;

    /**
     * Create a new notification instance.
     */
    public function __construct($loginData)
    {
        $this->loginData = $loginData;
        
        Log::info('📧 LoginNotification oluşturuldu', [
            'ip' => $loginData['ip'],
            'device' => $loginData['device'],
            'date' => $loginData['date'],
            'time' => $loginData['time']
        ]);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        Log::info('📧 E-posta hazırlanıyor', [
            'recipient' => $notifiable->email,
            'name' => $notifiable->name ?: $notifiable->username,
            'subject' => '🔐 Hesabınıza Giriş Yapıldı - ' . config('app.name')
        ]);
        
        try {
            $mailMessage = (new MailMessage)
                ->subject('🔐 Hesabınıza Giriş Yapıldı - ' . config('app.name'))
                ->greeting('Merhaba ' . ($notifiable->name ?: $notifiable->username) . '!')
                ->line('Hesabınıza başarılı bir giriş yapıldı.')
                ->line('**Giriş Detayları:**')
                ->line('📅 **Tarih:** ' . $this->loginData['date'])
                ->line('🕐 **Saat:** ' . $this->loginData['time'])
                ->line('🌐 **IP Adresi:** ' . $this->loginData['ip'])
                ->line('💻 **Cihaz:** ' . $this->loginData['device'])
                ->line('🏢 **Konum:** ' . $this->loginData['location'])
                ->line('')
                ->line('Bu giriş siz değilseniz, lütfen derhal şifrenizi değiştirin ve hesap güvenliğinizi kontrol edin.')
                ->action('Hesap Ayarlarına Git', route('profile.settings'))
                ->line('Hesabınızın güvenliği bizim için önemlidir.')
                                    ->salutation('Güvenle kalın,' . "\n" . '🎯 ' . config('app.name') . ' Takımı');
                
            Log::info('✅ E-posta başarıyla hazırlandı');
            return $mailMessage;
            
        } catch (\Exception $e) {
            Log::error('❌ E-posta hazırlanırken hata', [
                'error' => $e->getMessage(),
                'recipient' => $notifiable->email
            ]);
            throw $e;
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'login',
            'ip' => $this->loginData['ip'],
            'device' => $this->loginData['device'],
            'location' => $this->loginData['location'],
            'date' => $this->loginData['date'],
            'time' => $this->loginData['time'],
        ];
    }
} 