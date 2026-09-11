<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_active')->index();
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->string('verify_provider', 32)->nullable()->after('verified_at');
            $table->string('verify_subject')->nullable()->after('verify_provider');
            $table->string('register_number', 16)->nullable()->after('verify_subject');
            $table->string('last_name')->nullable()->after('register_number');
            $table->string('first_name')->nullable()->after('last_name');
            $table->json('verify_data')->nullable()->after('first_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'verified_at', 'verify_provider', 'verify_subject', 'register_number', 'last_name', 'first_name', 'verify_data']);
        });
    }
};
