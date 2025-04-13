<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Any;
use Vod\Vod\Exceptions\VParseException;
use Vod\Vod\Vod;

use function Vod\Vod\v;

/**
 * @template T of Vod
 * @extends BaseType<T>
 */

class VVodClass extends BaseType
{
    private function schema(): BaseType
    {
        if (! $this->lastSchema) {
            return v()->any();
        }
        return $this->lastSchema::schema()->setParent($this);
    }
    /**
     * @var class-string<Vod>|null
     */
    private string|null $lastSchema = null;
    /**
     * @param class-string<Vod>|mixed $value
     * @param BaseType $context
     * @return Vod|class-string
     */
    public function parseValueForType($value, BaseType $context): Vod|string
    {

        if (is_string($value) && is_subclass_of($value, Vod::class, true)) {
            $this->lastSchema = $value;
            return $value;
        }
        
        if (! $this->lastSchema) {
            VParseException::throw('No schema Vod class has been set', $this, $value);
        }

        $isVod = is_subclass_of($value, Vod::class);

        if ($isVod) {
            if (! is_a($value, $this->lastSchema, true)) {
                VParseException::throw('Value must be an instance of ' . $this->lastSchema, $this, $value);
            }
            return $value;
        }
        
        return new $this->lastSchema($value);
    }

    public function empty()
    {
        return $this->schema()->empty();
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return $this->schema()->toTypeScript($collection);
    }

    public function toPhpType(bool $simple = false): string
    {
        return "class-string";
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
