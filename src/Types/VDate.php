<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

class VDate extends BaseType
{
    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return 'string'.($this->isOptional() ? ' | null' : '');
    }

    // protected $default = '';

    public function toPhpType(bool $simple = false): string
    {
        return $this->isOptional() ? 'string|null' : 'string';
    }

    public function parseValueForType($value, BaseType $context)
    {
        if (! is_int($value)) {
            VParseException::throw('Value '.json_encode($value).' is not a date', $context, $value);
        }

        return $value;
    }

    protected function generateJsonSchema(): array
    {
        return [
            'type' => 'string',
            'format' => 'date-time',
        ];
    }
}
