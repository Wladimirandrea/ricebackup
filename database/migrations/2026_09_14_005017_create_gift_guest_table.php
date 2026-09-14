<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campo max_selection a regalos (si aún no lo tienes)
        Schema::table('gifts', function (Blueprint $table) {
            $table->integer('max_selection')->default(1)->after('name');
        });

        // Crear la tabla pivote
        Schema::create('gift_guest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_id')->constrained()->onDelete('cascade');
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_guest');
        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn('max_selection');
        });
    }
};