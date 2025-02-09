<?php

namespace Vod\Vod\TypescriptTransformer;

use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;
use Spatie\TypeScriptTransformer\TypeReflectors\ClassTypeReflector;
use Vod\Vod\Vod;

class VodTypeTransfomer implements Transformer
{
    public function transform(ReflectionClass $class, string $name): ?TransformedType
    {
        if (is_subclass_of($class->getName(), Vod::class, true)) {
            $reflector = ClassTypeReflector::create($class);
            $missingSymbols = new MissingSymbolsCollection;
            try {
                $schema = $class->getName()::schema();
            } catch (\Error $e) {
                print_r($class->getName());

                return null;
            }
            $ts = $class->getName()::schema()->exportTypeScript($missingSymbols);

            return TransformedType::create(
                $reflector->getReflectionClass(),
                $reflector->getName(),
                $ts,
                $missingSymbols
            );
        }

        return null;
    }
}
