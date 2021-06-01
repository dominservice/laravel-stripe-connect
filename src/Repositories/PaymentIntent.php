<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


class PaymentIntent extends \Dominservice\LaravelStripeConnect\StripeConnect
{
    public static function create($params)
    {
        self::prepare();
        return \Stripe\PaymentIntent::create($params);
    }

    public static function update($params)
    {
        self::prepare();
        return \Stripe\PaymentIntent::update($params);
    }

    public static function get($transactionId)
    {
        self::prepare();
        return \Stripe\PaymentIntent::retrieve($transactionId);
    }

    public static function confirm($transactionId)
    {
        self::prepare();
        $payment = new \Stripe\PaymentIntent($transactionId);
        return $payment->capture();
    }

    public static function capture($transactionId)
    {
        self::prepare();
        $payment = new \Stripe\PaymentIntent($transactionId);
        return $payment->capture();
    }

    public static function cancel($transactionId, $cancellationReason = null)
    {
        self::prepare();
        $payment = new \Stripe\PaymentIntent($transactionId);
        return $payment->cancel();
    }

    public static function list($limit = 10, $transactionId = null, $isPrev = false, $user = null)
    {
        self::prepare();

        $params = ['limit'=>$limit];
        if ($transactionId) {
            if ($isPrev) {
                $params['ending_before'] = $transactionId;
            } else {
                $params['starting_after'] = $transactionId;
            }
        }

        if ($user) {
            $vendor = Account::get($user);
            return \Stripe\PaymentIntent::all($params, ['stripe_account' => $vendor->vendor_id]);
        }

        return \Stripe\PaymentIntent::all($params);
    }
}
