<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


use Stripe\Account as StripeAccount;

class AccountBank extends \Dominservice\LaravelStripeConnect\StripeConnect
{
    public static function create($bank, $account, $params = [])
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->account_id;
        $xtendParams = [
            'source' => [
                'object' => 'bank_account',
                'country' => $bank->country,
                'currency' => $bank->currency,
                'account_number' => $bank->account_number,
                'account_holder_name' => $bank->account_holder_name ?? null,
                'account_holder_type' => $bank->account_holder_type ?? null,
                'routing_number' => $bank->routing_number ?? null,
            ],
        ];


        $params = array_replace_recursive($xtendParams, $params);

        return StripeAccount::createExternalAccount($id, $params);
    }

}
