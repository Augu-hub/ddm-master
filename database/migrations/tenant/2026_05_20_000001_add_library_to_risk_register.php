<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('risk_register', function (Blueprint $table) {
            $table->timestamp('moved_to_library_at')->nullable()->after('incident_id')
                ->comment('Date de transfert en bibliothèque — null = non transféré');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('risk_register', function (Blueprint $table) {
            $table->dropColumn('moved_to_library_at');
        });
    }
};
