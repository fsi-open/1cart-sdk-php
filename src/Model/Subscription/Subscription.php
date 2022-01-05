<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Subscription;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Subscription
{
    private UuidInterface $id;
    private UriInterface $uri;
    private string $event;

    /**
     * @param array<string,mixed> $data
     * @param UriFactoryInterface $uriFactory
     * @return static
     */
    public static function fromData(array $data, UriFactoryInterface $uriFactory): self
    {
        return new self(Uuid::fromString($data['id']), $uriFactory->createUri($data['url']), $data['event']);
    }

    /**
     * @param UuidInterface $id
     * @param UriInterface $uri
     * @param Event::* $event
     */
    public function __construct(UuidInterface $id, UriInterface $uri, string $event)
    {
        Event::assertExists($event);

        $this->id = $id;
        $this->uri = $uri;
        $this->event = $event;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getEvent(): string
    {
        return $this->event;
    }
}
