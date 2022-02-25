<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use DateTimeImmutable;
use Psr\Http\Message\UriInterface;

final class DigitalFileProperties implements ProductProperties
{
    private UriInterface $uri;
    private DateTimeImmutable $expiresAt;

    public function __construct(UriInterface $uri, DateTimeImmutable $expiresAt)
    {
        $this->uri = $uri;
        $this->expiresAt = $expiresAt;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function jsonSerialize()
    {
        return null;
    }
}
