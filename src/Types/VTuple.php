<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class VTuple extends BaseType
{
    public function __construct(private array $types) {}

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        //derive tuple type from $this->types;
        $types = array_map(fn ($type) => $type->toTypeScript($collection), $this->types);

        return '['.implode(', ', $types).']'.($this->isOptional() ? ' | null' : '');
    }

    public function parseValueForType($value, BaseType $context)
    {
        return $value;
    }

    protected function generateJsonSchema(): array
    {
        return [
            'type' => 'array',
            'items' => array_map(fn (BaseType $type) => $type->toJsonSchema(), $this->types),
            'minItems' => count($this->types),
            'maxItems' => count($this->types),
        ];
    }
}
