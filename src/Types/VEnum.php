<?php

namespace Vod\Vod\Types;

use Exception;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

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
            throw new Exception('Value is not a valid enum member');
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
