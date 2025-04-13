<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

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

    public function toPhpType(bool $simple = false): string
    {
        if ($this->isInt) {
            return $this->isOptional() ? 'int|null' : 'int';
        }

        return $this->isOptional() ? 'float|null' : 'float';
    }

    // protected $default = 0;

    public function parseValueForType($value, BaseType $context)
    {
        if (! is_numeric($value) || is_string($value)) {
            VParseException::throw('Value '.json_encode($value).' is not a number', $context, $value);
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
