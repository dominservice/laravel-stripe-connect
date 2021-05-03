<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStripesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripes', function (Blueprint $table) {
            $table->increments('id');

            $table->string('account_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->unsignedTinyInteger('has_person')->nullable();
            $table->unsignedTinyInteger('has_bank_account')->nullable();
            $table->unsignedTinyInteger('has_payment_card')->nullable();

            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('customer_id')->nullable();

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
        Schema::dropIfExists('stripes');
    }
}
