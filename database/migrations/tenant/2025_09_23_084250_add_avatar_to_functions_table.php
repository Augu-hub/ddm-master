<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('tenant')->table('functions', function (Blueprint $t) {
            $t->string('avatar_path')->nullable()->after('character');
            $t->index('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('functions', function (Blueprint $t) {
            $t->dropIndex(['avatar_path']);
            $t->dropColumn('avatar_path');
        });
    }
};
