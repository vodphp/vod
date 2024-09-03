<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class VIntersection extends BaseType
{
    /**
     * @param BaseType[] $types
     */
    public function __construct(public array $types)
    {

    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return '(' . implode(' & ', array_map(fn (BaseType $type) => $type->toTypeScript($collection), $this->types)) . ')' . ($this->isOptional() ? ' | null' : '');
    }

    public function parseValueForType($value, BaseType $context)
    {
        foreach ($this->types as $type) {
            $type->parseValueForType($value, $context);
        }
        return $value;
    }

    protected function generateJsonSchema(): array
    {
        return [
            'allOf' => array_map(fn(BaseType $type) => $type->toJsonSchema(), $this->types),
        ];
    }
}
