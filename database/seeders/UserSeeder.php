<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ──────────────────────────────────────────────
        $admin = User::create([
            'name'       => 'Admin Principal',
            'email'      => 'admin@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'admin',
            'is_active'  => true,
            'created_by' => null,
            'profile_image' => 'profile-images/admin.png',
        ]);

        // ── Case Manager 1 ───────────────────────────────────────
        $manager = User::create([
            'name'       => 'Don Ramon',
            'email'      => 'donramon@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'case_manager',
            'is_active'  => true,
            'created_by' => $admin->id,
            'profile_image' => 'profile-images/donramon.png',
        ]);

        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'chavo del 8',
            'email'      => 'chavo@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/chavo.png',
        ]);

        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'chilindrina',
            'email'      => 'chilindrina@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/chilindrina.png',
        ]);

        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'quiko',
            'email'      => 'quiko@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/quiko.png',
        ]);
        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'bruja',
            'email'      => 'bruja@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/bruja.png',
        ]);
        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'donaflorinda',
            'email'      => 'donaflorinda@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/florinda.png',
        ]);


        // ── Case Manager 2 ───────────────────────────────────────
        $manager = User::create([
            'name'       => 'Homero Simpson',
            'email'      => 'homero@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'case_manager',
            'is_active'  => true,
            'created_by' => $admin->id,
            'profile_image' => 'profile-images/homero.png',
        ]);

        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'lisa simpson',
            'email'      => 'lisa@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/lisa.png',
        ]);
        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'bart simpson',
            'email'      => 'bart@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/bart.png',
        ]);

         User::create([
            'name'       => 'marge simpson',
            'email'      => 'marge@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/marge.png',
        ]);
         User::create([
            'name'       => 'maggie simpson',
            'email'      => 'maggie@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/maggie.png',
        ]);
         User::create([
            'name'       => 'juan Topo',
            'email'      => 'juantopo@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/juan.png',
        ]);


        // ── Case Manager 3 ───────────────────────────────────────
        $manager = User::create([
            'name'       => 'Ned stark',
            'email'      => 'nedstark@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'case_manager',
            'is_active'  => true,
            'created_by' => $admin->id,
            'profile_image' => 'profile-images/ned.png',
        ]);

        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'Arya stark',
            'email'      => 'arya@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/arya.png',
        ]);
        // ── Cliente ────────────────────────────────────────────
        User::create([
            'name'       => 'sansa stark',
            'email'      => 'sansa@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/sansa.png',
        ]);

         User::create([
            'name'       => 'bran stark',
            'email'      => 'bran@sistema.com',
            'password'   => bcrypt('123456789'),
            'role'       => 'client',
            'is_active'  => true,
            'created_by' => $manager->id,
            'profile_image' => 'profile-images/bran.png',
        ]);
         











    }
}
