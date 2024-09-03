<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class VLiteral extends BaseType
{
    public function __construct(protected string $value) {}

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return "'$this->value'".($this->isOptional() ? ' | null' : '');
    }

    //protected $default = '';

    public function parseValueForType($value, BaseType $context)
    {
        if ($value !== $this->value) {
            throw new \Exception('Value is not '.$this->value);
        }

        return $value;
    }

    protected function generateJsonSchema(): array
    {
        return [
            'type' => 'string',
            'enum' => [$this->value],
        ];
    }
}
