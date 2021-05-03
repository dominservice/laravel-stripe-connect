<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


use Stripe\Account as StripeAccount;

class AccountCard extends \Dominservice\LaravelStripeConnect\StripeConnect
{
    public static function create($card, $account, $params = [])
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->account_id;
        $xtendParams = [
            'source' => [
                'object' => 'card',
                'number' => (string)$card->number,
                'exp_month' => (int)$card->exp_month,
                'exp_year' => (int)$card->exp_year,
                'cvc' => (int)$card->cvc,
                'name' => (string)$card->cardholder_full_name,
            ],
        ];


        $params = array_replace_recursive($xtendParams, $params);

        return StripeAccount::createExternalAccount($id, $params);
    }

}
