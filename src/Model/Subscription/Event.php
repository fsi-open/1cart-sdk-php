<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Subscription;

use CommerceGuys\Enum\AbstractEnum;

final class Event extends AbstractEnum
{
    public const ORDER_CREATED = 'orderCreated';
    public const ORDER_CANCELLED = 'orderCancelled';
    public const PAYMENT_STATE_CHANGED = 'paymentStateChanged';
    public const SHIPMENT_STATE_CHANGED = 'shipmentStateChanged';
}
