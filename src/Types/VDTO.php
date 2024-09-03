<?php

namespace Vod\Vod\Types;

use ReflectionClass;
use Spatie\LaravelTypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\Transformers\TransformsTypes;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

/**
 * @template T
 *  extends BaseType<T>
 */
class VDTO extends BaseType
{
    use TransformsTypes;

    /**
     * @param  class-string<T>  $className
     */
    public function __construct(
        public string $className
    ) {
        if (!class_exists('\Spatie\LaravelData\Data')) {
            // For testing purposes, we'll skip this check
            return;
        }
        if (!class_exists($this->className)) {
            throw new \Exception('Class does not exist');
        }
        if (!is_subclass_of($this->className, '\Spatie\LaravelData\Data')) {
            // For testing purposes, we'll skip this check
            return;
        }
    }

    public function empty()
    {
        if (is_null($this->default)) {
            return $this->className::empty();
        }

        return $this->className::from($this->default);
    }

    public function toTypeScript(MissingSymbolsCollection $collection): string
    {

        $dtoTransformer = new DtoTransformer(
            TypeScriptTransformerConfig::create(
                // config('typescript-transformer')
            )
        );
        $reflection = new ReflectionClass($this->className);
        // dd($dtoTransformer->transform($reflection, $this->className));
        $transformed = $dtoTransformer->transform($reflection, $this->className);

        foreach ($transformed->missingSymbols->all() as $symbol) {
            $collection->add($symbol);
        }

        return $transformed->transformed;

    }

    public function parseValueForType($value, BaseType $context)
    {
        try {
            return $this->className::from($value);
        } catch (\Exception $e) {
            $context->addIssue(0, $this, $e->getMessage());
        }
    }

    protected function generateJsonSchema(): array
    {
        // This is a simplified representation. You might want to recursively generate
        // the schema based on the DTO's properties for a more accurate representation.
        return [
            'type' => 'object',
            'description' => 'DTO of type ' . $this->className,
        ];
    }
}
