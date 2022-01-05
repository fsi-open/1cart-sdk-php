<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Shipping;

use InvalidArgumentException;

final class ShipmentFactory
{
    /**
     * @param array<string,mixed> $data
     * @return Shipment
     */
    public static function fromData(array $data): Shipment
    {
        switch ($data['type'] ?? '') {
            case 'digital':
                return DigitalShipment::fromData($data);

            case 'external':
                return ExternalShipment::fromData($data);

            case 'furgonetka-dpd':
                return FurgonetkaDpdShipment::fromData($data);

            case 'furgonetka-fedex':
                return FurgonetkaFedExShipment::fromData($data);

            case 'furgonetka-inpost':
                return FurgonetkaInpostShipment::fromData($data);

            case 'furgonetka-inpost-courier':
                return FurgonetkaInpostCourierShipment::fromData($data);

            case 'self-pickup':
                return SelfPickupShipment::fromData($data);

            case 'self-distribution':
                return SelfDistributionShipment::fromData($data);
        }

        throw new InvalidArgumentException("Unknown shipment type \"{$data['type']}\"");
    }
}
