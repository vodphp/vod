<?php

namespace Vod\Vod\Types;

use Closure;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

/**
 *  extends BaseType<mixed>
 */
class VDeferred extends BaseType
{
    private BaseType $type;

    public function __construct(BaseType $type)
    {
        $this->type = $type->optional();
        $this->type->setParent($this);
    }

    public function toPhpType(bool $simple = false): string
    {
        return '\Inertia\DeferProp';
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return $this->type->toTypeScript($collection);
    }

    public function parseValueForType($value, BaseType $context)
    {
        if ($value instanceof Closure) {
            $value = $value();
        }
        if (is_object($value) && is_a($value, 'Inertia\DeferProp', true)) {
            return inertia()->defer(function () use ($value, $context) {
                return $this->type->parseValueForType($value(), $context);
            }, $value->group());
        }
        return $this->type->parseValueForType($value, $context);
    }

    public function toJsonSchema(): array
    {
        return $this->type->toJsonSchema();
    }

    protected function generateJsonSchema(): array
    {
        return $this->type->generateJsonSchema();
    }
}
