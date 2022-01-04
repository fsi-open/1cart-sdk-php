<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

use Money\Currency;
use Money\Money;

final class FormattedMoney
{
    private Money $money;
    private string $formatted;

    /**
     * @param array<array-key,mixed> $data
     * @return Money
     */
    public static function fromData(array $data): Money
    {
        return new Money($data['amount'] ?? '', new Currency($data['currency'] ?? ''));
    }

    /**
     * @param numeric-string $amount
     * @param non-empty-string $currency
     * @param string $formatted
     */
    public function __construct(string $amount, string $currency, string $formatted)
    {
        $this->money = new Money($amount, new Currency($currency));
        $this->formatted = $formatted;
    }

    public function __toString(): string
    {
        return $this->formatted;
    }

    public function asMoneyObject(): Money
    {
        return $this->money;
    }
}
