<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;
use Vod\Vod\Vod;

/**
 * @template T of Vod
 *
 * @extends BaseType<T>
 */
class VVod extends BaseType
{
    /**
     * @param  class-string<T>  $className
     */
    public function __construct(
        protected string $className
    ) {
        $this->globalClassReference = $className;
    }

    private function schema(): BaseType
    {
        return $this->className::schema()->setParent($this);
    }

    /**
     * @param  mixed  $value
     * @return T|Vod|class-string
     */
    public function parseValueForType($value, BaseType $context): Vod|string
    {
        $isVod = is_subclass_of($value, Vod::class, true);

        if ($isVod) {
            if (! is_a($value, $this->className, true)) {
                VParseException::throw('Value must be an instance of ' . $this->className, $this, $value);
            }

            return $value;
        }

        return new $this->className($value);
    }

    public function empty()
    {
        return $this->schema()->empty();
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return $this->schema()->toTypeScript($collection) . ($this->isOptional() ? ' | null' : '');
    }

    public function toPhpType(bool $simple = false): string
    {
        return '\\' . ($this->globalClassReference ?? Vod::class) . ($this->isOptional() ? ' | null' : '');
    }

    public function toJsonSchema(): array
    {
        return $this->schema()->toJsonSchema();
    }

    public function generateJsonSchema(): array
    {
        return $this->schema()->generateJsonSchema();
    }
}
