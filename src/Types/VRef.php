<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

/**
 *  @extends BaseType<mixed>
 */
class VRef extends BaseType
{
    public function __construct(protected string $refName) {}

    public function getStore(): ?VObject
    {
        $parent = $this->getParent();

        while ($parent !== null) {
            if ($parent instanceof VObject) {
                return $parent;
            }
            $parent = $parent->getParent();
        }

        return null;
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        $store = $this->getStore();
        if ($store === null) {
            VParseException::throw("Store is not set for VRef '{$this->refName}'", $this, $this->refName);
        }

        return $store->getDefinition($this->refName)->exportTypeScript($collection);
    }

    public function toPhpType(bool $simple = false): string
    {
        return $this->getStore()->getDefinition($this->refName)->toPhpType($simple);
    }

    public function parseValueForType($value, BaseType $context)
    {
        $store = $this->getStore();
        if ($store === null) {
            VParseException::throw("Store is not set for VRef '{$this->refName}'", $context, $this->refName);
        }

        if (! $store) {
            return $value;
        }

        if (! $store->getDefinition($this->refName)) {
            throw new \Exception("Definition '{$this->refName}' not found in store");
        }

        return $store->getDefinition($this->refName)->parseValueForType($value, $context);
    }

    public function getName(): string
    {
        return $this->refName;
    }

    public function toJsonSchema(): array
    {
        return parent::toJsonSchema();
    }

    protected function generateJsonSchema(): array
    {
        return [
            '$ref' => '#/$defs/'.$this->refName,
        ];
    }
}
