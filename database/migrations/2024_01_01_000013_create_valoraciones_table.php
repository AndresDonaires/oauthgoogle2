<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valoraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones');
            $table->foreignId('mentor_id')->constrained('usuarios');
            $table->foreignId('aprendiz_id')->constrained('usuarios');
            $table->unsignedTinyInteger('calificacion'); // 1–5
            $table->string('comentario', 500)->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valoraciones');
    }
};
