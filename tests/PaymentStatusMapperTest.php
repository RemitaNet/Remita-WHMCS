<?php

declare(strict_types=1);

use PaymentEngine\Whmcs\Support\PaymentStatusMapper;

final class PaymentStatusMapperTest extends TestCase
{
    public function testApprovedQueryMapsToSuccess(): void
    {
        $result = PaymentStatusMapper::mapQueryResponse([
            'status' => '00',
            'data' => ['paymentState' => 'APPROVED'],
        ]);

        $this->assertSame(PaymentStatusMapper::STATUS_SUCCESS, $result);
    }

    public function testPendingWebhookMapsToPending(): void
    {
        $result = PaymentStatusMapper::mapWebhookPayload([
            'data' => ['status' => 'pending'],
        ]);

        $this->assertSame(PaymentStatusMapper::STATUS_PENDING, $result);
    }
}
