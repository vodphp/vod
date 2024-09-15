<?php

namespace Vod\Vod;

use Illuminate\Support\Arr;
use JsonSerializable;
use Vod\Vod\Types\BaseType;
use Vod\Vod\Types\VObject;

abstract class Vod implements JsonSerializable
{
    public function __construct(
        protected mixed $input
    ) {}

    /**
     * @return BaseType
     */
    abstract public static function schema();

    public function __invoke(?string $name = null)
    {

        $value = static::schema()->parse($this->input);
        if ($name) {
            return Arr::get($value, $name);
        }
        return $value;
    }

    public function raw()
    {
        return $this->input;
    }

    public function __get(string $name)
    {
        $schema = static::schema();
        assert($schema instanceof VObject);
        $childSchema = $schema->getSchema()[$name];
        return $childSchema->parse($this->input[$name]);
    }


    public function defaults()
    {
        return static::schema()->empty();
    }

    public function jsonSerialize(): mixed
    {
        return $this->__invoke();
    }

    public static function from(mixed $input): static
    {
        // @phpstan-ignore-next-line
        return new static($input);
    }
}
