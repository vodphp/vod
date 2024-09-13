<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

/**
 * @extends BaseType<string>
 * */
class VString extends BaseType
{
    public function parseValueForType($value, BaseType $context)
    {
        if (! is_string($value)) {
            VParseException::throw('Value '.json_encode($value).' is not a string', $context, $value);
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
        return 'string'.($this->isOptional() ? ' | null' : '');
    }

    public function toJsonSchema(): array
    {
        return parent::toJsonSchema();
    }

    protected function generateJsonSchema(): array
    {
        $schema = ['type' => 'string'];

        return $this->addDescriptionToSchema($schema);
    }
}
