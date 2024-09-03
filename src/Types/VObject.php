<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

/**
 * @extends BaseType<mixed>
 * */
class VObject extends BaseType
{
    protected array $schema;

    protected array $definitions = [];

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
        $this->definitions[$name] = $type;

        return $this;
    }

    public function getDefinition(string $name)
    {
        return $this->definitions[$name];
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
        $ts = "";
        if ($name && !$this->getParent()) {
            $ts .= "export type {$name} = ";
        }

        $ts .= '{ '.implode('; ', $schema).'; }'.($this->isOptional() ? ' | null' : '');
        //Only root object can have definitions
        if (!$this->getParent()) {
            foreach ($this->definitions as $name => $definition) {
                $ts .= PHP_EOL . "export type {$name} = {$definition->toTypeScript($collection)};\n";
            }
        }
        return $ts;
    }

    public function parseValueForType($value, BaseType $context)
    {

        if (! is_array($value)) {
            $context->addIssue(0, $this, 'Not an object');

            return;
        }

        foreach ($this->schema as $key => $type) {
            if (! is_string($key)) {
                throw new \Exception('Keys must be strings');
            }
            if (! ($type instanceof BaseType)) {
                throw new \Exception('Schema values inherit from the BaseType');
            }
        }
        $parsedValue = [];
        foreach ($this->schema as $key => $type) {
            if (! array_key_exists($key, $value)) {
                if (! $type->isOptional()) {
                    $context->addIssue(0, $this, "Required object key  \"$key\" not found");
                }

                $parsedValue[$key] = $type->empty();

                continue;
            }
            $results = $type->safeParse($value[$key], $key);
            if (! $results['ok']) {

                foreach ($results['issues'] as $issue) {

                    $context->addIssue(0, $this, $issue[2]);

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

    public function getSchema(): array
    {
        return $this->schema;
    }
}
