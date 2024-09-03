<?php

use Vod\Vod\Types\VArray;
use Vod\Vod\Types\VBoolean;
use Vod\Vod\Types\VNumber;
use Vod\Vod\Types\VObject;
use Vod\Vod\Types\VString;
use Vod\Vod\V;

use function Vod\Vod\v;

it('V()', function () {
    expect(v())->toBeInstanceOf(V::class);
});

it('V can infer types', function () {
    expect(v()->infer('string'))->toBeInstanceOf(VString::class);
    expect(v()->infer(123))->toBeInstanceOf(VNumber::class);
    expect(v()->infer(true))->toBeInstanceOf(VBoolean::class);
    expect(v()->infer([]))->toBeInstanceOf(VObject::class);
    expect(v()->infer([1, 2, 3]))->toBeInstanceOf(VArray::class);
    expect(v()->infer(new stdClass))->toBeInstanceOf(VObject::class);
    expect(v()->infer(['a' => 1, 'b' => 2]))->toBeInstanceOf(VObject::class);
});

it('can add a rule', function() {
    expect(v()->string()->rules(['email'])->parse('dean@example.com'))->toBe('dean@example.com');
    expect(fn() => v()->string()->rules('email')->parse('not an email'))->toThrow(Exception::class);
});

it('V can parse values', function () {
    expect(v()->string()->parse('hello'))->toBe('hello');
    expect(v()->number()->parse(123))->toBe(123);
    expect(v()->boolean()->parse(true))->toBe(true);
    expect(v()->object([
        'a' => v()->number(),
        'b' => v()->number(),
        'c' => v()->string()->optional(),
    ])->parse(['a' => 1, 'b' => 2, 'notme' => 3]))->toBe(['a' => 1, 'b' => 2, 'c' => null]);
    expect(v()->array()->parse([1, 2, 3]))->toBe([1, 2, 3]);
});

it('V can refine values', function () {
    expect(v()->string()->transform(fn ($value) => $value === 'hello')->parse('hello'))->toBe(true);
    expect(v()->string()->transform(fn ($value) => $value === 'hello')->parse('world'))->toBe(false);
});

it('can generate JSON schema for OpenAI with optional fields', function () {
    $schema = v()->object([
        'name' => v()->string(),
        'age' => v()->number()->int(),
        'is_student' => v()->boolean(),
        'hobbies' => v()->array(v()->string())->optional(),
        'address' => v()->object([
            'street' => v()->string(),
            'city' => v()->string(),
            'zip' => v()->string()->optional(),
        ])->optional(),
    ]);

    $expectedJsonSchema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
            'is_student' => ['type' => 'boolean'],
            'hobbies' => [
                'oneOf' => [
                    [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    ['type' => 'null'],
                ],
            ],
            'address' => [
                'oneOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'street' => ['type' => 'string'],
                            'city' => ['type' => 'string'],
                            'zip' => [
                                'oneOf' => [
                                    ['type' => 'string'],
                                    ['type' => 'null'],
                                ],
                            ],
                        ],
                        'required' => ['street', 'city'],
                    ],
                    ['type' => 'null'],
                ],
            ],
        ],
        'required' => ['name', 'age', 'is_student'],
    ];

    expect($schema->toJsonSchema())->toBe($expectedJsonSchema);
});

it('can generate JSON schema for VAny', function () {
    $schema = v()->any();
    expect($schema->toJsonSchema())->toBe([]);
});

it('can generate JSON schema for VDTO', function () {
    class TestDTO {}
    $schema = v()->dto(TestDTO::class);
    expect($schema->toJsonSchema())->toBe([
        'type' => 'object',
        'description' => 'DTO of type TestDTO',
    ]);
});

it('can generate JSON schema for VDate', function () {
    $schema = v()->date();
    expect($schema->toJsonSchema())->toBe([
        'type' => 'string',
        'format' => 'date-time',
    ]);
});

it('can generate JSON schema for VEnum', function () {
    $schema = v()->enum(['red', 'green', 'blue']);
    expect($schema->toJsonSchema())->toBe([
        'enum' => ['red', 'green', 'blue'],
    ]);
});

