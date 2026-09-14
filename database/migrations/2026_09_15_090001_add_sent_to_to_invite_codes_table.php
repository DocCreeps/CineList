<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invite_codes', function (Blueprint $table) {
            // Adresse e-mail à laquelle le code a été envoyé (facultatif : un code
            // peut aussi être généré puis partagé manuellement, sans envoi de mail).
            $table->string('sent_to')->nullable()->after('code');
            $table->foreignId('created_by')->nullable()->after('sent_to')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invite_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('sent_to');
        });
    }
};
