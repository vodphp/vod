<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

/**
 * @extends BaseType<mixed>
 */
class VEnum extends BaseType
{
    protected array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function parseValueForType($value, BaseType $context)
    {
        if (! in_array($value, $this->values, true)) {
            VParseException::throw('Value is not a valid enum member', $this, $value);
        }

        return $value;
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        $values = array_map(function ($value) {
            if (is_string($value)) {
                return "'{$value}'";
            }
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return $value;
        }, $this->values);

        return implode(' | ', $values).($this->isOptional() ? ' | null' : '');
    }

    protected function generateJsonSchema(): array
    {
        return [
            'enum' => $this->values,
        ];
    }
}
