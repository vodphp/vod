<?php

namespace Vod\Vod;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Vod\Vod\Types\BaseType;
use Vod\Vod\Types\VEnum;
use Vod\Vod\Types\VObject;

/**
 * @implements Arrayable<array-key, mixed>
 */
abstract class Vod implements Arrayable, ArrayAccess, JsonSerializable, Stringable
{
    private $cachedValue;

    final public function __construct(
        protected $input
    ) {
        // force the value to be cached and throw an error if it fails.
        $this->__invoke();
    }

    /**
     * @return BaseType
     */
    abstract public static function schema();

    public function __invoke(?string $name = null)
    {
        if (! $this->cachedValue) {
            $this->cachedValue = static::schema()->parse($this->input);
        }

        $value = $this->cachedValue;
        if ($name) {
            return Arr::get($value, $name);
        }

        return $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->input[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->__set($offset, null);
    }

    public static function fromRequest(Request $request): static
    {
        return new static($request->all());
    }

    public function raw()
    {
        return $this->input;
    }

    public function value()
    {
        return $this->__invoke();
    }

    public function __get(string $name)
    {
        $schema = static::schema();
        if ($schema instanceof VObject) {
            $childSchema = $schema->getSchema()[$name];

            return $childSchema->parse($this->input[$name] ?? null);
        }

        $value = $schema->parse($this->input ?? null);

        return data_get($value, $name);
    }

    public function __set(string $name, mixed $value)
    {
        $schema = static::schema();
        $this->cachedValue = null;
        assert($schema instanceof VObject);
        $childSchema = $schema->getSchema()[$name];
        $this->input[$name] = $childSchema->parse($value);
    }

    public function toArray(): array
    {
        $value = $this->__invoke();
        if (is_array($value)) {
            return $value;
        }
        throw new \Exception('Expected array, got '.gettype($value));
    }

    public function defaults()
    {
        return static::schema()->empty();
    }

    public function jsonSerialize(): mixed
    {
        return $this->__invoke();
    }

    public function __toString(): string
    {
        return (string) $this->__invoke();
    }

    public static function __callStatic(string $name, array $arguments): static
    {
        $schema = static::schema();
        if ($schema instanceof VEnum && $schema->parse($name)) {
            return new static($name);
        }
        throw new \Exception('Invalid call to '.static::class.'::'.$name);
    }

    /**
     * @return static
     */
    public static function from(mixed ...$input)
    {
        if (static::schema() instanceof VEnum) {
            return new static($input[0]);
        }

        return new static($input);
    }

    public static function toStub()
    {
        $schema = static::schema();
        $reflector = new ReflectionClass(static::class);
        $name = $reflector->getName();
        $namespace = $reflector->getNamespaceName();
        $class = $reflector->getShortName();
        $statics = [];
        if ($schema instanceof VEnum) {
            foreach ($schema->values() as $value) {
                $statics[] = '  * @method static self '.$value.'()';
            }
        }
        $statics = implode("\n", $statics);
        $properties = [];
        $fromArgsDocBlock = [];
        $fromArgs = [];
        if ($schema instanceof VObject) {
            $schemaProps = [];
            foreach ($schema->getSchema() as $name => $type) {
                $schemaProps[] = [$name, $type];
            }
            // sort $schemaProps, put isOptional at the end
            usort($schemaProps, function ($a, $b) {
                return $a[1]->isOptional() - $b[1]->isOptional();
            });
            foreach ($schemaProps as [$name, $type]) {
                $properties[] = '* @property '.$type->toPhpType().' $'.$name;
                $fromArgsDocBlock[] = '* @param '.$type->toPhpType().' $'.$name;
                $fromArgs[] = $type->toPhpType(simple: true).' $'.$name.($type->isOptional() ? ' = null' : '');
            }
        }
        if ($schema instanceof VEnum) {
            $fromArgsDocBlock[] = '* @param '.$schema->toPhpType().' $value ';
            $fromArgs[] = 'string $value';
        }

        $properties = implode("\n", $properties);
        $fromArgsDocBlock = implode("\n", $fromArgsDocBlock);
        $fromArgs = implode(', ', $fromArgs);

        $inputType = $schema->toPhpType();
        $inputTypeSimple = $schema->toPhpType(simple: true);

        return <<<PHP
        namespace {$namespace} {
            /**
             *
            {$statics}
            {$properties}
            *
            */
            class {$class}
            {
                /**
                 * @param {$inputType} \$input
                 */
                public function __construct(protected {$inputTypeSimple} \$input) {}
                /**
                {$fromArgsDocBlock}
                **/
                public static function from($fromArgs): static {
                    return new static(count(func_get_args()) > 1 ? func_get_args() : func_get_args()[0]);
                }
            }
        }
        PHP;
    }
}
