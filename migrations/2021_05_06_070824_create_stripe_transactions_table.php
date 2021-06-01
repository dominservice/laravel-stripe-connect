<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('stripe_transactions')) {
            Schema::create('stripe_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_stripe_id')->nullable();
                $table->foreign('customer_stripe_id')->references('id')->on('stripes')->onDelete('set null');
                $table->unsignedBigInteger('vendor_stripe_id')->nullable();
                $table->foreign('vendor_stripe_id')->references('id')->on('stripes')->onDelete('set null');
                $table->double('amount', 50, 25)->nullable();
                $table->double('vendor_amount', 50, 25)->nullable();
                $table->double('fee_amount', 50, 25)->nullable();
                $table->string('stripe_transaction_id')->nullable();
                $table->string('stripe_checkout_id')->nullable();
                $table->string('reference_number');
                $table->string('currency', 3);
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();
                $table->softDeletes();
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
        Schema::dropIfExists('stripe_transactions');
    }
}
