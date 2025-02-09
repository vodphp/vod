<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class VIntersection extends BaseType
{
    /**
     * @param  BaseType[]  $types
     */
    public function __construct(public array $types) {
        foreach ($this->types as $type) {
            $type->setParent($this);
        }
    }

    public function toPhpType(bool $simple = false): string
    {
        if ($simple) {
            return 'mixed';
        }
        return implode('&', array_map(fn (BaseType $type) => $type->toPhpType(), $this->types));
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return '('.implode(' & ', array_map(fn (BaseType $type) => $type->exportTypeScript($collection), $this->types)).')'.($this->isOptional() ? ' | null' : '');
    }

    public function parseValueForType($value, BaseType $context)
    {
        foreach ($this->types as $type) {
            $type->parseValueForType($value, $context);
        }

        return $value;
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
            'allOf' => array_map(fn (BaseType $type) => $type->toJsonSchema(), $this->types),
        ];
    }
}
