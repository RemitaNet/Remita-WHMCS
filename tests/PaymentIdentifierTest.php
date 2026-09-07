<?php

declare(strict_types=1);

use PaymentEngine\Whmcs\Support\PaymentIdentifier;

final class PaymentIdentifierTest extends TestCase
{
    public function testBuildIncludesInvoiceId(): void
    {
        $paymentIdentifier = PaymentIdentifier::build(42);

        $this->assertTrue(str_starts_with($paymentIdentifier, 'whmcs-42-'));
        $this->assertSame(42, PaymentIdentifier::extractInvoiceId($paymentIdentifier));
    }

    public function testExtractInvoiceIdReturnsNullForUnknownShape(): void
    {
        $this->assertSame(null, PaymentIdentifier::extractInvoiceId('wc-42-123'));
    }
}
