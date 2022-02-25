<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

final class EuReturnRightsForfeitExtension implements ProductExtension
{
    private bool $forfeitRequired;

    public function __construct(bool $forfeitRequired)
    {
        $this->forfeitRequired = $forfeitRequired;
    }

    public function isForfeitRequired(): bool
    {
        return $this->forfeitRequired;
    }

    public function getKey(): string
    {
        return 'eu_return_rights_forfeit';
    }

    public function jsonSerialize()
    {
        return [
            'forfeit_required' => $this->forfeitRequired,
        ];
    }
}
