<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('provider', 40)->default('melipayamak');
            $table->string('webservice_username', 150)->nullable();
            $table->text('webservice_password')->nullable();
            $table->json('internal_recipient_user_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('title', 180);
            $table->unsignedBigInteger('body_id')->nullable();
            $table->boolean('enabled')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient', 32)->index();
            $table->string('pattern_key', 80)->index();
            $table->string('related_type', 180)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 20)->index();
            $table->string('provider_message_id', 100)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_patterns');
        Schema::dropIfExists('sms_settings');
    }
};
