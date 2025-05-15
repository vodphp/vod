<?php

namespace Vod\Vod\Types;

use Illuminate\Contracts\Support\Arrayable;
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

    protected $allowAdditionalProperties = false;

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

    public function allowAdditionalProperties(bool $allow = true)
    {
        $this->allowAdditionalProperties = $allow;

        return $this;
    }

    public function define(string $name, BaseType $type, bool $skipHoisting = false)
    {
        if (! $skipHoisting) {
            $type->setParent($this, $name);
            $type->setParentsRecursively();
        }
        $this->definitions[$name] = $type;
        if (! $skipHoisting) {
            $this->hoistDefinitions();
        }

        return $this;
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function extend(array $schema): self
    {
        $schema = array_merge($this->schema, $schema);
        return new self($schema);
    }

    public function omit(array $keys): self
    {
        $schema = array_diff_key($this->schema, array_flip($keys));
        return new self($schema);
    }

    public function literalProperties(): array
    {
        $properties = [];
        foreach ($this->schema as $key => $type) {
            if ($type instanceof VLiteral) {
                $properties[$key] = $type->empty();
            }
        }

        return $properties;
    }

    public function getDefinition(string $name): ?BaseType
    {
        $parent = $this;
        while ($parent) {
            if ($parent instanceof self) {
                if (isset($parent->getDefinitions()[$name])) {
                    return $parent->getDefinitions()[$name];
                }
            }
            $parent = $parent->getParent();
        }

        return null;
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

    private function getRootParent(): ?self
    {
        $parent = $this;
        $parentObject = null;
        while ($parent) {
            if ($parent instanceof self) {
                $parentObject = $parent;
            }
            $parent = $parent->getParent();
        }

        return $parentObject;
    }

    public function toTypeScript(MissingSymbolsCollection $collection, ?string $name = null): string
    {
        $this->setParentsRecursively();
        $schema = [];
        foreach ($this->schema as $key => $type) {
            if ($type instanceof VRef) {
                $part = "$key: {$type->getName()}";
            } else {
                $part = "$key: {$type->exportTypeScript($collection)}";
            }
            $schema[] = $part;
        }
        if ($this->allowAdditionalProperties) {
            $schema[] = '[key: string]: any';
        }
        $ts = '';
        if ($name && ! $this->getParent()) {
            $ts .= "export type {$name} = ";
        }

        $ts .= '{ ' . implode('; ', $schema) . '; }' . ($this->isOptional() ? ' | null' : '');
        // Only root object can have definitions
        if (! $this->getParent()) {
            foreach ($this->definitions as $name => $definition) {
                $ts .= PHP_EOL . "export type {$name} = {$definition->exportTypeScript($collection)};";
            }
        } else {
            $this->hoistDefinitions();
        }

        return $ts;
    }

    public function hoistDefinitions()
    {
        $rootParent = $this->getRootParent();
        $isTopLevel = $this->getParent() === null;
        if (! $isTopLevel) {
            foreach ($this->definitions as $name => $definition) {
                $rootParent->define($name, $definition, true);
            }
        }

        return $isTopLevel;
    }

    public function parseValueForType($value, BaseType $context)
    {
        $this->setParentsRecursively();
        if (! is_array($value) && $value instanceof Arrayable) {
            $value = $value->toArray();
        }
        if (! is_array($value)) {
            VParseException::throw('Value ' . json_encode($value) . ' is not an object', $this, $value);

            return;
        }

        foreach ($this->schema as $key => $type) {
            // @phpstan-ignore-next-line
            if (! is_string($key)) {
                VParseException::throw('Keys ' . json_encode($key) . ' must be strings', $this, $value);
            }
            // @phpstan-ignore-next-line
            if (! ($type instanceof BaseType)) {
                VParseException::throw('Schema values inherit from the BaseType, ' . json_encode($type) . ' found', $this, $value);
            }
        }
        $parsedValue = [];
        foreach ($this->schema as $key => $type) {
            if (! array_key_exists($key, $value)) {
                if (! $type->isOptional()) {
                    VParseException::throw("Required object key \"$key\" not found", $type, $value);
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
        if ($this->allowAdditionalProperties) {
            $parsedValue = array_merge($value, $parsedValue);
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
        if ($this->allowAdditionalProperties) {
            // allow all keys
            $properties['additionalProperties'] = true;
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

        if ($this->definitions && $this->getParent() === null) {
            foreach ($this->definitions as $name => $definition) {
                $schema['$defs'][$name] = $definition->toJsonSchema();
            }
        }

        return $schema;
    }

    public static int $RECURSION_COUNT = 0;

    public function toPhpType(bool $simple = false): string
    {
        if ($simple) {
            return 'array|\\' . Arrayable::class;
        }

        $typeDef = 'array{';
        self::$RECURSION_COUNT++;
        $props = [];
        foreach ($this->schema as $key => $type) {
            $props[] = $key . ':' . $type->toPhpType(self::$RECURSION_COUNT > 10);
        }
        $typeDef .= implode(',', $props) . '}';
        $typeDef .= '|\\' . Arrayable::class;
        if ($this->isOptional()) {
            $typeDef .= '|null';
        }
        self::$RECURSION_COUNT--;

        return $typeDef;
    }

    protected function setParentsRecursively()
    {
        foreach ($this->schema as $key => $type) {
            $type->setParent($this, $key);
            $type->setParentsRecursively();
        }
        foreach ($this->definitions as $key => $definition) {
            $definition->setParent($this, $key);
            $definition->setParentsRecursively();
            $this->hoistDefinitions();
        }
    }
}
