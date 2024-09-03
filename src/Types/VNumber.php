<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

/**
 * @extends BaseType<float>
 * */
class VNumber extends BaseType
{
    protected bool $isInt = false;

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return 'number'.($this->isOptional() ? ' | null' : '');
    }

    public function int(): self
    {
        $this->isInt = true;
        $this->rules[] = 'int';

        return $this;
    }

    public function float(): self
    {
        $this->isInt = false;
        $this->rules = array_diff($this->rules, ['int']);

        return $this;
    }

    //protected $default = 0;

    public function parseValueForType($value, BaseType $context)
    {
        if (! is_numeric($value) || is_string($value)) {
            throw new \Exception('Value is not a number');
        }
        if ($this->rules) {
            if (in_array('int', $this->rules)) {
                return (int) $value;
            }
        }
        if (is_int($value)) {
            return (int) $value;
        }

        return (float) $value;
    }

    protected function generateJsonSchema(): array
    {
        $schema = ['type' => $this->isInt ? 'integer' : 'number'];

        return $this->addDescriptionToSchema($schema);
    }

    public function toJsonSchema(): array
    {
        return $this->generateJsonSchema();
    }
}
