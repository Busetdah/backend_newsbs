<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stitches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('good')->default(0);
            $table->unsignedInteger('bad')->default(0);
            // Menyimpan timestamp event untuk frontend (entry.time)
            $table->timestamp('time')->useCurrent();
            // created_at & updated_at standar Laravel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stitches');
    }
};
