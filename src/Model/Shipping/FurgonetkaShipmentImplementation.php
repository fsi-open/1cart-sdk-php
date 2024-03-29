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
use OneCart\Api\Model\Dimensions;

trait FurgonetkaShipmentImplementation
{
    private Dimensions $dimensions;
    private float $weight;
    private ?string $waybillNumber;
    private ?Money $surcharge;
    private ?string $surchargeDescription;
    private ?DateTimeImmutable $returnedAt;

    public function getDimensions(): Dimensions
    {
        return $this->dimensions;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function getWaybillNumber(): ?string
    {
        return $this->waybillNumber;
    }

    public function getSurcharge(): ?Money
    {
        return $this->surcharge;
    }

    public function getSurchargeDescription(): ?string
    {
        return $this->surchargeDescription;
    }

    public function getReturnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }

    private function furgonetkaConstruct(
        Dimensions $dimensions,
        float $weight,
        ?string $waybillNumber,
        ?Money $surcharge,
        ?string $surchargeDescription,
        ?DateTimeImmutable $returnedAt
    ): void {
        $this->dimensions = $dimensions;
        $this->weight = $weight;
        $this->waybillNumber = $waybillNumber;
        $this->surcharge = $surcharge;
        $this->surchargeDescription = $surchargeDescription;
        $this->returnedAt = $returnedAt;
    }
}
