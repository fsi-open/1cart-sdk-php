<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

final class EuVatExemptionExtension implements ProductExtension
{
    private string $vatExemption;

    public function __construct(string $vatExemption)
    {
        EuVatExemption::assertExists($vatExemption);

        $this->vatExemption = $vatExemption;
    }

    public function getVatExemption(): string
    {
        return $this->vatExemption;
    }

    public function getKey(): string
    {
        return 'eu_vat_exemption';
    }

    public function jsonSerialize()
    {
        return [
            'vat_exemption' => $this->vatExemption,
        ];
    }
}
