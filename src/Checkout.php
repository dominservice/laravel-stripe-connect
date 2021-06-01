<?php
/**
 *
 */

namespace Dominservice\LaravelStripeConnect;


use Dominservice\LaravelStripeConnect\Models\Eloquent\StripeTransaction;
use Dominservice\LaravelStripeConnect\Repositories\Account;
use Dominservice\LaravelStripeConnect\Repositories\Customer;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ExceptionInterface;

/**
 * Class Checkout
 * @package Dominservice\LaravelStripeConnect
 */
class Checkout
{
    /**
     * @var
     */
    private $paymentMethods,
        $country,
        $currency,
        $successUrl,
        $cancelUrl,
        $vendor,
        $sessionId,
        $vendorUserId,
        $customerUserId,
        $typeCharges,
        $referenceNumber;
    /**
     * @var array
     */
    private $products = [];

    /**
     * @var int
     */
    private $totalAmount = 0;

    /**
     * Checkout constructor.
     * @param null $sessionId
     */
    public function __construct($sessionId = null, $typeCharges = 'direct')
    {
        $this->sessionId = $sessionId;
        $this->typeCharges = $typeCharges; // direct | destination
    }

    /**
     * @return $this
     */
    public function directCharges()
    {
        $this->typeCharges = 'direct';
        return $this;
    }

    /**
     * @return $this
     */
    public function destinationCharges()
    {
        $this->typeCharges = 'destination';
        return $this;
    }

    /**
     * @param $user
     * @param array $params
     * @param false $company
     * @return $this
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function vendor($user, $params = [], $company = false)
    {
        $this->vendorUserId = $user->id;
        $this->vendor  = Account::create($user, $params, $company);
        $stripeAccount = Account::getStripeAccount($user);
        if (!$this->country) {
            $this->country = strtoupper($stripeAccount->country);
        }
        if (!$this->currency) {
            $this->currency = strtoupper($stripeAccount->default_currency);
        }
        return $this;
    }

    /**
     * @param int $fee
     * @param false $feeIsPercent
     * @return $this
     */
    public function fee($fee = 0, $feeIsPercent = false)
    {
        $amount = $feeIsPercent === false ? $fee : $this->totalAmount;
        $percent = !$feeIsPercent ? false : $fee;
        $this->fee = StripeConnect::validAmount($amount, $this->currency, $percent);
        return $this;

    }

    /**
     * @param $country
     * @return $this
     */
    public function country($country)
    {
        $this->country = strtoupper($country);
        return $this;
    }

    /**
     * @param $currency
     * @return $this
     */
    public function currency($currency)
    {
        $this->currency = strtoupper($currency);
        return $this;
    }

    /**
     * @param $successUrl
     * @return $this
     */
    public function successUrl($successUrl)
    {
        $this->successUrl = $successUrl;
        return $this;
    }

    /**
     * @param $cancelUrl
     * @return $this
     */
    public function cancelUrl($cancelUrl)
    {
        $this->cancelUrl = $cancelUrl;
        return $this;
    }

    /**
     * @param $user
     * @param array $params
     * @param null $token
     * @return $this
     */
    public function customer($user, $params = [], $token = null)
    {
        $this->customerUserId = $user->id;
        $this->customer = Customer::create($user, $token, $params);
        return $this;
    }

    /**
     * @param string $name
     * @param float $amount
     * @param float|int $quantity
     * @param null $currency
     * @return $this
     */
    public function product(string $name, float $amount, float $quantity = 1, $currency = null)
    {
        $currency = $currency === null ? $this->currency : $currency;
        $amount = StripeConnect::validAmount($amount, $currency);
        $this->products[] = [
            'name' => $name,
            'amount' => $amount,
            'currency' => $currency,
            'quantity' => $quantity,
        ];
        $this->totalAmount += ($amount*$quantity);
        return $this;
    }

    public function serReferenceTransaction($reference)
    {
        $this->referenceNumber = $reference;
        return $this;
    }

    /**
     * @param string $mode
     * @return string|null
     */
    public function create($mode = 'payment')
    {
        StripeConnect::prepare();
        $output = (object)['status'=>false];
        $mode = in_array($mode, ['payment', 'setup', 'subscription']) ? $mode : 'payment';
        $opt = [
            'payment_method_types' => StripeConnect::setPaymentMethods($this->country),
            'line_items' => $this->products,
            'payment_intent_data' => [
                'application_fee_amount' => $this->fee,
            ],
            'mode' => $mode,
            'success_url' => $this->successUrl,
            'cancel_url' => $this->cancelUrl,
        ];
        if (in_array($mode, ['payment', 'subscription']) && $this->customer && $this->typeCharges === 'destination') {
            $opt['customer'] = $this->customer->customer_id;
        }
        if ($this->typeCharges === 'destination') {
            $opt['payment_intent_data'] = [
                'application_fee_amount' => $this->fee,
                'on_behalf_of' => $this->vendor->vendor_id,
                'transfer_data' => [
                    'destination' => $this->vendor->vendor_id,
                ],
            ];
        }

        try {
            if ($this->typeCharges === 'destination') {
                $session = \Stripe\Checkout\Session::create($opt);
            } else {
                $session = \Stripe\Checkout\Session::create($opt, ['stripe_account' => $this->vendor->vendor_id]);
            }
            $id = $session->id;
            $output->status = true;
            $transaction = new StripeTransaction();
            $transaction->vendor_stripe_id = $this->vendor->id;
            $transaction->customer_stripe_id = $this->customer->id;
            $transaction->amount = $session->amount_total / 100;
            $transaction->vendor_amount = ($session->amount_total - $this->fee) / 100;
            $transaction->fee_amount = $this->fee / 100;
            $transaction->stripe_transaction_id = $session->payment_intent;
            $transaction->stripe_checkout_id = $session->id;
            $transaction->reference_number = $this->referenceNumber ?? strtoupper(uniqid(date('Y_m_d') . '_'));
            $transaction->currency = $this->currency;
            $transaction->status = $session->payment_status === \Stripe\Checkout\Session::PAYMENT_STATUS_UNPAID ? 0 : 1;
            $transaction->save();
            $output->transaction = $transaction;
        }
        catch (ExceptionInterface $e) {
            $id = null;
            $output->message = $e->getMessage();
            $output->transaction = null;
        }

        return $output;
    }

    public function verifyPayment()
    {
        StripeConnect::prepare();
        $response = (object)['code'=>200, 'transaction'=>null];
        $endpoint_secret = config('services.stripe.webhooks.checkout');
        $payload = request()->getContent();
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            $response->code = 400;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            $response->code = 400;
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
                $response->transaction = $this->handleCheckoutSessionCompleted($paymentIntent);
                break;
            default:
                break;
        }

        return $response;
    }

    private function handleCheckoutSessionCompleted($paymentIntent)
    {
        if ($transaction = StripeTransaction::where('stripe_checkout_id', $paymentIntent->id)->first()) {
            $transaction->status = $paymentIntent->payment_status === \Stripe\Checkout\Session::PAYMENT_STATUS_PAID ? 1 : 2;
            $transaction->save();
        }
        return $transaction;
    }
}
