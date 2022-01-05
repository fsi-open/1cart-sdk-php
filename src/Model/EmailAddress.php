<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use RuntimeException;

final class EmailAddress
{
    private static ?EmailValidator $emailValidator = null;
    private static ?EmailValidation $emailValidation = null;

    private string $email;

    public function __construct(string $email)
    {
        if (null === self::$emailValidator) {
            self::$emailValidator = new EmailValidator();
        }

        if (null === self::$emailValidation) {
            self::$emailValidation = new RFCValidation();
        }

        if (true !== self::$emailValidator->isValid($email, self::$emailValidation)) {
            throw new RuntimeException("Value \"{$email}\" was expected to be a valid e-mail address.");
        }

        $this->email = $email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