it('can generate JSON schema for VIntersection', function () {
    $schema = v()->string()->and(v()->number());
    expect($schema->toJsonSchema())->toBe([
        'allOf' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
    ]);
});

it('can generate JSON schema for VTuple', function () {
    $schema = v()->tuple([v()->string(), v()->number(), v()->boolean()]);
    expect($schema->toJsonSchema())->toBe([
        'type' => 'array',
        'items' => [
            ['type' => 'string'],
            ['type' => 'number'],
            ['type' => 'boolean'],
        ],
        'minItems' => 3,
        'maxItems' => 3,
    ]);
});

it('can generate JSON schema for VUnion', function () {
    $schema = v()->string()->or(v()->number());
    expect($schema->toJsonSchema())->toBe([
        'oneOf' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
    ]);
});

it('can generate JSON schema for optional types', function () {
    $schema = v()->string()->optional();
    expect($schema->toJsonSchema())->toBe([
        'oneOf' => [
            ['type' => 'string'],
            ['type' => 'null'],
        ],
    ]);
});

it('can generate JSON schema for complex nested structures', function () {
    $schema = v()->object([
        'name' => v()->string(),
        'age' => v()->number()->int(),
        'hobbies' => v()->array(v()->string())->optional(),
        'address' => v()->object([
            'street' => v()->string(),
            'city' => v()->string(),
            'country' => v()->enum(['USA', 'Canada', 'UK'])->optional(),
        ])->optional(),
        'tags' => v()->tuple([v()->string(), v()->number()]),
        'metadata' => v()->any(),
    ]);

    expect($schema->toJsonSchema())->toBe([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
            'hobbies' => [
                'oneOf' => [
                    [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    ['type' => 'null'],
                ],
            ],
            'address' => [
                'oneOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'street' => ['type' => 'string'],
                            'city' => ['type' => 'string'],
                            'country' => [
                                'oneOf' => [
                                    ['enum' => ['USA', 'Canada', 'UK']],
                                    ['type' => 'null'],
                                ],
                            ],
                        ],
                        'required' => ['street', 'city'],
                    ],
                    ['type' => 'null'],
                ],
            ],
            'tags' => [
                'type' => 'array',
                'items' => [
                    ['type' => 'string'],
                    ['type' => 'number'],
                ],
                'minItems' => 2,
                'maxItems' => 2,
            ],
            'metadata' => [],
        ],
        'required' => ['name', 'age', 'tags', 'metadata'],
    ]);
});

it('can generate JSON schema with descriptions', function () {
    $schema = v()->object([
        'name' => v()->string()->description('The user\'s full name'),
        'age' => v()->number()->int()->description('The user\'s age in years'),
        'is_student' => v()->boolean()->description('Whether the user is a student'),
        'hobbies' => v()->array(v()->string())->optional()->description('List of user\'s hobbies'),
        'address' => v()->object([
            'street' => v()->string()->description('Street name and number'),
            'city' => v()->string()->description('City name'),
            'zip' => v()->string()->optional()->description('Postal code'),
        ])->optional()->description('User\'s address information'),
    ])->description('User information schema');

    $expectedJsonSchema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string', 'description' => 'The user\'s full name'],
            'age' => ['type' => 'integer', 'description' => 'The user\'s age in years'],
            'is_student' => ['type' => 'boolean', 'description' => 'Whether the user is a student'],
            'hobbies' => [
                'oneOf' => [
                    [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'List of user\'s hobbies',
                    ],
                    ['type' => 'null'],
                ],
            ],
            'address' => [
                'oneOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'street' => ['type' => 'string', 'description' => 'Street name and number'],
                            'city' => ['type' => 'string', 'description' => 'City name'],
                            'zip' => [
                                'oneOf' => [
                                    ['type' => 'string', 'description' => 'Postal code'],
                                    ['type' => 'null'],
                                ],
                            ],
                        ],
                        'required' => ['street', 'city'],
                        'description' => 'User\'s address information',
                    ],
                    ['type' => 'null'],
                ],
            ],
        ],
        'required' => ['name', 'age', 'is_student'],
        'description' => 'User information schema',
    ];

    expect($schema->toJsonSchema())->toBe($expectedJsonSchema);
});
