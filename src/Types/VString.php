<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

/**
 * @extends BaseType<string>
 * */
class VString extends BaseType
{
    public function parseValueForType($value, BaseType $context)
    {
        if (! is_string($value)) {
            throw new \Exception('Value is not a string');
        }

        return (string) $value;
    }

    public function email(): self
    {
        $this->rules[] = 'email';

        return $this;
    }

    public function url(): self
    {
        $this->rules[] = 'url';

        return $this;
    }

    //protected $default = '';

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return 'string' . ($this->isOptional() ? ' | null' : '');
    }

    public function toJsonSchema(): array
    {
        return parent::toJsonSchema();
    }

    protected function generateJsonSchema(): array
    {
        return ['type' => 'string'];
    }
}
