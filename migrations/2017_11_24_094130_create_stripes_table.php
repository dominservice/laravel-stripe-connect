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
        if (!Schema::hasTable('stripes')) {
            Schema::create('stripes', function (Blueprint $table) {
                $table->id();
                $table->string('vendor_id')->nullable();
                $table->string('customer_id')->nullable();
                $table->bigInteger('user_id')->unsigned()->index();
                $table->unsignedTinyInteger('has_person')->nullable();
                $table->unsignedTinyInteger('has_bank_account')->nullable();
                $table->unsignedTinyInteger('has_payment_card')->nullable();
                $table->unsignedTinyInteger('has_agreement_acceptance')->nullable();
                $table->foreign('user_id')->references('id')->on((new config('services.stripe.model'))->getTable());
                $table->string('type_account')->nullable(); // custom, standard, express
                $table->timestamps();
            });
        }
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
