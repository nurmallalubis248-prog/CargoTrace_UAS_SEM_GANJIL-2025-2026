<?php

namespace App\Services;

/**
 * Gateway pembayaran Midtrans (Snap: QRIS, virtual account, dll.) SUDAH DIHAPUS.
 *
 * Class kosong ini sengaja dibiarkan hanya supaya file tampilan lama yang masih memanggil
 * MidtransService::configured() tidak error. configured() selalu false, jadi tombol bayar yang bergantung padanya
 * tidak tampil. Aman dihapus bersama pemanggilnya di Blade.
 */
class MidtransService
{
    public function configured(): bool
    {
        return false;
    }
}
