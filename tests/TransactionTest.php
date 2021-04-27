<?php
namespace Dominservice\LaravelStripeConnect\Tests;

use PHPUnit\Framework\TestCase;
use Dominservice\LaravelStripeConnect\StripeConnect;
use Dominservice\LaravelStripeConnect\Transaction;

/**
 * Class TransactionTest
 * @package Dominservice\LaravelStripeConnect\Tests
 */
class TransactionTest extends TestCase
{
    public function testSetters()
    {
        $transaction = StripeConnect::transaction()->fee(35)->amount(1000, 'usd');
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
