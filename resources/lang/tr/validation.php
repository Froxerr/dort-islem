<?php

return [
    'required' => ':attribute alanı zorunludur.',
    'email' => ':attribute alanı geçerli bir e-posta adresi olmalıdır.',
    'unique' => ':attribute alanı daha önceden kayıt edilmiş.',
    'min' => [
        'string' => ':attribute alanı en az :min karakter olmalıdır.',
    ],
    'max' => [
        'string' => ':attribute alanı en fazla :max karakter olmalıdır.',
    ],
    'confirmed' => ':attribute alanı doğrulama ile eşleşmiyor.',
    'string' => ':attribute alanı metin olmalıdır.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    'attributes' => [
        'name' => 'Kaşif Adı',
        'email' => 'E-posta',
        'password' => 'Şifre',
    ],
]; 