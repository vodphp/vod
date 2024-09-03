<?php

namespace Vod\Vod;

use Vod\Vod\Types\BaseType;
use Vod\Vod\Types\VAny;
use Vod\Vod\Types\VArray;
use Vod\Vod\Types\VBoolean;
use Vod\Vod\Types\VDate;
use Vod\Vod\Types\VDTO;
use Vod\Vod\Types\VEnum;
use Vod\Vod\Types\VIntersection;
use Vod\Vod\Types\VLiteral;
use Vod\Vod\Types\VNumber;
use Vod\Vod\Types\VObject;
use Vod\Vod\Types\VRef;
use Vod\Vod\Types\VString;
use Vod\Vod\Types\VTuple;
use Vod\Vod\Types\VUnion;

class V
{
    public function ref(string $name)
    {
        return new VRef($name);
    }

    public function string()
    {
        return new VString;
    }

    public function dto(string $className)
    {
        return new VDTO($className);
    }

    public function number()
    {
        return new VNumber;
    }

    public function boolean()
    {
        return new VBoolean;
    }

    public function array(?BaseType $schema = null)
    {
        return new VArray($schema ?? $this->any());
    }

    public function any()
    {
        return new VAny;
    }

    public function infer(mixed $type)
    {
        if (is_string($type)) {
            return $this->string()->default($type);
        }
        if (is_int($type)) {
            return $this->number()->default($type);
        }
        if (is_bool($type)) {
            return $this->boolean()->default($type);
        }
        if (is_array($type)) {
            //is associative array?
            if (array_keys($type) !== range(0, count($type) - 1)) {

                return $this->inferObject($type);
            }

            return $this->array();
        }
        if (is_object($type)) {
            if (is_subclass_of($type, BaseType::class)) {
                return $type;
            }

            if (is_subclass_of($type, '\Spatie\LaravelData\Data')) {
                return $this->dto(get_class($type));
            }

            return $this->inferObject($type);
        }

        return $this->any();
    }

    protected function inferObject($object)
    {
        $inferredObject = [];
        foreach ($object as $key => $value) {
            $inferredObject[$key] = $this->infer($value);
        }

        return $this->object($inferredObject);
    }

    public function union(array $types)
    {
        return new VUnion($types);
    }

    public function allOf(array $types)
    {
        return $this->intersection($types);
    }

    public function anyOf(array $types)
    {
        return $this->union($types);
    }

    public function intersection(array $types)
    {
        return new VIntersection($types);
    }

    public function object(array $schema)
    {
        return new VObject($schema);
    }

    public function literal(string $value)
    {
        return new VLiteral($value);
    }

    public function enum(array $values)
    {
        return new VEnum($values);
    }

    public function toJsonSchema(string $name, BaseType $schema)
    {
        return [
            'name' => $name,
            'schema' => $schema->toJsonSchema(),
        ];
    }

    public function date()
    {
        return new VDate;
    }

    public function tuple(array $types)
    {
        return new VTuple($types);
    }
}
