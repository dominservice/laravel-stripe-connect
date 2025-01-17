<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


use App\Models\User;
use Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe;
use Dominservice\LaravelStripeConnect\StripeConnect;
use GuzzleHttp\Client;
use Stripe\Account as StripeAccount;

class Account extends StripeConnect
{
    /**
     * @return Stripe[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function index()
    {
        return Stripe::all();
    }

    /**
     * @param $to
     * @return Stripe|null
     */
    public static function get($to)
    {
        self::prepare();
        $account = self::getStripeModel($to);

        return $account->vendor_id !== null ? $account : null;
    }

    /**
     * @param $to
     * @param array $params
     * @param false $company
     * @return \Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe
     */
    public static function create($to, $params = [], $company = false)
    {
        if (!$account = self::get($to)) {
            $typeAccount = !empty($params['type']) && in_array($params['type'], [StripeAccount::TYPE_CUSTOM, StripeAccount::TYPE_EXPRESS, StripeAccount::TYPE_STANDARD])
                ? $params['type'] : StripeAccount::TYPE_CUSTOM;
            return self::createUser($to, 'vendor_id', $typeAccount, function () use ($to, $params, $company, $typeAccount) {
                $country = !empty($company->country) ? $company->country : ($to->country ?? null);
                $xtendParams = [
                    'country' => $country,
                    'business_type' => $company ? StripeAccount::BUSINESS_TYPE_COMPANY : StripeAccount::BUSINESS_TYPE_INDIVIDUAL,
                    "type" => $typeAccount, // custom - TYPE_CUSTOM | express - TYPE_EXPRESS | standard - TYPE_STANDARD
                    "email" => $to->email,
                ];

                if (in_array($typeAccount, [StripeAccount::TYPE_CUSTOM, StripeAccount::TYPE_EXPRESS]) && empty($params['capabilities'])) {
                    $xtendParams['capabilities'] = self::setCapabilities($country);
                }

                if ($xtendParams['business_type'] === StripeAccount::BUSINESS_TYPE_COMPANY) {
                    $xtendParams['business_profile'] = [
                        'url' => $company->url,
                        'mcc' => '5969',
                    ];
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

                $params = array_replace_recursive($xtendParams, $params);

                return StripeAccount::create($params);
            }, $typeAccount);
        }

        return $account;
    }

    /**
     * @param $token
     * @param $from
     * @param array $params
     * @return Stripe
     */
    public function createOrUpdate($to, $params = [], $company = false): Stripe
    {
        if (!$account = self::get($to)) {
            return self::create($to, $params, $company);
        } else {
            return self::update($to, $params);
        }
    }

    public static function update($to, $params = [])
    {
        if ($account = self::getStripeModel($to)) {
            return StripeAccount::update($account->vendor_id, $params);
        }
        return false;
    }

    /**
     * @param $to
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function delete($to)
    {
        if ($account = self::getStripeModel($to)) {
            if ($stripeAccount = self::getStripeAccount($account->vendor_id)) {
                return $stripeAccount->delete() && $account->delete();
            }
        }

        return false;
    }

    /**
     * @param $to
     * @return object
     */
    public static function accountLink($to, $return = null, $reauth = null): object
    {
        return self::accountLinkFromStripeModel(self::getStripeModel($to), $return, $reauth);
    }

    /**
     * @param $to
     * @return object
     */
    public static function accountLinkFromStripeModel($account, $return = null, $reauth = null): object
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;
        return \Stripe\AccountLink::create([
            'account' => $id,
            'refresh_url' => $reauth ?? url('reauth'),
            'return_url' => $return ?? url('return'),
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

        return StripeAccount::createLoginLink($account->vendor_id, [
            'redirect_url' => $redirect,
        ]);
    }

    /**
     * @param User|string $to
     * @return false|StripeAccount
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function getStripeAccount($to)
    {
        self::prepare();

        if (!is_string($to)) {
            if ($to instanceof User && $account = self::getStripeModel($to)) {
                return \Stripe\Account::retrieve($account->vendor_id);
            } elseIf ($to instanceof Stripe) {
                return \Stripe\Account::retrieve($to->vendor_id);
            }
        } else {
            return \Stripe\Account::retrieve($to);
        }

        return false;
    }

    /**
     * @return \Stripe\Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function getAllStripeAccounts()
    {
        self::prepare();

        return \Stripe\Account::all();
    }

    /**
     * @param $account
     * @return \Stripe\Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function getAllStripeExternalAccounts($account)
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;

        return StripeAccount::allExternalAccounts($id);
    }

    public static function stripeServicesAgreementAcceptance($to)
    {
        $response = self::update($to, [
            'tos_acceptance' => [
                'date' => time(),
                'ip' => $_SERVER['REMOTE_ADDR'],
            ],
        ]);

        if ($response && $account = self::get($to)) {
            $account->has_agreement_acceptance = 1;
            $account->save();
        }

        return $response;
    }

    public static function stripeDeauthorize($to)
    {
        self::prepare();

        if ($account = self::get($to)
            && $deauthorize = (new \Stripe\Account($account->vendor_id))->deauthorize(config('services.stripe.app_id'))
        ) {
            return $account->delete();
        }

        return false;
    }

    private static function setCapabilities($country)
    {
        $countryData = (new \Dominservice\DataLocaleParser\DataParser)->parseAllDataPerCountry('en', $country);
        $capabilities = [
            'card_payments' => ['requested' => true],
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

        return $capabilities;
    }

    public static function balance($to)
    {
        self::prepare();

        if ($account = self::get($to)) {
            $balance = \Stripe\Balance::retrieve(['stripe_account' => $account->vendor_id]);
            return $balance;
        }
    }
}

