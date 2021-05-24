<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


use Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe;
use Stripe\Account as StripeAccount;

class AccountCard extends \Dominservice\LaravelStripeConnect\StripeConnect
{
    public static function create($card, $account, $params = [])
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;
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

        if ($response = StripeAccount::createExternalAccount($id, $params) && !is_string($account)) {
            $account->has_payment_card = 1;
            $account->save();
        }
        return $response;
    }

    public static function get($account, $externalAccounts = false)
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;
        $return = collect();

        if (!$externalAccounts) {
            $externalAccounts = StripeAccount::allExternalAccounts($id);
        }
        foreach ($externalAccounts as $item) {
            if ($item->object === 'card') {
                $return->push($item);
            }
        }

        return $return;
    }

    public static function delete($account, $externalAccountId)
    {
        $id = is_string($account) ? $account : $account->vendor_id;
        if (is_string($account)) {
            $account = Stripe::where('vendor_id', $id);
        }
        $list = self::get($account);
        
        if ($list->count() === 0) {
            $account->has_payment_card = 0;
            $account->save();
        }

        return StripeAccount::deleteExternalAccount($id, $externalAccountId);
    }
}
