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

        if (! is_array($value)) {
            throw new Exception('Value is not an array');
        }

        return array_map(fn ($item) => $this->schema->parse($item), $value);
    }

    public function toTypeScript(MissingSymbolsCollection $missingSymbols): string
    {
        if ($this->schema instanceof VRef) {
            return $this->schema->getName() .'[]'.($this->isOptional() ? ' | null' : '');
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
