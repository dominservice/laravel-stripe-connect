<?php


namespace Dominservice\LaravelStripeConnect;

use Dominservice\LaravelStripeConnect\Repositories\Account;
use Dominservice\LaravelStripeConnect\Repositories\Customer;
use Stripe\Account as StripeAccount;
use Stripe\Charge;


/**
 * Class Transaction
 * @package Dominservice\LaravelStripeConnect
 */
class Transaction
{
    /**
     * @var
     */
    private $from, $to, $value, $currency, $to_params, $token, $fee, $from_params, $saved_customer;

    private $zeroDecimalCurrencies = [
        'BIF',
        'CLP',
        'DJF',
        'GNF',
        'JPY',
        'KMF',
        'KRW',
        'MGA',
        'PYG',
        'RWF',
        'UGX',
        'VND',
        'VUV',
        'XAF',
        'XOF',
        'XPF',
    ];

    /**
     * Transaction constructor.
     * @param null $token
     */
    public function __construct($token = null)
    {
        $this->token = $token;
    }

    /**
     * Set the Customer.
     *
     * @param $user
     * @param array $params
     * @return $this
     */
    public function from($user, $params = [])
    {
        $this->from = $user;
        $this->from_params = $params;
        return $this;
    }

    /**
     * @return $this
     */
    public function useSavedCustomer()
    {
        $this->saved_customer = true;
        return $this;
    }

    /**
     * Set the Vendor.
     *
     * @param $user
     * @param array $params
     * @param false|object $company
     * @return $this
     */
    public function to($user, $params = [], $company = false)
    {
        $this->to = $user;
        $this->to_params = $params;
        $this->to_company = $company;
        return $this;
    }

    /**
     * The amount of the transaction.
     *
     * @param $value
     * @param $currency
     * @return $this
     */
    public function amount($value, $currency, $fee = null, $feeIsPercent = false)
    {
        $this->currency = $currency;
        $this->value = $this->validAmount($amount);
        $this->fee = $this->validAmount($fee, null, $feeIsPercent ? $this->value : null);
        return $this;
    }

    /**
     * Create the transaction: charge customer and credit vendor.
     * This function saves the two accounts.
     *
     * @param array $params
     * @return Charge
     */
    public function create($params = [], $type = '')
    {
        // Prepare vendor
        $vendor = Account::create($this->to, $this->to_params, $this->to_company);
        // Prepare customer
        if ($this->saved_customer) {
            $customer = Customer::createOrUpdate($this->token, $this->from, $this->from_params);
            $params["customer"] = $customer->customer_id;
        } else {
            $params["source"] = $this->token;
        }


//        $paymentIntent = \Stripe\PaymentIntent::create([
//            'amount' => $this->value,
//            'currency' => $this->currency,
//            'payment_method_types' => ['p24'],
//            'transfer_group' => 'ORDER10',
//        ]);
//
//// Create a Transfer to a connected account (later):
//        $transfer = \Stripe\Transfer::create([
//            'amount' => $this->value,
//            'currency' => $this->currency,
//            'destination' => $vendor->vendor_id,
//            'transfer_group' => 'ORDER10',
//        ]);
//
//// Create a second Transfer to another connected account (later):
////        $transfer2 = \Stripe\Transfer::create([
////            'amount' => 2000,
////            'currency' => 'pln',
////            'destination' => '{{OTHER_CONNECTED_STRIPE_vendor_id}}',
////            'transfer_group' => '{ORDER10}',
////        ]);
//
//        dd($paymentIntent
//        , $vendor
//        , $customer
//            , $transfer
//        );

        $charge = Charge::create(array_merge([
            'description' =>  'level.name',
            'amount' => $this->value,
            'currency' => $this->currency,
            'transfer_data' => ['destination' => $vendor->vendor_id],
            "application_fee_amount" => $this->fee ?? null,

        ], $params));

        dd($charge);


        return Charge::create(array_merge([
            'payment_method_types' => ['p24'],
            'amount' => $this->value,
            'currency' => $this->currency,
            'transfer_data' => [
                'destination' => $vendor->vendor_id,
            ],

            "application_fee_amount" => $this->fee ?? null,
        ], $params));
    }

    private function validAmount($amount = null, $currency = null, $valueForPercent = null)
    {
        $amount = $amount ?? 0;
        if ($isPercent && $valueForPercent) {
            $amount = ($valueForPercent / 100) * $amount;
        }

        $amount = in_array(($currency ?? $this->currency), $this->zeroDecimalCurrencies) ? $amount : $amount*100;
        return in_array(($currency ?? $this->currency), $this->zeroDecimalCurrencies) ? $amount : $amount*100;
    }
}
