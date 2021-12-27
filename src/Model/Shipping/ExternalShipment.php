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
use OneCart\Api\Model\PersonalAddress;
use Ramsey\Uuid\UuidInterface;

final class ExternalShipment implements Shipment
{
    use CourierShipmentImplementation;
    use ShipmentImplementation;

    private string $courierCompany;

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
     * @param PersonalAddress $sender
     * @param PersonalAddress $recipient
     * @param string $courierCompany
     * @param DateTimeImmutable|null $pickedUpAt
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
        PersonalAddress $sender,
        PersonalAddress $recipient,
        string $courierCompany,
        ?DateTimeImmutable $pickedUpAt
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

        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->courierCompany = $courierCompany;
        $this->pickedUpAt = $pickedUpAt;
    }

    public function getCourierCompany(): string
    {
        return $this->courierCompany;
    }
}
