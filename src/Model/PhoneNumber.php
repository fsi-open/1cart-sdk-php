<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use RuntimeException;

final class PhoneNumber
{
    private string $phone;

    public function __construct(string $phone)
    {
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneNumberInfo = $phoneNumberUtil->parse($phone);
        } catch (NumberParseException $e) {
            throw new RuntimeException("Invalid phone number {$phone}");
        }

        $this->phone = $phone;
    }

    public function __toString(): string
    {
        return $this->phone;
    }
}
