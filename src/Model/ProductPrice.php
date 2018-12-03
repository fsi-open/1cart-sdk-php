<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

class ProductPrice
{
    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $formatted;

    public function __construct(string $amount, string $currency, string $formatted)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->formatted = $formatted;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getFormatted(): string
    {
        return $this->formatted;
    }
}
