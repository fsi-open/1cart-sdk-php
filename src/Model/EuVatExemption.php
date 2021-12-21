<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

use CommerceGuys\Enum\AbstractEnum;

final class EuVatExemption extends AbstractEnum
{
    public const SUBJECT = 'subject';
    public const FREE = 'free';
}
