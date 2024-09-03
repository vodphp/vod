<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class VUnion extends BaseType
{
    /**
     * @param  BaseType[]  $types
     */
    public function __construct(public array $types) {}

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return '('.implode(' | ', array_map(fn (BaseType $type) => $type->toTypeScript($collection), $this->types)).')'.($this->isOptional() ? ' | null' : '');
    }

    public function parseValueForType($value, BaseType $context)
    {
        foreach ($this->types as $type) {
            try {
                return $type->parseValueForType($value, $context);
            } catch (\Exception $e) {
                continue;
            }
        }
        throw new \Exception('Value does not match any type in union');
    }

    protected function generateJsonSchema(): array
    {
        return [
            'oneOf' => array_map(fn (BaseType $type) => $type->toJsonSchema(), $this->types),
        ];
    }
}
