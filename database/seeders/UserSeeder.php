<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ──────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'name'          => 'Admin Principal',
                'password'      => Hash::make('123456789'),
                'role'          => 'admin',
                'is_active'     => true,
                'created_by'    => null,
                'profile_image' => 'profile-images/admin.png',
            ]
        );

        // ── Case Manager 1 ───────────────────────────────────────
        $manager1 = User::updateOrCreate(
            ['email' => 'donramon@sistema.com'],
            [
                'name'          => 'Don Ramon',
                'password'      => Hash::make('123456789'),
                'role'          => 'case_manager',
                'is_active'     => true,
                'created_by'    => $admin->id,
                'profile_image' => 'profile-images/donramon.png',
            ]
        );

        // ── Clientes de Don Ramon ──────────────────────────────
        User::updateOrCreate(
            ['email' => 'chavo@sistema.com'],
            [
                'name'          => 'chavo del 8',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager1->id,
                'profile_image' => 'profile-images/chavo.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'chilindrina@sistema.com'],
            [
                'name'          => 'chilindrina',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager1->id,
                'profile_image' => 'profile-images/chilindrina.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'quiko@sistema.com'],
            [
                'name'          => 'quiko',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager1->id,
                'profile_image' => 'profile-images/quiko.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bruja@sistema.com'],
            [
                'name'          => 'bruja',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager1->id,
                'profile_image' => 'profile-images/bruja.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'donaflorinda@sistema.com'],
            [
                'name'          => 'donaflorinda',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager1->id,
                'profile_image' => 'profile-images/florinda.png',
            ]
        );

        // ── Case Manager 2 ───────────────────────────────────────
        $manager2 = User::updateOrCreate(
            ['email' => 'homero@sistema.com'],
            [
                'name'          => 'Homero Simpson',
                'password'      => Hash::make('123456789'),
                'role'          => 'case_manager',
                'is_active'     => true,
                'created_by'    => $admin->id,
                'profile_image' => 'profile-images/homero.png',
            ]
        );

        // ── Clientes de Homero ─────────────────────────────────
        User::updateOrCreate(
            ['email' => 'lisa@sistema.com'],
            [
                'name'          => 'lisa simpson',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager2->id,
                'profile_image' => 'profile-images/lisa.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bart@sistema.com'],
            [
                'name'          => 'bart simpson',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager2->id,
                'profile_image' => 'profile-images/bart.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'marge@sistema.com'],
            [
                'name'          => 'marge simpson',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager2->id,
                'profile_image' => 'profile-images/marge.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'maggie@sistema.com'],
            [
                'name'          => 'maggie simpson',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager2->id,
                'profile_image' => 'profile-images/maggie.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'juantopo@sistema.com'],
            [
                'name'          => 'juan Topo',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager2->id,
                'profile_image' => 'profile-images/juan.png',
            ]
        );

        // ── Case Manager 3 ───────────────────────────────────────
        $manager3 = User::updateOrCreate(
            ['email' => 'nedstark@sistema.com'],
            [
                'name'          => 'Ned stark',
                'password'      => Hash::make('123456789'),
                'role'          => 'case_manager',
                'is_active'     => true,
                'created_by'    => $admin->id,
                'profile_image' => 'profile-images/ned.png',
            ]
        );

        // ── Clientes de Ned ────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'arya@sistema.com'],
            [
                'name'          => 'Arya stark',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager3->id,
                'profile_image' => 'profile-images/arya.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'sansa@sistema.com'],
            [
                'name'          => 'sansa stark',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager3->id,
                'profile_image' => 'profile-images/sansa.png',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bran@sistema.com'],
            [
                'name'          => 'bran stark',
                'password'      => Hash::make('123456789'),
                'role'          => 'client',
                'is_active'     => true,
                'created_by'    => $manager3->id,
                'profile_image' => 'profile-images/bran.png',
            ]
        );
    }
}