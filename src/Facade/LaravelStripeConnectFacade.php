<?php

/**
 * Laravel Stripe Connect
 *
 * This package will allow you to add a full user messaging system
 * into your Laravel application.
 *
 * @package   Dominservice\LaravelStripeConnect
 * @author    DSO-IT Mateusz Domin <biuro@dso.biz.pl>
 * @copyright (c) 2021 DSO-IT Mateusz Domin
 * @license   MIT
 * @version   1.0.0
 */

namespace Dominservice\LaravelStripeConnect\Facade;


use Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe;
use Dominservice\LaravelStripeConnect\Transaction;
use Illuminate\Support\Facades\Facade;

/**
 * Class LaravelStripeConnect
 * @package Dominservice\LaravelStripeConnect\Facade
 *
 * @method static prepare()
 * @method static Stripe getStripeModel($user)
 * @method static Stripe createAccount($to, $params = [])
 * @method static Stripe createCustomer($token, $from, $params = [])
 * @method static Stripe createOrUpdateCustomer($token, $from, $params = [])
 * @method static Stripe create($user, $id_key, $callback)
 * @method static Transaction transaction($token = null)
 */
class LaravelStripeConnectFacade extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return \Dominservice\LaravelStripeConnect\StripeConnect::class;
    }

}
