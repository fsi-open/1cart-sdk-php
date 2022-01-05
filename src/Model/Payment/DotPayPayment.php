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
use Money\Money;
use OneCart\Api\Model\FormattedMoney;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class DotPayPayment implements Payment
{
    use PaymentImplementation;

    private int $channel;

    /**
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromData(array $data): self
    {
        return new self(
            Uuid::fromString($data['id']),
            new DateTimeImmutable($data['created_at']),
            FormattedMoney::fromData($data['value'] ?? []),
            (null !== ($data['completed_at'] ?? null)) ? new DateTimeImmutable($data['completed_at']) : null,
            (null !== ($data['cancelled_at'] ?? null)) ? new DateTimeImmutable($data['cancelled_at']) : null,
            $data['channel'] ?? 0
        );
    }

    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $createdAt,
        Money $value,
        ?DateTimeImmutable $completedAt,
        ?DateTimeImmutable $cancelledAt,
        int $channel
    ) {
        $this->construct($id, $createdAt, $value, $completedAt, $cancelledAt);
        $this->channel = $channel;
    }

    public function getChannel(): int
    {
        return $this->channel;
    }
}
