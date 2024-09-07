<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ValidSponsorCode extends Constraint
{
    public string $message = 'validator.invalid_sponsor_code';
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
