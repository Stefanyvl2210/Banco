<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransaccionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuenta_id');
            $table->foreign('cuenta_id')
                ->references('id')
                ->on('cuentas')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->integer('num_transaccion')->unique();
            $table->enum('tipo', ["deposito", "retiro", "transferencia"]);
            $table->float('cantidad');
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
        Schema::table('transacciones', function (Blueprint $table) {
            $table->dropForeign(['cuenta_id']);
            $table->dropColumn('cuenta_id');
        });
        Schema::dropIfExists('transacciones');
    }
}
