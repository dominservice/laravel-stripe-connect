<?php


namespace Dominservice\LaravelStripeConnect;

use Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe;
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
    protected static function prepare()
    {
        StripeBase::setApiKey(config('services.stripe.secret'));
    }

    /**
     * @param $user
     * @return Stripe
     */
    protected static function getStripeModel($user): Stripe
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
     * @param $user
     * @param $id_key
     * @param $callback
     * @return Stripe
     */
    protected static function createUser($user, $id_key, $callback): Stripe
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
    public static function transaction($token = null)
    {
        return new Transaction($token);
    }
}
