<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

/**
 * @extends BaseType<mixed>
 * */
class VObject extends BaseType
{
    protected array $schema;

    /**
     * @param  array<string, BaseType<mixed>>  $schema
     * */
    public function __construct(array $schema)
    {
        $this->schema = $schema;
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

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        $schema = [];
        foreach ($this->schema as $key => $type) {
            $part = "$key: {$type->toTypeScript($collection)}";
            $schema[] = $part;
        }

        return '{ '.implode('; ', $schema).'; }'.($this->isOptional() ? ' | null' : '');
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

        return $schema;
    }

    public function toJsonSchema(): array
    {
        return parent::toJsonSchema();
    }
}
