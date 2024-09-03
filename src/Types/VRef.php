<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

/**
 *  extends BaseType<mixed>
 */
class VRef extends BaseType
{
    public function __construct(protected string $refName) {}

    public function getStore(): ?VObject
    {

        $parent = $this->getParent();
        $topStore = null;
        while ($parent !== null) {
            if ($parent instanceof VObject) {
                $topStore = $parent;
            }
            $parent = $parent->getParent();
        }

        return $topStore;
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        $store = $this->getStore();
        if ($store === null) {
            throw new \Exception("Store is not set for VRef '{$this->refName}'");
        }

        return $store->getDefinition($this->refName)->toTypeScript($collection);
    }

    public function parseValueForType($value, BaseType $context)
    {
        $store = $this->getStore();
        if ($store === null) {
            throw new \Exception("Store is not set for VRef '{$this->refName}'");
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
