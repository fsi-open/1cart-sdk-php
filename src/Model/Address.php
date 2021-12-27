<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use InvalidArgumentException;

use function array_diff;
use function array_key_exists;
use function implode;
use function in_array;
use function preg_match;
use function sprintf;
use function trim;

final class Address
{
    private static ?CountryRepositoryInterface $countryRepository = null;
    private static ?AddressFormatRepositoryInterface $addressFormatRepository = null;
    private static ?SubdivisionRepositoryInterface $subdivisionRepository = null;

    private string $countryCode;
    private ?string $administrativeArea;
    private ?string $locality;
    private ?string $dependentLocality;
    private ?string $postalCode;
    private ?string $sortingCode;
    private string $street;
    private string $buildingNumber;
    private ?string $flatNumber;

    public function __construct(
        string $countryCode,
        ?string $postalCode,
        ?string $sortingCode,
        ?string $administrativeArea,
        ?string $locality,
        ?string $dependentLocality,
        string $street,
        string $buildingNumber,
        ?string $flatNumber
    ) {
        $this->validateCountryCode($countryCode);
        $this->countryCode = $countryCode;

        $this->validateFields(
            $countryCode,
            $postalCode,
            $sortingCode,
            $administrativeArea,
            $locality,
            $dependentLocality
        );

        $this->validateSubdivisions(
            $countryCode,
            $administrativeArea,
            $locality,
            $dependentLocality
        );

        if ($postalCode !== null) {
            $this->validatePostalCode($postalCode, $countryCode);
        }

        $this->postalCode = $postalCode;
        $this->sortingCode = $sortingCode;
        $this->administrativeArea = $administrativeArea;
        $this->locality = $locality;
        $this->dependentLocality = $dependentLocality;

        if ('' === trim($street)) {
            throw new InvalidArgumentException('Street is required');
        }
        $this->street = $street;
        if ('' === trim($buildingNumber)) {
            throw new InvalidArgumentException('Building number is required');
        }
        $this->buildingNumber = $buildingNumber;
        if (null !== $flatNumber && '' === trim($flatNumber)) {
            throw new InvalidArgumentException('Flat number can\'t be blank');
        }
        $this->flatNumber = $flatNumber;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getAdministrativeArea(): ?string
    {
        return $this->administrativeArea;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function getDependentLocality(): ?string
    {
        return $this->dependentLocality;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getSortingCode(): ?string
    {
        return $this->sortingCode;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getBuildingNumber(): string
    {
        return $this->buildingNumber;
    }

    public function getFlatNumber(): ?string
    {
        return $this->flatNumber;
    }

    private function validateCountryCode(string $countryCode): void
    {
        if (false === array_key_exists($countryCode, $this->getCountryRepository()->getList())) {
            throw new InvalidArgumentException(sprintf('Country "%s" is unknown', $countryCode));
        }
    }

    private function validateFields(
        string $countryCode,
        ?string $postalCode,
        ?string $sortingCode,
        ?string $administrativeArea,
        ?string $locality,
        ?string $dependentLocality
    ): void {
        $addressFormat = $this->getAddressFormatRepository()->get($countryCode);
        $ignoredFields = [
            AddressField::GIVEN_NAME,
            AddressField::FAMILY_NAME,
            AddressField::ADDITIONAL_NAME,
            AddressField::ORGANIZATION,
            AddressField::ADDRESS_LINE1,
            AddressField::ADDRESS_LINE2
        ];

        $requiredFields = $addressFormat->getRequiredFields();
        foreach ($requiredFields as $requiredField) {
            if (in_array($requiredField, $ignoredFields, true)) {
                continue;
            }

            if (null === $$requiredField || '' === trim($$requiredField)) {
                throw new InvalidArgumentException(
                    "Field \"{$requiredField}\" is required for country \"{$countryCode}\""
                );
            }
        }

        $unusedFields = array_diff(AddressField::getAll(), $addressFormat->getUsedFields());
        foreach ($unusedFields as $unusedField) {
            if (true === in_array($unusedField, $ignoredFields, true)) {
                continue;
            }

            if (null !== $$unusedField) {
                throw new InvalidArgumentException(
                    "Field \"{$unusedField}\" is not allowed in country \"{$countryCode}\""
                );
            }
        }

        $optionalFields = array_diff($addressFormat->getUsedFields(), $requiredFields);
        foreach ($optionalFields as $optionalField) {
            if (in_array($optionalField, $ignoredFields, true)) {
                continue;
            }

            if (null !== $$optionalField && '' === trim($$optionalField)) {
                throw new InvalidArgumentException(
                    "Field \"{$optionalField}\" cannot be blank in country \"{$countryCode}\""
                );
            }
        }
    }

    private function validateSubdivisions(
        string $countryCode,
        ?string $administrativeArea,
        ?string $locality,
        ?string $dependentLocality
    ): void {
        $addressFormat = $this->getAddressFormatRepository()->get($countryCode);
        if ($addressFormat->getSubdivisionDepth() < 1) {
            return;
        }

        $subdivisionFields = $addressFormat->getUsedSubdivisionFields();
        $parents = [$countryCode];
        foreach ($subdivisionFields as $field) {
            if (null === $$field) {
                break;
            }

            $subdivision = $this->getSubdivisionRepository()->get($$field, $parents);
            if (null === $subdivision) {
                throw new InvalidArgumentException(
                    sprintf('%s "%s" could not be found inside "%s"', $field, $$field, implode(', ', $parents))
                );
            }

            if (false === $subdivision->hasChildren()) {
                break;
            }

            $parents[] = $$field;
        }
    }

    /**
     * @param string $postalCode
     * @param string $countryCode
     */
    private function validatePostalCode(string $postalCode, string $countryCode): void
    {
        $addressFormat = $this->getAddressFormatRepository()->get($countryCode);
        $postalCodePattern = $addressFormat->getPostalCodePattern();

        if (null !== $postalCodePattern) {
            preg_match("/{$postalCodePattern}/i", $postalCode, $matches);
            if (($matches[0] ?? null) !== $postalCode) {
                throw new InvalidArgumentException(
                    "Postal code \"{$postalCode}\" is invalid for country \"{$countryCode}\""
                );
            }
        }
    }

    private function getCountryRepository(): CountryRepositoryInterface
    {
        if (null === self::$countryRepository) {
            self::$countryRepository = new CountryRepository();
        }

        return self::$countryRepository;
    }

    private function getAddressFormatRepository(): AddressFormatRepositoryInterface
    {
        if (null === self::$addressFormatRepository) {
            self::$addressFormatRepository = new AddressFormatRepository();
        }

        return self::$addressFormatRepository;
    }

    private function getSubdivisionRepository(): SubdivisionRepositoryInterface
    {
        if (null === self::$subdivisionRepository) {
            self::$subdivisionRepository = new SubdivisionRepository();
        }

        return self::$subdivisionRepository;
    }
}
