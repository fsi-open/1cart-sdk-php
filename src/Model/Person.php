<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

final class Person
{
    private string $givenName;
    private string $familyName;
    private ?string $organization;
    private ?EmailAddress $email;
    private ?PhoneNumber $phoneNumber;

    public function __construct(
        string $givenName,
        string $familyName,
        ?string $organization,
        ?EmailAddress $email,
        ?PhoneNumber $phoneNumber
    ) {
        $this->givenName = $givenName;
        $this->familyName = $familyName;
        $this->organization = $organization;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
    }

    public function getGivenName(): string
    {
        return $this->givenName;
    }

    public function getFamilyName(): string
    {
        return $this->familyName;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function getEmail(): ?EmailAddress
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }
}
