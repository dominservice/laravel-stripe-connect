# Laravel Stripe Connect

[![Packagist](https://img.shields.io/packagist/v/Dominservice/laravel-stripe-connect.svg)]()
[![Packagist](https://img.shields.io/packagist/l/Dominservice/laravel-stripe-connect.svg)](https://packagist.org/packages/Dominservice/laravel-stripe-connect)
[![Build Status](https://travis-ci.org/Dominservice/laravel-stripe-connect.svg?branch=master)](https://travis-ci.org/Dominservice/laravel-stripe-connect)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Dominservice/laravel-stripe-connect/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Dominservice/laravel-stripe-connect/?branch=master)

> Marketplaces and platforms use Stripe Connect to accept money and pay out to third parties. Connect provides a complete set of building blocks to support virtually any business model, including on-demand businesses, e‑commerce, crowdfunding, fintech, and travel and events. 

Create a marketplace application with this helper for [Stripe Connect](https://stripe.com/connect).

## Installation

Install via composer

```
composer require Dominservice/laravel-stripe-connect
```
Or place manually in composer.json:
```
"require": {
    "dominservice/conversations": "^1.0"
}
```
Run:
```
composer update
```
Add the service provider to `config/app.php`

```php
'providers' => [
    Dominservice\LaravelStripeConnect\ServiceProvider::class,
],

(...)

'aliases' => [
    'LaravelStripeConnect' => Dominservice\LaravelStripeConnect\Facade\LaravelStripeConnectFacade::class,
]
```

Add your stripe credentials in `.env`:

```
STRIPE_KEY=pk_test_XxxXXxXXX
STRIPE_SECRET=sk_test_XxxXXxXXX
```
and made a call from the config/services.php as

```php
'stripe' => [
  'model'  => App\User::class,
  'key'    => env('STRIPE_KEY'),
  'secret' => env('STRIPE_SECRET'),
],
```

Run migrations:

```
php artisan migrate
```

## Version Compatibility

The following table shows which version to install. We have provided the Stripe API version that we
developed against as guide. You may find the package works with older versions of the API.

| Laravel | Stripe PHP | Stripe API | Laravel Stripe Connect |
| :-- | :-- | :-- | :-- |
| `^8.0` | `^7.52` | `>=2020-03-02` | `^1` |
| `^7.0` | `^7.0` | `>=2020-03-02` | `^1` |
| `^6.0` | `^6.40` | `>=2019-05-16` | `^1` |

## Usage

You can make a single payment from a user to another user
 or save a customer card for later use. Just remember to
 import the base class via:
 
```php
use Dominservice\LaravelStripeConnect\StripeConnect;
```

### Example #1: direct charge

The customer gives his credentials via Stripe Checkout and is charged.
It's a one shot process. `$customer` and `$vendor` must be `User` instances. The `$token` must have been created using [Checkout](https://stripe.com/docs/checkout/tutorial) or [Elements](https://stripe.com/docs/stripe-js).

```php
StripeConnect::transaction($token)
    ->amount(1000, 'usd')
    ->from($customer)
    ->to($vendor)
    ->create(); 
```

### Example #2: save a customer then charge later

Sometimes, you may want to register a card then charge later.
First, create the customer.

```php
StripeConnect::createCustomer($token, $customer);
```

Then, (later) charge the customer without token.

```php
StripeConnect::transaction()
    ->amount(1000, 'usd')
    ->useSavedCustomer()
    ->from($customer)
    ->to($vendor)
    ->create(); 
```

### Exemple #3: create a vendor account

You may want to create the vendor account before charging anybody.
Just call `createAccount` with a `User` instance.

```php
StripeConnect::createAccount($vendor);
```

### Exemple #4: Charge with application fee

```php
StripeConnect::transaction($token)
    ->amount(1000, 'usd')
    ->fee(50)
    ->from($customer)
    ->to($vendor)
    ->create(); 
```



## Contributing

We have only implemented the repositories for the Stripe resources we are using in our application.

If you find this package is missing a resource you need in your application, an ideal way to contribute
is to submit a pull request to add the missing repository.

