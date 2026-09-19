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
            $table->string('verified_phone', 32)->nullable()->after('verify_provider');
        });

        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('phone', 32)->index();
            $table->string('session_id')->unique();
            $table->string('code', 16);
            $table->string('callback_token', 64)->nullable()->unique();
            $table->string('sms_uri', 255)->nullable();
            $table->text('display_instruction')->nullable();
            $table->string('status', 16)->default('PENDING')->index();
            $table->string('callback_status', 16)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'verified_at', 'verify_provider', 'verified_phone']);
        });
    }
};
