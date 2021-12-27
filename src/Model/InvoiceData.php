<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

use InvalidArgumentException;

final class InvoiceData
{
    private ?string $givenName;
    private ?string $familyName;
    private ?string $organization;
    private ?TaxId $taxId;
    private Address $address;

    public function __construct(
        ?string $givenName,
        ?string $familyName,
        ?string $organization,
        ?TaxId $taxId,
        Address $address
    ) {
        if (true === $this->isEmptyString($givenName) xor true === $this->isEmptyString($familyName)) {
            throw new InvalidArgumentException('Both given name and family name must be provided or omitted');
        }

        if (true === $this->isEmptyString($givenName) && true === $this->isEmptyString($organization)) {
            throw new InvalidArgumentException('Organization or given name and family name is required');
        }

        if (null === $taxId && false === $this->isEmptyString($organization)) {
            throw new InvalidArgumentException('Tax ID is required for organizations inside EU');
        }

        $this->givenName = $givenName;
        $this->familyName = $familyName;
        $this->organization = $organization;
        $this->taxId = $taxId;
        $this->address = $address;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function getTaxId(): ?TaxId
    {
        return $this->taxId;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    private function isEmptyString(?string $value): bool
    {
        return null === $value || '' === $value;
    }
}
