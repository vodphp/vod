<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class VDate extends BaseType
{
    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return 'string' . ($this->isOptional() ? ' | null' : '');
    }


    //protected $default = '';

    public function parseValueForType($value, BaseType $context)
    {
        if (! is_int($value)) {
            throw new \Exception('Value is not an integer');
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
