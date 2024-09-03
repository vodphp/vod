<?php

namespace Vod\Vod\Types;

use Exception;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

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
            throw new Exception('Value is not a boolean');
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
