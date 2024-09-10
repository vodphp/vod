<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

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
            return [];
        }

        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            VParseException::throw('Value is not an array', $this, $value);
        }

        return array_map(fn ($item) => $this->schema->parse($item), $value);
    }

    public function toTypeScript(MissingSymbolsCollection $missingSymbols): string
    {
        if ($this->schema instanceof VRef) {
            return $this->schema->getName().'[]'.($this->isOptional() ? ' | null' : '');
        }

        return $this->schema->toTypeScript($missingSymbols).'[]'.($this->isOptional() ? ' | null' : '');
    }

    protected function generateJsonSchema(): array
    {

        $schema = [
            'type' => 'array',
            'items' => $this->schema->toJsonSchema(),
        ];

        return $this->addDescriptionToSchema($schema);
    }

    protected function setParentsRecursively()
    {
        $this->schema->setParent($this);
        $this->schema->setParentsRecursively();
    }

    public function getSchema(): BaseType
    {
        return $this->schema;
    }
}
