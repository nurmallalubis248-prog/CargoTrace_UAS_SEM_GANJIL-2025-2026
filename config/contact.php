<?php

// "Hubungi Admin" di halaman login/daftar. Kosongkan nilai di .env untuk menyembunyikan salurannya.
// WhatsApp: angka saja dengan kode negara, tanpa + dan tanpa 0 di depan (Indonesia: 6281234567890).
return [
    'whatsapp' => preg_replace('/\D+/', '', (string) env('CONTACT_WHATSAPP', '')),
    'whatsapp_text' => env('CONTACT_WHATSAPP_TEXT', 'Halo Admin CargoTrace, saya butuh bantuan.'),
    'phone' => env('CONTACT_PHONE'),
    'instagram' => ltrim((string) env('CONTACT_INSTAGRAM', ''), '@'),
    'email' => env('CONTACT_EMAIL'),
];
