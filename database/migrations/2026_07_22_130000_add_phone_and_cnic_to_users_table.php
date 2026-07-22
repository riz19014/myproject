<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 11)->nullable()->after('email');
            $table->string('cnic', 14)->nullable()->after('phone');
            $table->enum('type', ['accountant', 'user', 'admin', 'owner', 'super-admin'])
                ->default('user')
                ->after('cnic');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'cnic', 'type']);
        });
    }
};
