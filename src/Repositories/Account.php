<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


use \Dominservice\LaravelStripeConnect\StripeConnect;
use Stripe\Account as StripeAccount;

class Account extends StripeConnect
{
    public static function index()
    {

    }
    public static function get()
    {

    }

    /**
     * @param $to
     * @param array $params
     * @param false $company
     * @return \Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe
     */
    public static function create($to, $params = [], $company = false)
    {
        $country =  !empty($company->country) ? $company->country : ($to->country ?? null);
        $countryData = (new \Dominservice\DataLocaleParser\DataParser)->parseAllDataPerCountry('en', $country);
        $capabilities = [
            'card_payments' => ['requested' => true],
//            'card_issuing' => ['requested' => true],
        ];
        $xtendParams = [
            'country' => $country,
            'business_type' => $company ? 'company' : 'individual',
            "type" => "express", // custom | express | standard
            "email" => $to->email,
        ];

        if (in_array($countryData->so, ['AU', 'CA', 'NZ', 'UK', 'US'])) {
            $capabilities['afterpay_clearpay_payments'] = ['requested' => true];
        }
        if (in_array($countryData->so, ['MY', 'SG'])) {
            $capabilities['grabpay_payments'] = ['requested' => true];
        }
        if ($countryData->so === 'MX') {
            $capabilities['oxxo_payments'] = ['requested' => true];
        }
        if ($countryData->so === 'CA') {
            $capabilities['acss_debit_payments'] = ['requested' => true];
        }
        if ($countryData->so === 'AU') {
            $capabilities['au_becs_debit_payments'] = ['requested' => true];
        }
        if ($countryData->so === 'MY') {
            $capabilities['fpx_payments'] = ['requested' => true];
        }
        if ($countryData->so === 'JP') {
            $capabilities['jcb_payments'] = ['requested' => true];
        }

        if ($countryData->continent === 'EU') {
            $capabilities = array_merge($capabilities, [
                'sepa_debit_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ]);
            if ($countryData->so === 'FR') {
                $capabilities['cartes_bancaires_payments'] = ['requested' => true];
            }
            if ($countryData->so === 'UK') {
                $capabilities['bacs_debit_payments'] = ['requested' => true];
            }
            if ($countryData->so === 'BE') {
                $capabilities['bancontact_payments'] = ['requested' => true];
            }
            if ($countryData->so === 'AT') {
                $capabilities['eps_payments'] = ['requested' => true];
            }
            if ($countryData->so === 'DE') {
                $capabilities['giropay_payments'] = ['requested' => true];
            }
            if ($countryData->so === 'NL') {
                $capabilities['ideal_payments'] = ['requested' => true];
            }
            if ($countryData->so === 'PL') {
                $capabilities['p24_payments'] = ['requested' => true];
            }
            if (in_array($countryData->so, ['AT', 'BE', 'DE', 'IT', 'NL', 'ES'])) {
                $capabilities['sofort_payments'] = ['requested' => true];
            }
        }
        if (in_array($countryData->so, ['US', 'CA', 'JP'])) {
            $capabilities['transfers'] = ['requested' => true];
        }

        $xtendParams['capabilities'] = $capabilities;

        if ($xtendParams['business_type'] === 'company') {
            $xtendParams['company'] = [
                'address' => [
                    'city' => $company->city ?? null,
                    'country' => $company->country ?? null,
                    'line1' => $company->address ?? null,
                    'line2' => $company->address_2 ?? null,
                    'postal_code' => $company->postcode ?? null,
                    'state' => $company->state ?? null,
                ],
                'name' => $company->name ?? null,
                'phone' => $company->phone ?? null,
                'tax_id' => ($company->tax_no_prefix ?? null) . ($company->tax_no ?? null),
            ];
        } else {
            $xtendParams['individual'] = [
                'email' => $to->email,
                'first_name' => $to->first_name ?? null,
                'last_name' => $to->last_name ?? null,
                'phone' => $to->phone ?? null,
            ];

        }

        $params = array_merge($xtendParams, $params);

        return self::createUser($to, 'account_id', function () use ($params) {
            return StripeAccount::create($params);
        });
    }

    public static function update($to, $params = [])
    {
        return self::createUser($to, 'account_id', function () use ($params) {
            return StripeAccount::update($params);
        });
    }

    public static function delete()
    {
//  curl https://api.stripe.com/v1/accounts/acct_1Il6GJ2SLlYTiKbx \
//  -u sk_test_51IkjjWEO2T8U5qyEDZdNiR87nIpmgVoa9sqM1YIxT77tOMIJBghmYmjeMX6Xd7RrzArwcS2jxnueYVNFQipUqBHC001aMSr6jY: \
//  -X DELETE
    }

    /**
     * @param $to
     * @return object
     */
    public static function accountLink($to): object
    {
        self::prepare();
        $account = self::getStripeModel($to);
        return \Stripe\AccountLink::create([
            'account' => $account->account_id,
            'refresh_url' => url('reauth'),
            'return_url' => url('return'),
            'type' => 'account_onboarding',
        ]);
    }

    /**
     * @param $redirect
     * @return object
     */
    public static function loginLink($to, $redirect): object
    {
        self::prepare();
        $account = self::getStripeModel($to);

        return StripeAccount::createLoginLink($account->account_id, [
            'redirect_url' => $redirect,
        ]);
    }
}
