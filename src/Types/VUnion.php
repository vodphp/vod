<?php

namespace Vod\Vod\Types;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;

class VUnion extends BaseType
{
    /**
     * @param  BaseType[]  $types
     */
    public function __construct(public array $types, private ?string $discriminatedOn = null)
    {
        $this->setParentsRecursively();
        $this->checkForDiscriminatedProperty();
    }

    public function omit(array $types): self
    {
        $types = array_diff($this->types, $types);
        return new self($types);
    }

    private function checkForDiscriminatedProperty()
    {
        $discriminatedOn = null;

        $literalPropertyGroups = [];

        foreach ($this->types as $type) {
            // Only objects can be discrimated
            if (! ($type instanceof VObject)) {
                $this->discriminatedOn = null;

                return;
            }
            $literalPropertyGroups[] = $type->literalProperties();
        }

        if ($this->discriminatedOn) {
            return;
        }

        if (count($literalPropertyGroups) === 0) {
            return;
        }

        $firstLiteralPropertyGroup = $literalPropertyGroups[0];

        foreach ($firstLiteralPropertyGroup as $key => $value) {
            $keyIsDiscriminated = false;
            foreach ($literalPropertyGroups as $literalPropertyGroup) {
                if (! isset($literalPropertyGroup[$key])) {
                    continue;
                }
                if ($literalPropertyGroup[$key] !== $value) {
                    $keyIsDiscriminated = true;
                }
            }
            if ($keyIsDiscriminated) {
                $discriminatedOn = $key;
                break;
            }
        }

        if ($discriminatedOn) {
            $this->discriminatedOn = $discriminatedOn;
        }
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {
        return '(' . implode(' | ', array_map(fn(BaseType $type) => $type->exportTypeScript($collection), $this->types)) . ')' . ($this->isOptional() ? ' | null' : '');
    }

    public function parseValueForType($value, BaseType $context)
    {
        if (count($this->types) === 1) {
            return $this->types[0]->parseValueForType($value, $context);
        }

        if ($this->discriminatedOn && is_array($value) && isset($value[$this->discriminatedOn])) {
            /** @var VObject $type */
            foreach ($this->types as $type) {
                if ($type->literalProperties()[$this->discriminatedOn] === $value[$this->discriminatedOn]) {
                    return $type->parseValueForType($value, $context);
                }
            }
        }

        foreach ($this->types as $type) {
            try {
                return $type->parseValueForType($value, $context);
            } catch (VParseException $e) {
                continue;
            }
        }
        VParseException::throw('Value ' . json_encode($value) . ' does not match any type in union', $context, $value);
    }

    public function toPhpType(bool $simple = false): string
    {
        return implode('|', array_unique(array_map(fn(BaseType $type) => $type->toPhpType(simple: $simple), $this->types))) . ($this->isOptional() ? '|null' : '');
    }

    protected function setParentsRecursively()
    {
        foreach ($this->types as $key => $type) {
            // @phpstan-ignore-next-line
            if (! ($type instanceof BaseType)) {
                throw new \Exception('Union type is not a BaseType, have you double wrapped an array?');
            }
            $type->setParent($this, '|' . $key . '|');
            $type->setParentsRecursively();
        }
    }

    protected function generateJsonSchema(): array
    {
        return [
            'oneOf' => array_map(fn(BaseType $type) => $type->toJsonSchema(), $this->types),
        ];
    }
}
