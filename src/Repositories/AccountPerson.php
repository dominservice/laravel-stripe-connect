<?php


namespace Dominservice\LaravelStripeConnect\Repositories;


use Dominservice\LaravelStripeConnect\Models\Eloquent\Stripe;
use Dominservice\LaravelStripeConnect\StripeConnect;
use Stripe\Account as StripeAccount;

class AccountPerson extends StripeConnect
{
    /**
     * @param $account
     * @return \Stripe\Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function index($account)
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;

        return StripeAccount::allPersons($id);
    }

    /**
     * @param $account
     * @param $personId
     * @return \Stripe\Person
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function get($account, $personId)
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;

        return StripeAccount::retrievePerson($id, $personId);
    }

    /**
     * @param $to
     * @param $account
     * @param array $params
     * @return Stripe|string
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function create($to, $account, $params = []): Stripe|string
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;
        $dob = \Carbon\Carbon::parse($to->dob);
        $xtendParams = [
            'address' => [
                'city' => $to->city ?? null,
                'country' => $to->country ?? null,
                'line1' => $to->address ?? null,
                'line2' => $to->address_2 ?? null,
                'postal_code' => $to->postcode ?? null,
                'state' => $to->state ?? null,
            ],
            'email' => $to->email,
            'first_name' => $to->first_name,
            'last_name' => $to->last_name,
            'phone' => $to->phone,
            'dob' => [
                'day' => $dob->day,
                'month' => $dob->month,
                'year' => $dob->year,
            ],
            'relationship' => [
                'director' => true,
                'executive' => true,
                'owner' => true,
                'percent_ownership' => null,
                'representative' => true,
                'title' => 'Owner',
            ]
        ];
        $params = array_replace_recursive($xtendParams, $params);

        if ($response = StripeAccount::createPerson($id, $params) && !is_string($account)) {
            $account->has_person = 1;
            $account->save();
        }

        return $response;
    }

    /**
     * @param $to
     * @param $account
     * @param $personId
     * @param $params
     * @return Stripe|string
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function update($to, $account, $personId, $params): Stripe|string
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;

        return StripeAccount::updatePerson($id, $personId, $params);
    }

    /**
     * @param $account
     * @param $personId
     * @return \Stripe\Person
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function delete($account, $personId)
    {
        self::prepare();
        $id = is_string($account) ? $account : $account->vendor_id;

        return StripeAccount::deletePerson($id, $personId);
    }
}
