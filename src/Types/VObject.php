<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

/**
 * @extends BaseType<mixed>
 * */
class VObject extends BaseType
{
    /**
     * @var array<string, BaseType<mixed>>
     */
    protected array $schema;

    protected array $definitions = [];

    /**
     * @return array<string, BaseType<mixed>>
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param  array<string, BaseType<mixed>>  $schema
     * */
    public function __construct(array $schema)
    {
        $this->schema = $schema;
        $this->setParentsRecursively();
    }

    public function define(string $name, BaseType $type)
    {
        $type->setParent($this);
        $type->setParentsRecursively();
        $this->definitions[$name] = $type;

        return $this;
    }

    public function getDefinition(string $name): ?BaseType
    {
        return $this->definitions[$name] ?? null;
    }

    public function empty()
    {
        if ($this->isOptional()) {
            return parent::empty();
        }

        $empty = [];
        foreach ($this->schema as $key => $type) {
            if ($type->isOptional()) {
                $empty[$key] = null;

                continue;
            }
            $empty[$key] = $type->empty();
        }

        return $empty;
    }

    public function toTypeScript(MissingSymbolsCollection $collection, ?string $name = null): string
    {
        $this->setParentsRecursively();
        $schema = [];
        foreach ($this->schema as $key => $type) {
            if ($type instanceof VRef) {
                $part = "$key: {$type->getName()}";
            } else {
                $part = "$key: {$type->toTypeScript($collection)}";
            }
            $schema[] = $part;
        }
        $ts = '';
        if ($name && ! $this->getParent()) {
            $ts .= "export type {$name} = ";
        }

        $ts .= '{ '.implode('; ', $schema).'; }'.($this->isOptional() ? ' | null' : '');
        //Only root object can have definitions
        if (! $this->getParent()) {
            foreach ($this->definitions as $name => $definition) {
                $ts .= PHP_EOL."export type {$name} = {$definition->toTypeScript($collection)};";
            }
        }

        return $ts;
    }

    public function parseValueForType($value, BaseType $context)
    {
        $this->setParentsRecursively();
        if (! is_array($value)) {
            VParseException::throw('Value '.json_encode($value).' is not an object', $this, $value);

            return;
        }

        foreach ($this->schema as $key => $type) {
            if (! is_string($key)) {
                VParseException::throw('Keys '.json_encode($key).' must be strings', $this, $value);
            }
            if (! ($type instanceof BaseType)) {
                VParseException::throw('Schema values inherit from the BaseType, '.json_encode($type).' found', $this, $value);
            }
        }
        $parsedValue = [];
        foreach ($this->schema as $key => $type) {
            if (! array_key_exists($key, $value)) {
                if (! $type->isOptional()) {
                    VParseException::throw("Required object key \"$key\" not found", $this, $value);
                }

                $parsedValue[$key] = $type->empty();

                continue;
            }
            $results = $type->safeParse($value[$key], $key);
            if (! $results['ok']) {

                foreach ($results['issues'] as $issue) {
                    VParseException::throw($issue[2], $this, $value);

                }
            }
            $parsedValue[$key] = $results['value'] ?? null;
        }

        return $parsedValue;
    }

    protected function generateJsonSchema(): array
    {
        $properties = [];
        $required = [];

        foreach ($this->schema as $key => $type) {
            $propertySchema = $type->toJsonSchema();
            $properties[$key] = $propertySchema;

            if (! $type->isOptional()) {
                $required[] = $key;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (! empty($required)) {
            $schema['required'] = $required;
        }

        return $this->addDescriptionToSchema($schema);
    }

    public function toJsonSchema(): array
    {
        $this->setParentsRecursively();
        $schema = $this->generateJsonSchema();

        if ($this->isOptional()) {
            return [
                'oneOf' => [
                    $this->addDescriptionToSchema($schema),
                    ['type' => 'null'],
                ],
            ];
        }

        return $schema;
    }

    protected function addDescriptionToSchema(array $schema): array
    {
        $schema = parent::addDescriptionToSchema($schema);

        // Add descriptions to nested properties
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                if (isset($property['oneOf'])) {
                    foreach ($property['oneOf'] as &$option) {
                        if (isset($option['type']) && $option['type'] === 'object') {
                            $option = $this->schema[$key]->addDescriptionToSchema($option);
                        }
                    }
                }
            }
        }
        if ($this->definitions) {
            foreach ($this->definitions as $name => $definition) {
                $schema['$defs'][$name] = $definition->toJsonSchema();
            }
        }

        return $schema;
    }

    protected function setParentsRecursively()
    {
        foreach ($this->schema as $type) {
            $type->setParent($this);
            $type->setParentsRecursively();
        }
        foreach ($this->definitions as $definition) {
            $definition->setParent($this);
            $definition->setParentsRecursively();
        }
    }

}
