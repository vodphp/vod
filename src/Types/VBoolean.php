<?php

namespace Vod\Vod\Types;

use Exception;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

/**
 * @extends BaseType<bool>
 * */
class VBoolean extends BaseType
{
    public function parseValueForType($value, BaseType $context)
    {
        if ($this->isOptional() && $value === null) {
            return null;
        }

        if (! is_bool($value)) {
            VParseException::throw('Value is not a boolean', $this, $value);
        }

        return $value;
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return 'boolean'.($this->isOptional() ? ' | null' : '');
    }

    protected function generateJsonSchema(): array
    {
        $schema = ['type' => 'boolean'];

        return $this->addDescriptionToSchema($schema);
    }
}
