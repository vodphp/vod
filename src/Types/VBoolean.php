<?php

namespace Vod\Vod\Types;

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
            VParseException::throw('Value '.json_encode($value).' is not a boolean', $this, $value);
        }

        return $value;
    }

    public function toPhpType(bool $simple = false): string
    {
        return $this->isOptional() ? 'bool|null' : 'bool';
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
