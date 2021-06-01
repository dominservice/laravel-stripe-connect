<?php


namespace Dominservice\LaravelStripeConnect\Repositories;

use Dominservice\LaravelStripeConnect\StripeConnect;
use Stripe\BaseStripeClient;
use Stripe\Service\BalanceTransactionService;

class Client extends StripeConnect
{
    public static function balanceTransactions($to, $balanceId = null)
    {
        self::prepare();

        if ($account = self::geStripeClientObject()) {
            if ($balanceId) {
                return (new BalanceTransactionService($account))->retrieve($balanceId);
            }
            return (new BalanceTransactionService($account))->all();
        }
        return null;
    }

    private static function geStripeClientObject()
    {
        return new BaseStripeClient(config('services.stripe.secret'));
    }
}
