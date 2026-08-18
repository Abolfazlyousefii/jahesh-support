<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 100);
            $table->string('account_holder', 150);
            $table->string('card_number', 32)->nullable();
            $table->string('iban', 40)->nullable();
            $table->string('account_number', 64)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('customer_payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('financial_bank_accounts')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('paid_at');
            $table->string('tracking_code', 100)->nullable();
            $table->string('receipt_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->text('customer_note')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status', 'created_at']);
            $table->index(['paid_at', 'status']);
        });

        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->index();
            $table->unsignedBigInteger('amount');
            $table->string('description', 500);
            $table->string('reference', 150)->nullable();
            $table->date('entry_date')->index();
            $table->string('source', 40)->default('manual')->index();
            $table->foreignId('payment_receipt_id')->nullable()->unique()->constrained('customer_payment_receipts')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->index();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'entry_date', 'id']);
            $table->index(['customer_id', 'voided_at', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('customer_payment_receipts');
        Schema::dropIfExists('financial_bank_accounts');
    }
};
