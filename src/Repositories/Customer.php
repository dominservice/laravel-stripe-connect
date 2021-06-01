<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


use Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe;

class Customer extends \Dominservice\LaravelStripeConnect\StripeConnect
{
    public static function index()
    {

    }
    public static function get()
    {

    }

    /**
     * @param $token
     * @param $from
     * @param array $params
     * @return Stripe
     */
    public static function create($from, $token = null, $params = []): Stripe
    {
        $params = array_merge([
            "email" => $from->email,
            'source' => $token,
        ], $params);
        return self::createUser($from, 'customer_id', 'customer', function () use ($params) {
            return \Stripe\Customer::create($params);
        });
    }

    /**
     * @param $token
     * @param $from
     * @param array $params
     * @return Stripe
     */
    public static function createOrUpdate($from, $token = null, $params = []): Stripe
    {
        self::prepare();
        $user = self::getStripeModel($from);
        if (!$user) {
            return self::create($from, $token, $params);
        }
        if ($token) {
            $customer = \Stripe\Customer::retrieve($user->customer_id);
            $customer->source = $token;
            $customer->save();
        }
        return $user;
    }

    public static function update($to, $params = [])
    {

    }

    public static function delete()
    {

    }
}
