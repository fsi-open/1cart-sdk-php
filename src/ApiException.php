<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /**
     * @var array<int,ApiError>
     */
    private array $errors;

    /**
     * @param string $message
     * @param array<int,ApiError> $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);

        $this->errors = $errors;
    }

    /**
     * @return array<int,ApiError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
