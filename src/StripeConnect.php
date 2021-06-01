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
    public static function prepare()
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
    protected static function createUser($user, $id_key, $typeAccount = null, $callback = null): Stripe
    {
        self::prepare();
        $userForStripe = self::getStripeModel($user);
        if (!$userForStripe->$id_key) {
            if ($callback) {
                $userForStripe->$id_key = call_user_func($callback)->id;
            }
            $userForStripe->type_account = $typeAccount;
            $userForStripe->save();
        }
        return $userForStripe;
    }

    /**
     * @param null $token
     * @return Transaction
     */
    public static function transaction($token = null)
    {
        return new Transaction($token);
    }

    /**
     * @param null $token
     * @return Checkout
     */
    public static function checkout($token = null)
    {
        return new Checkout($token);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $valueForPercent
     * @return float|int
     */
    public static function validAmount($amount, $currency, $percent = false)
    {
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',];
        if ($percent) {
            $amount = round(($amount / 100) * $percent);
        }

        return in_array($currency, $zeroDecimalCurrencies) || $percent ? (int)$amount : (round($amount, 2)*100);
    }

    public static function setPaymentMethods($country)
    {
        $countryData = (new \Dominservice\DataLocaleParser\DataParser)->parseAllDataPerCountry('en', $country);
        $paymentMethods = ['card'];

        if (!empty($countryData->so)) {
            if (in_array($countryData->so, ['AU', 'CA', 'NZ', 'UK', 'US'])) {
                $paymentMethods[] = 'afterpay_clearpay';
            }
            if (in_array($countryData->so, ['MY', 'SG'])) {
                $paymentMethods[] = 'grabpay';
            }
//        if ($countryData->so === 'MX') {
//            $paymentMethods[] = 'oxxo';
//        }
            if ($countryData->so === 'CA') {
                $paymentMethods[] = 'acss_debit';
            }
//        if ($countryData->so === 'AU') {
//            $paymentMethods[] = 'au_becs_debit';
//        }
            if ($countryData->so === 'MY') {
                $paymentMethods[] = 'fpx';
            }
//        if ($countryData->so === 'JP') {
//            $paymentMethods[] = 'jcb';
//        }

            if ($countryData->continent === 'EU') {
                if($countryData->currency->code === 'EUR') {
                    $paymentMethods[] = 'sepa_debit';
                }
//            if ($countryData->so === 'FR') {
//                $this->paymentMethods[] = 'cartes_bancaires';
//            }
                if ($countryData->so === 'UK') {
                    $paymentMethods[] = 'bacs_debit';
                }
                if ($countryData->so === 'BE') {
                    $paymentMethods[] = 'bancontact';
                }
                if ($countryData->so === 'AT') {
                    $paymentMethods[] = 'eps';
                }
                if ($countryData->so === 'DE') {
                    $paymentMethods[] = 'giropay';
                }
                if ($countryData->so === 'NL') {
                    $paymentMethods[] = 'ideal';
                }
                if ($countryData->so === 'PL') {
                    $paymentMethods[] = 'p24';
                }
                if (in_array($countryData->so, ['AT', 'BE', 'DE', 'IT', 'NL', 'ES'])) {
                    $paymentMethods[] = 'sofort';
                }
            }
//        if (in_array($countryData->so, ['US', 'CA', 'JP'])) {
//            $paymentMethods[] = 'transfers';
//        }
        }

        return $paymentMethods;
    }
}
