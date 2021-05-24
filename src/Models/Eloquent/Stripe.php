<?php
namespace Dominservice\LaravelStripeConnect\Models\Eloquent;

use Dominservice\LaravelStripeConnect\Repositories\Account;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stripe
 * @package Dominservice\LaravelStripeConnect
 */
class Stripe extends Model
{
    protected $casts = [
        'has_agreement_acceptance' => 'bool',
        'has_person' => 'bool',
        'has_bank_account' => 'bool',
        'has_payment_card' => 'bool',
    ];

    public function vendorExternalAccounts()
    {
        return $this->hasMany(StripeVendorExternalAccount::class, 'vendor_stripe_id');
    }

    public function vendorTransactions()
    {
        return $this->hasMany(StripeTransaction::class, 'account_stripe_id');
    }

    public function customerTransactions()
    {
        return $this->hasMany(StripeTransaction::class, 'customer_stripe_id');
    }

    public function isCustomer()
    {
        return !is_null($this->cusromer_id);
    }

    public function isVendor()
    {
        return !is_null($this->vendor_id);
    }

    public function vendorHasFullData()
    {
        return $this->isVendor()
            && $this->has_agreement_acceptance
            && $this->has_person
            && ($this->has_bank_account || $this->has_payment_card);
    }

    public function getAccountLinc()
    {
        if ($accountLink = Account::accountLinkFromStripeModel($this->vendor_id, route('account.stripeReturn'), route('account.stripeRefresh'))) {
            $accountLink = $accountLink->url;
        }

        return $accountLink;
    }

    public function accountSetAllComplete()
    {
        $this->has_person = 1;
        $this->has_bank_account = 1;
        $this->has_agreement_acceptance = 1;
        $this->save();
    }
}
