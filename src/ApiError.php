<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api;

final class ApiError
{
    private string $field;
    private string $message;

    /**
     * @param array{field?:string,message?:string} $data
     * @return static
     */
    public static function fromData(array $data): self
    {
        return new self($data['field'] ?? '', $data['message'] ?? '');
    }

    public function __construct(string $field, string $message)
    {
        $this->field = $field;
        $this->message = $message;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
