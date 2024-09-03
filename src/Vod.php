<?php

namespace Vod\Vod;

use Vod\Vod\Types\BaseType;

abstract class Vod
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

    public static function from(array $input): self
    {
        return new static($input);
    }
}
