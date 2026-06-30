<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('google_id')->nullable();
            $table->unsignedTinyInteger('rol')->default(1); // 1=estudiante, 2=mentor, 3=admin
            $table->string('estado')->default('activo');   // activo | inactivo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
