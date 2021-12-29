<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

final class PersonalAddress
{
    private Person $person;
    private Address $address;

    /**
     * @param array<string,array<string,mixed>> $data
     * @return static
     */
    public static function fromData(array $data): self
    {
        return new self(Person::fromData($data['person'] ?? []), Address::fromData($data['address'] ?? []));
    }

    public function __construct(Person $person, Address $address)
    {
        $this->person = $person;
        $this->address = $address;
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}
