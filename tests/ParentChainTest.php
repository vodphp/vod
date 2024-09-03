<?php

use function Vod\Vod\v;

it('sets parent chain when parsing', function () {
    $schema = v()->object([
        'name' => v()->string(),
        'age' => v()->number(),
        'hobbies' => v()->array(v()->string()),
    ]);

    $schema->parse(['name' => 'John', 'age' => 30, 'hobbies' => ['reading', 'swimming']]);

    expect($schema->getParent())->toBeNull();
    expect($schema->getSchema()['name']->getParent())->toBe($schema);
    expect($schema->getSchema()['age']->getParent())->toBe($schema);
    expect($schema->getSchema()['hobbies']->getParent())->toBe($schema);
    expect($schema->getSchema()['hobbies']->getSchema()->getParent())->toBe($schema->getSchema()['hobbies']);
});

it('sets parent chain when generating TypeScript', function () {
    $schema = v()->object([
        'name' => v()->string(),
        'age' => v()->number(),
        'hobbies' => v()->array(v()->string()),
    ]);

    $schema->toTypeScript(new Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection);

    expect($schema->getParent())->toBeNull();
    expect($schema->getSchema()['name']->getParent())->toBe($schema);
    expect($schema->getSchema()['age']->getParent())->toBe($schema);
    expect($schema->getSchema()['hobbies']->getParent())->toBe($schema);
    expect($schema->getSchema()['hobbies']->getSchema()->getParent())->toBe($schema->getSchema()['hobbies']);
});

it('sets parent chain when generating JSON schema', function () {
    $schema = v()->object([
        'name' => v()->string(),
        'age' => v()->number(),
        'hobbies' => v()->array(v()->string()),
    ]);

    $schema->toJsonSchema();

    expect($schema->getParent())->toBeNull();
    expect($schema->getSchema()['name']->getParent())->toBe($schema);
    expect($schema->getSchema()['age']->getParent())->toBe($schema);
    expect($schema->getSchema()['hobbies']->getParent())->toBe($schema);
    expect($schema->getSchema()['hobbies']->getSchema()->getParent())->toBe($schema->getSchema()['hobbies']);
});

it('sets parent chain for deeply nested structures', function () {
    $schema = v()->object([
        'user' => v()->object([
            'name' => v()->string(),
            'address' => v()->object([
                'street' => v()->string(),
                'city' => v()->string(),
            ]),
        ]),
        'orders' => v()->array(v()->object([
            'id' => v()->number(),
            'items' => v()->array(v()->string()),
        ])),
    ]);

    $schema->parse([
        'user' => [
            'name' => 'John',
            'address' => [
                'street' => 'Main St',
                'city' => 'New York',
            ],
        ],
        'orders' => [
            ['id' => 1, 'items' => ['book', 'pen']],
            ['id' => 2, 'items' => ['notebook']],
        ],
    ]);

    expect($schema->getParent())->toBeNull();
    expect($schema->getSchema()['user']->getParent())->toBe($schema);
    expect($schema->getSchema()['user']->getSchema()['name']->getParent())->toBe($schema->getSchema()['user']);
    expect($schema->getSchema()['user']->getSchema()['address']->getParent())->toBe($schema->getSchema()['user']);
    expect($schema->getSchema()['user']->getSchema()['address']->getSchema()['street']->getParent())->toBe($schema->getSchema()['user']->getSchema()['address']);
    expect($schema->getSchema()['orders']->getParent())->toBe($schema);
    expect($schema->getSchema()['orders']->getSchema()->getParent())->toBe($schema->getSchema()['orders']);
    expect($schema->getSchema()['orders']->getSchema()->getSchema()['id']->getParent())->toBe($schema->getSchema()['orders']->getSchema());
    expect($schema->getSchema()['orders']->getSchema()->getSchema()['items']->getParent())->toBe($schema->getSchema()['orders']->getSchema());
    expect($schema->getSchema()['orders']->getSchema()->getSchema()['items']->getSchema()->getParent())->toBe($schema->getSchema()['orders']->getSchema()->getSchema()['items']);
});
