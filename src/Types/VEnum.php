<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Stringable;
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

    public function values(): array
    {
        return $this->values;
    }

    public function parseValueForType($value, BaseType $context)
    {
        if ($value instanceof Stringable) {
            $valueAsString = (string) $value;
        } else {
            $valueAsString = $value;
        }
        if (! in_array($valueAsString, $this->values, true)) {
            VParseException::throw('Value '.json_encode($valueAsString).' is not a valid enum member', $this, $value);
        }

        return $valueAsString;
    }

    public function toPhpType(bool $simple = false): string
    {
        if ($simple) {
            return 'string'.($this->isOptional() ? '|null' : '');
        }

        return implode('|', array_map(function ($value) {
            return "\"{$value}\"";
        }, $this->values)).($this->isOptional() ? '|null' : '');
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
