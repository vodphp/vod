<?php

namespace Vod\Vod\Exceptions;

use Exception;
use Vod\Vod\Types\BaseType;

class VParseException extends Exception
{
    public ?BaseType $type;

    public mixed $value;

    public function __construct(string $message = 'Value is invalid', int $code = 0, ?\Throwable $previous = null, ?BaseType $type = null, mixed $value = null)
    {

        $schemaPath = $type?->getSchemaPath();
        if ($schemaPath) {
            $message .= ' (' . $schemaPath . ')';
        }
        parent::__construct($message, $code, $previous);
        $this->type = $type;
        $this->value = $value;
    }



    public static function throw(string $message, ?BaseType $type = null, mixed $value = null)
    {
        throw new VParseException(message: $message, type: $type, value: $value);
    }
}
