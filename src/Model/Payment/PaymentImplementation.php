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
use Ramsey\Uuid\UuidInterface;

trait PaymentImplementation
{
    private UuidInterface $id;
    private DateTimeImmutable $createdAt;
    private Money $value;
    private ?DateTimeImmutable $completedAt;
    private ?DateTimeImmutable $cancelledAt;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getValue(): Money
    {
        return $this->value;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    private function construct(
        UuidInterface $id,
        DateTimeImmutable $createdAt,
        Money $value,
        ?DateTimeImmutable $completedAt,
        ?DateTimeImmutable $cancelledAt
    ): void {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->value = $value;
        $this->completedAt = $completedAt;
        $this->cancelledAt = $cancelledAt;
    }
}
