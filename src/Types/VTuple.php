<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class VTuple extends BaseType
{
    public function __construct(private array $types)
    {
        foreach ($this->types as $key => $type) {
            $type->setParent($this, $key);
        }
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        // derive tuple type from $this->types;
        $types = array_map(fn ($type) => $type->exportTypeScript($collection), $this->types);

        return '['.implode(', ', $types).']'.($this->isOptional() ? ' | null' : '');
    }

    public function parseValueForType($value, BaseType $context)
    {
        return $value;
    }

    public function toPhpType(bool $simple = false): string
    {
        if ($simple) {
            return 'array';
        }

        return 'array';
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
