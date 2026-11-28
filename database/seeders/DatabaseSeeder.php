<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds ONLY the demo accounts (admin, operator, two customers).
 * No carriers, locations, shipments, tracking events or exceptions are created:
 * add your own master data and shipments through the application (Admin menu / API).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->user('Admin CargoTrace', 'admin@cargotrace.test', User::ROLE_ADMIN);
        $this->user('Logistics Operator', 'operator@cargotrace.test', User::ROLE_OPERATOR);
        $this->user('PT Nusantara Export', 'customer1@cargotrace.test', User::ROLE_CUSTOMER);
        $this->user('Samudra Trading Co.', 'customer2@cargotrace.test', User::ROLE_CUSTOMER);
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            return $user;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => 'password']);
        $user->role = $role; // role is not mass-assignable
        $user->email_verified_at = now(); // demo accounts are pre-verified
        $user->save();

        return $user;
    }
}