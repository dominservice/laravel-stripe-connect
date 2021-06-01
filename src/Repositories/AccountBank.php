<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


use Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe;
use Dominservice\LaravelStripeConnect\Models\Eloquent\StripeVendorExternalAccount;
use Stripe\Account as StripeAccount;

class AccountBank extends \Dominservice\LaravelStripeConnect\StripeConnect
{
    public static function create($bank, $account, $params = [])
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;
        $xtendParams = [
            'external_account' => [
                'object' => 'bank_account',
                'country' => $bank->country,
                'currency' => $bank->currency,
                'account_number' => $bank->account_number,
                'account_holder_name' => $bank->account_holder_name ?? null,
                'account_holder_type' => $bank->account_holder_type ?? null,
                'routing_number' => $bank->routing_number ?? null,
                'default_for_currency' => $bank->default_for_currency ?? true,
            ],
        ];
        $params = array_replace_recursive($xtendParams, $params);

        if ($response = StripeAccount::createExternalAccount($id, $params) && !is_string($account)) {
            $StripeExternalAccount = null;
            foreach (self::get($account) as $item) {
                if ((string)substr($bank->account_number, -4) === (string)$item->last4) {
                    $StripeExternalAccount = $item;
                    break;
                }
            }

            $account->has_bank_account = 1;
            $account->save();
            if ($StripeExternalAccount) {
                $extAccounts = $account->vendorExternalAccounts()->get();
                if ($extAccounts->count() > 0 && !empty($bank->default_for_currency) && $bank->default_for_currency === true) {
                    foreach ($extAccounts as $item) {
                        $item->default_for_currency = 0;
                        $item->save();
                    }
                }
                $externalAccount = new StripeVendorExternalAccount();
                $externalAccount->vendor_stripe_id = $account->id;
                $externalAccount->external_id = $StripeExternalAccount->id;
                $externalAccount->default_for_currency
                    = $extAccounts->count() === 0 || (!empty($bank->default_for_currency) && $bank->default_for_currency === true)
                    ? 1 : null;
                $externalAccount->save();
            }
        }

        return $response;
    }

    public static function get(Stripe &$account, $externalAccounts = false)
    {
        $id = $account->vendor_id;
        if (!$account->vendorExternalAccounts) {
            $account->vendorExternalAccounts = $account->vendorExternalAccounts()->get();
        }

        $return = collect();

        if (!$externalAccounts) {
            $externalAccounts = StripeAccount::allExternalAccounts($id);
        }
        foreach ($externalAccounts as $item) {
            if ($item->object === 'bank_account') {
                $item->default_for_currency = false;
                foreach ($account->vendorExternalAccounts as &$vendorExtAccount) {
                    if ($vendorExtAccount->external_id === $item->id) {
                        $vendorExtAccount->stripe = $item;
                        $item->default_for_currency = (int)$vendorExtAccount->default_for_currency === 1;
                        break;
                    }
                }
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
            $account->has_bank_account = 0;
            $account->save();
        }

        return StripeAccount::deleteExternalAccount($id, $externalAccountId);
    }
    
    public static function deleteStripeExternalAccount($account, $externalAccountId)
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;

        return StripeAccount::deleteExternalAccount($id, $externalAccountId);
    }
}
