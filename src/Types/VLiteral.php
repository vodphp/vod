<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

class VLiteral extends BaseType
{
    public function __construct(protected string $value) {}

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return "'$this->value'".($this->isOptional() ? ' | null' : '');
    }

    // protected $default = '';

    public function toPhpType(bool $simple = false): string
    {
        if ($simple) {
            return $this->isOptional() ? 'string|null' : 'string';
        }

        return '"'.$this->value.'"'.($this->isOptional() ? '|null' : '');
    }

    public function parseValueForType($value, BaseType $context)
    {
        if ($value !== $this->value) {
            VParseException::throw('Value '.json_encode($value).' is not '.$this->value, $context, $value);
        }

        return $value;
    }

    public function empty()
    {
        return $this->value;
    }

    protected function generateJsonSchema(): array
    {
        return [
            'type' => 'string',
            'enum' => [$this->value],
        ];
    }
}
