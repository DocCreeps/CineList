<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applique la migration.
     */
    public function up(): void
    {
        Schema::create('invite_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('invite_codes');
    }
};
