<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Payment;

use InvalidArgumentException;

final class PaymentFactory
{
    /**
     * @param array<string,mixed> $data
     * @return Payment
     */
    public static function fromData(array $data): Payment
    {
        switch ($data['type'] ?? '') {
            case 'cod':
                return CashOnDeliveryPayment::fromData($data);

            case 'blue_media':
                return BlueMediaPayment::fromData($data);

            case 'dotpay':
                return DotPayPayment::fromData($data);
        }

        throw new InvalidArgumentException("Unknown payment type \"{$data['type']}\"");
    }
}
