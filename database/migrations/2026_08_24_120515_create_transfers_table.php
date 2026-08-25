<?php

use App\Enums\TransactionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('to_wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('note')->nullable();
            $table->string('status')->default(TransactionStatus::Completed->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
