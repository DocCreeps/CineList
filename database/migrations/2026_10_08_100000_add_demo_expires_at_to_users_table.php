<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Comptes démo : le « modèle » (is_demo, sans expiration) sert de base à des bacs à sable
            // jetables (is_demo + demo_expires_at) créés à chaque visite d'un lien démo.
            $table->timestamp('demo_expires_at')->nullable()->after('is_demo')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('demo_expires_at');
        });
    }
};
