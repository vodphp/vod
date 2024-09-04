<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

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
            } catch (VParseException $e) {
                continue;
            }
        }
        VParseException::throw('Value does not match any type in union', $context, $value);
    }

    protected function setParentsRecursively()
    {
        foreach ($this->types as $type) {
            $type->setParent($this);
            $type->setParentsRecursively();
        }
    }

    protected function generateJsonSchema(): array
    {
        return [
            'oneOf' => array_map(fn (BaseType $type) => $type->toJsonSchema(), $this->types),
        ];
    }
}
