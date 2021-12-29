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
use OneCart\Api\Model\Dimensions;
use OneCart\Api\Model\FormattedMoney;
use OneCart\Api\Model\PersonalAddress;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class FurgonetkaInpostCourierShipment implements Shipment
{
    use CourierShipmentImplementation;
    use FurgonetkaShipmentImplementation;
    use ShipmentImplementation;

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
            Dimensions::fromData($data['dimensions'] ?? []),
            (float) $data['weight'],
            $data['waybill_number'] ?? null,
            (null !== ($data['surcharge'] ?? null)) ? FormattedMoney::fromData($data['surcharge']) : null,
            $data['surcharge_description'] ?? null,
            (null !== ($data['returned_at'] ?? null)) ? new DateTimeImmutable($data['returned_at']) : null,
            (null !== ($data['picked_up_at'] ?? null)) ? new DateTimeImmutable($data['picked_up_at']) : null,
            PersonalAddress::fromData($data['sender']),
            PersonalAddress::fromData($data['recipient'])
        );
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
     * @param Dimensions $dimensions
     * @param float $weight
     * @param string|null $waybillNumber
     * @param FormattedMoney|null $surcharge
     * @param string|null $surchargeDescription
     * @param DateTimeImmutable|null $returnedAt
     * @param DateTimeImmutable|null $pickedUpAt
     * @param PersonalAddress $sender
     * @param PersonalAddress $recipient
     */
    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $createdAt,
        string $description,
        array $productIds,
        FormattedMoney $price,
        ?FormattedMoney $codValue,
        ?DateTimeImmutable $preparedAt,
        ?DateTimeImmutable $deliveredAt,
        ?DateTimeImmutable $cancelledAt,
        Dimensions $dimensions,
        float $weight,
        ?string $waybillNumber,
        ?FormattedMoney $surcharge,
        ?string $surchargeDescription,
        ?DateTimeImmutable $returnedAt,
        ?DateTimeImmutable $pickedUpAt,
        PersonalAddress $sender,
        PersonalAddress $recipient
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

        $this->furgonetkaConstruct(
            $dimensions,
            $weight,
            $waybillNumber,
            $surcharge,
            $surchargeDescription,
            $returnedAt
        );

        $this->pickedUpAt = $pickedUpAt;
        $this->sender = $sender;
        $this->recipient = $recipient;
    }
}
