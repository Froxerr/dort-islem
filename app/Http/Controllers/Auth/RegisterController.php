<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    protected $validEmailDomains = [
        'gmail.com',
        'hotmail.com',
        'outlook.com',
        'yahoo.com',
        'icloud.com',
        'yandex.com',
        'edu.tr',
    ];

    public function showRegistrationForm()
    {
        if (auth()->check()) {
            return redirect()->route('main');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('main');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZçÇğĞıİöÖşŞüÜ\s]+$/'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns,filter',
                'max:255',
                'unique:users',
                function ($attribute, $value, $fail) {
                    // Email formatını kontrol et
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('Geçerli bir e-posta adresi giriniz.');
                        return;
                    }

                    // Domain kontrolü
                    $domain = strtolower(explode('@', $value)[1]);
                    if (!in_array($domain, $this->validEmailDomains)) {
                        $fail('Lütfen geçerli bir e-posta servis sağlayıcısı kullanın (gmail.com, hotmail.com, vb.).');
                        return;
                    }

                    // DNS kaydı kontrolü
                    if (!checkdnsrr($domain, 'MX')) {
                        $fail('Bu e-posta adresi geçerli değil.');
                    }
                }
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.regex' => 'Kaşif adı sadece harflerden oluşmalıdır.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique' => 'Bu e-posta adresi zaten kullanılıyor.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::create([
            'username' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        auth()->login($user);

        return redirect()->route('main')->with('success', 'Hoş geldin ' . $user->name . '! Maceraya başlamaya hazırsın!');
    }
} 