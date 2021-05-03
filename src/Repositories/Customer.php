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
    public static function create($token, $from, $params = []): Stripe
    {
        $params = array_merge([
            "email" => $from->email,
            'source' => $token,
        ], $params);
        return self::createUser($from, 'customer_id', function () use ($params) {
            return \Stripe\Customer::create($params);
        });
    }

    /**
     * @param $token
     * @param $from
     * @param array $params
     * @return Stripe
     */
    public function createOrUpdate($token, $from, $params = []): Stripe
    {
        self::prepare();
        $user = self::getStripeModel($from);
        if (!$user) {
            return self::create($token, $from, $params);
        }
        $customer = \Stripe\Customer::retrieve($token->customer_id);
        $customer->source = $token;
        $customer->save();
        return $user;
    }

    public static function update($to, $params = [])
    {
      
    }

    public static function delete()
    {
        
    }
}
