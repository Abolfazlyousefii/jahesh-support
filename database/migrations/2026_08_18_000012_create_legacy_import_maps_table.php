<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source', 80);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('legacy_id');
            $table->unsignedBigInteger('current_id');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['source', 'entity_type', 'legacy_id'], 'legacy_import_unique');
            $table->index(['entity_type', 'current_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_maps');
    }
};
