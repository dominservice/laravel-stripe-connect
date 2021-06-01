<?php


namespace Dominservice\LaravelStripeConnect\Models\Eloquent;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

class StripeTransaction extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    public function vendor()
    {
        return $this->hasOne(Stripe::class, 'id', 'vendor_stripe_id');
    }

    public function customer()
    {
        return $this->hasOne(Stripe::class, 'id', 'customer_stripe_id');
    }
}
