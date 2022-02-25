<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use Psr\Http\Message\UriInterface;

final class DigitalUriProperties implements ProductProperties
{
    private UriInterface $uri;

    public function __construct(UriInterface $uri)
    {
        $this->uri = $uri;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function jsonSerialize()
    {
        return [
            'type' => 'digital-url',
            'uri' => $this->uri,
        ];
    }
}
