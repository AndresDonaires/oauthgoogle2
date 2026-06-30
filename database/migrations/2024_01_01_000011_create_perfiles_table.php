<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('bio', 500)->nullable();
            $table->string('carrera', 100)->nullable();
            $table->unsignedTinyInteger('ciclo')->nullable();
            $table->string('habilidades', 500)->nullable();
            $table->string('disponibilidad', 255)->nullable();
            $table->string('foto_url', 255)->nullable();
            $table->timestamp('fecha_actualizacion')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
