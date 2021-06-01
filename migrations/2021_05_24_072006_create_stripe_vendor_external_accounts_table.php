<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeVendorExternalAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('stripe_vendor_external_accounts')) {
            Schema::create('stripe_vendor_external_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_stripe_id');
                $table->foreign('vendor_stripe_id')->references('id')->on('stripes')->onDelete('cascade');
                $table->string('external_id');
                $table->unsignedTinyInteger('default_for_currency')->nullable();
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
        Schema::dropIfExists('stripe_vendor_external_accounts');
    }
}
