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
use Money\Money;
use OneCart\Api\Model\FormattedMoney;
use OneCart\Api\Model\PersonalAddress;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class SelfDistributionShipment implements Shipment
{
    use ShipmentImplementation;

    private PersonalAddress $recipient;
    private string $pointName;

    /**
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromData(array $data): self
    {
        return new self(
            Uuid::fromString($data['id']),
            new DateTimeImmutable($data['created_at']),
            $data['description'],
            $data['items'],
            FormattedMoney::fromData($data['price'] ?? []),
            (null !== ($data['cod_price'] ?? null)) ? FormattedMoney::fromData($data['cod_price']) : null,
            (null !== ($data['prepared_at'] ?? null)) ? new DateTimeImmutable($data['prepared_at']) : null,
            (null !== ($data['delivered_at'] ?? null)) ? new DateTimeImmutable($data['delivered_at']) : null,
            (null !== ($data['cancelled_at'] ?? null)) ? new DateTimeImmutable($data['cancelled_at']) : null,
            PersonalAddress::fromData($data['recipient']),
            $data['self_distribution_point_name'] ?? ''
        );
    }

    /**
     * @param UuidInterface $id
     * @param DateTimeImmutable $createdAt
     * @param string $description
     * @param array<int,string> $productIds
     * @param Money $price
     * @param Money|null $codValue
     * @param DateTimeImmutable|null $preparedAt
     * @param DateTimeImmutable|null $deliveredAt
     * @param DateTimeImmutable|null $cancelledAt
     * @param PersonalAddress $recipient
     * @param string $pointName
     */
    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $createdAt,
        string $description,
        array $productIds,
        Money $price,
        ?Money $codValue,
        ?DateTimeImmutable $preparedAt,
        ?DateTimeImmutable $deliveredAt,
        ?DateTimeImmutable $cancelledAt,
        PersonalAddress $recipient,
        string $pointName
    ) {
        $this->construct(
            $id,
            $createdAt,
            $description,
            $productIds,
            $price,
            $codValue,
            $preparedAt,
            $deliveredAt,
            $cancelledAt
        );

        $this->recipient = $recipient;
        $this->pointName = $pointName;
    }

    public function getRecipient(): PersonalAddress
    {
        return $this->recipient;
    }

    public function getPointName(): string
    {
        return $this->pointName;
    }
}
