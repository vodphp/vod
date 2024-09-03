<?php

namespace Vod\Vod\Types;

use Exception;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

/**
 * @template T
 *
 * @extends BaseType<array<int, T>>
 * */
class VArray extends BaseType
{
    protected BaseType $schema;

    public function __construct(BaseType $schema)
    {
        $this->schema = $schema;
    }

    public function parseValueForType($value, BaseType $context)
    {
        if ($this->isOptional() && $value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new Exception("Value is not an array");
        }

        return array_map(fn($item) => $this->schema->parse($item), $value);
    }

    public function toTypeScript(MissingSymbolsCollection $missingSymbols): string
    {
        return $this->schema->toTypeScript($missingSymbols) . '[]' . ($this->isOptional() ? ' | null' : '');
    }

    protected function generateJsonSchema(): array
    {
        $itemSchema = $this->schema ? $this->schema->toJsonSchema() : [];
        return [
            'type' => 'array',
            'items' => $itemSchema
        ];
    }
}
