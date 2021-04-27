<?php


namespace Dominservice\LaravelStripeConnect;

use Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe;
use Stripe\Account as StripeAccount;
use Stripe\Customer;
use Stripe\Stripe as StripeBase;


/**
 * Class StripeConnect
 * @package Dominservice\LaravelStripeConnect
 */
class StripeConnect
{

    /**
     *
     */
    private static function prepare()
    {
        StripeBase::setApiKey(config('services.stripe.secret'));
    }

    /**
     * @param $user
     * @return Stripe
     */
    private static function getStripeModel($user): Stripe
    {
        $stripe = Stripe::where('user_id', $user->id)->first();
        if (!$stripe) {
            $stripe = new Stripe();
            $stripe->user_id = $user->id;
            $stripe->save();
        }
        return $stripe;
    }

    /**
     * @param $to
     * @param array $params
     * @return Stripe
     */
    public static function createAccount($to, $params = []): Stripe
    {
        $params = array_merge([
            "type" => "custom",
            "email" => $to->email,
        ], $params);
        return self::create($to, 'account_id', function () use ($params) {
            return StripeAccount::create($params);
        });
    }

    /**
     * @param $token
     * @param $from
     * @param array $params
     * @return Stripe
     */
    public static function createCustomer($token, $from, $params = []): Stripe
    {
        $params = array_merge([
            "email" => $from->email,
            'source' => $token,
        ], $params);
        return self::create($from, 'customer_id', function () use ($params) {
            return Customer::create($params);
        });
    }

    /**
     * @param $token
     * @param $from
     * @param array $params
     * @return Stripe
     */
    public function createOrUpdateCustomer($token, $from, $params = []): Stripe
    {
        self::prepare();
        $user = self::getStripeModel($from);
        if (!$user) {
            return self::createCustomer($token, $from, $params);
        }
        $customer = Customer::retrieve($token->customer_id);
        $customer->source = $token;
        $customer->save();
        return $user;
    }

    /**
     * @param $user
     * @param $id_key
     * @param $callback
     * @return Stripe
     */
    private static function create($user, $id_key, $callback): Stripe
    {
        self::prepare();
        $user = self::getStripeModel($user);
        if (!$user->$id_key) {
            $user->$id_key = call_user_func($callback)->id;
            $user->save();
        }
        return $user;
    }

    /**
     * @param null $token
     * @return Transaction
     */
    public static function transaction($token = null): Transaction
    {
        return new Transaction($token);
    }
}
