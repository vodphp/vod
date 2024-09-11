<?php

namespace Vod\Vod;

use JsonSerializable;
use Vod\Vod\Types\BaseType;

abstract class Vod implements JsonSerializable
{
    public function __construct(
        protected array $input = []
    ) {}

    /**
     * @return BaseType
     */
    abstract public function v();

    public function __invoke()
    {
        return $this->v()->parse($this->input);
    }

    public function defaults()
    {
        return $this->v()->empty();
    }

    public function jsonSerialize(): mixed
    {
        return $this->__invoke();
    }

    public static function from(array $input): static
    {
        // @phpstan-ignore-next-line
        return new static($input);
    }
}
