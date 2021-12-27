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
use OneCart\Api\Model\PersonalAddress;

trait CourierShipmentImplementation
{
    private PersonalAddress $sender;
    private PersonalAddress $recipient;
    private ?DateTimeImmutable $pickedUpAt;

    public function getSender(): PersonalAddress
    {
        return $this->sender;
    }

    public function getRecipient(): PersonalAddress
    {
        return $this->recipient;
    }

    public function getPickedUpAt(): ?DateTimeImmutable
    {
        return $this->pickedUpAt;
    }
}
