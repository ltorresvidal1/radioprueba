<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ris_relplantillasradiologos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('plantilla_id');
            $table->foreignUuid('medico_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ris_relplantillasradiologos');
    }
};
