<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Payment;

use DateTimeImmutable;
use OneCart\Api\Model\FormattedMoney;
use Ramsey\Uuid\UuidInterface;

final class DotPayPayment implements Payment
{
    use PaymentImplementation;

    private int $channel;

    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $createdAt,
        FormattedMoney $value,
        ?DateTimeImmutable $completedAt,
        ?DateTimeImmutable $cancelledAt,
        int $channel
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->value = $value;
        $this->completedAt = $completedAt;
        $this->cancelledAt = $cancelledAt;
        $this->channel = $channel;
    }

    public function getChannel(): int
    {
        return $this->channel;
    }
}
