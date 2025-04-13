<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

class VLiteralMixed extends BaseType
{
    public function __construct(protected mixed $value) {}

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {

        $valueType = match (true) {
            is_string($this->value) => "'$this->value'",
            is_numeric($this->value) => $this->value,
            is_bool($this->value) => $this->value ? 'true' : 'false',
            is_null($this->value) => 'null',
            default => 'any'
        };

        return $valueType . ($this->isOptional() ? ' | null' : '');
    }

    //protected $default = '';

    public function toPhpType(bool $simple = false): string
    {
        if ($simple) {
            return $this->isOptional() ? 'string|null' : 'string';
        }
        return  'mixed' . ($this->isOptional() ? '|null' : '');
    }

    public function parseValueForType($value, BaseType $context)
    {
        if ($value !== $this->value) {
            VParseException::throw('Value ' . json_encode($value) . ' is not ' . $this->value, $context, $value);
        }

        return $value;
    }

    public function empty()
    {
        return $this->value;
    }


    protected function generateJsonSchema(): array
    {

        $type = match (true) {
            is_string($this->value) => 'string',
            is_numeric($this->value) => 'number',
            is_bool($this->value) => 'boolean',
            is_null($this->value) => 'null',
            default => 'any'
        };
        return [
            'type' => $type,
            'enum' => [$this->value],
        ];
    }
}
