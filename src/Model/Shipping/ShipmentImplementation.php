<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Shipping;

use DateTimeImmutable;
use OneCart\Api\Model\FormattedMoney;
use Ramsey\Uuid\UuidInterface;

trait ShipmentImplementation
{
    private UuidInterface $id;
    private DateTimeImmutable $createdAt;
    private string $description;
    /**
     * @var array<int,string>
     */
    private array $productIds;
    private FormattedMoney $price;
    private ?FormattedMoney $codValue;
    private ?DateTimeImmutable $preparedAt;
    private ?DateTimeImmutable $deliveredAt;
    private ?DateTimeImmutable $cancelledAt;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<int,string>
     */
    public function getProductIds(): array
    {
        return $this->productIds;
    }

    public function getPrice(): FormattedMoney
    {
        return $this->price;
    }

    public function getCodValue(): ?FormattedMoney
    {
        return $this->codValue;
    }

    public function getPreparedAt(): ?DateTimeImmutable
    {
        return $this->preparedAt;
    }

    public function getDeliveredAt(): ?DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    /**
     * @param UuidInterface $id
     * @param DateTimeImmutable $createdAt
     * @param string $description
     * @param array<int,string> $productIds
     * @param FormattedMoney $price
     * @param FormattedMoney|null $codValue
     * @param DateTimeImmutable|null $preparedAt
     * @param DateTimeImmutable|null $deliveredAt
     * @param DateTimeImmutable|null $cancelledAt
     * @return void
     */
    private function construct(
        UuidInterface $id,
        DateTimeImmutable $createdAt,
        string $description,
        array $productIds,
        FormattedMoney $price,
        ?FormattedMoney $codValue,
        ?DateTimeImmutable $preparedAt,
        ?DateTimeImmutable $deliveredAt,
        ?DateTimeImmutable $cancelledAt
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->description = $description;
        $this->productIds = $productIds;
        $this->price = $price;
        $this->codValue = $codValue;
        $this->preparedAt = $preparedAt;
        $this->deliveredAt = $deliveredAt;
        $this->cancelledAt = $cancelledAt;
    }
}
