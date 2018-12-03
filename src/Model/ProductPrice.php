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

final class ProductPrice
{
    /**
     * @var Money
     */
    private $moneyObject;

    /**
     * @var string
     */
    private $formatted;

    public function __construct(string $amount, string $currency, string $formatted)
    {
        $this->moneyObject = new Money($amount, new Currency($currency));
        $this->formatted = $formatted;
    }

    public function __toString()
    {
        return $this->formatted;
    }

    public function asMoneyObject(): Money
    {
        return $this->moneyObject;
    }
}
