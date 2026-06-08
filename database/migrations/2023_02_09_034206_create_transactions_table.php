<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('source_account_id')
                ->nullable()
                ->constrained('accounts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('destination_account_id')
                ->nullable()
                ->constrained('accounts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->integer('transaction_number')->unique();
            $table->enum('transaction_type', ['deposit', 'withdrawal', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
