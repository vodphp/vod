<?php

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;
use Vod\Vod\Types\VObject;

use function Vod\Vod\v;

it('VObject()', function () {
    $schema = [
        'name' => v()->string(),
        'age' => v()->number(),
    ];

    expect(v()->object($schema))->toBeInstanceOf(VObject::class);
    expect(v()->object($schema)->parse(['name' => 'John', 'age' => 30]))->toBe(['name' => 'John', 'age' => 30]);
    expect(v()->object($schema)->optional()->parse(null))->toBeNull();
    expect(fn () => v()->object($schema)->parse(['name' => 'John', 'age' => 'thirty']))->toThrow(Exception::class);
    expect(fn () => v()->object($schema)->parse('not an object'))->toThrow(Exception::class);

    expect(v()->object($schema)->safeParse('not an object')['errors'])->toBe('Value "not an object" is not an object');
    expect(v()->object($schema)->safeParse('not an object')['issues'])->toBeArray()->toHaveLength(1);
    expect(v()->object($schema)->toTypeScript(new MissingSymbolsCollection))->toBe('{ name: string; age: number; }');

    expect(v()->object([
        'name' => v()->string(),
        'age' => v()->number(),
        'address' => v()->object([
            'street' => v()->string(),
            'city' => v()->string(),
            'zip' => v()->string()->optional(),
        ]),
    ])->parse(['name' => 'John', 'age' => 30, 'address' => ['street' => 'Main St', 'city' => 'New York']]))->toBe(['name' => 'John', 'age' => 30, 'address' => ['street' => 'Main St', 'city' => 'New York', 'zip' => null]]);

    expect(v()->object([
        'name' => v()->string(),
        'age' => v()->number(),
        'tags' => v()->array(v()->string()),
        'address' => v()->object([
            'street' => v()->string(),
            'city' => v()->string(),
            'zip' => v()->string()->optional(),
        ]),
    ])->toTypeScript(new MissingSymbolsCollection))->toBe('{ name: string; age: number; tags: string[]; address: { street: string; city: string; zip: string | null; }; }');
});

it('Can support rules', function () {
    $schema = [
        'name' => v()->string()->optional()->rules('doesnt_start_with:A'),
    ];

    expect(v()->object($schema)->parse(['name' => 'John']))->toBe(['name' => 'John']);
    expect(fn () => v()->object($schema)->parse(['name' => 'Adam']))->toThrow(VParseException::class);

});

it('Can support nested rules', function () {
    $schema = [
        'name' => v()->string()->optional(),
        'address' => v()->object([
            'street' => v()->string()->optional(),
        ]),
    ];

    $v = v()->object($schema)
        ->rules(
            [
                'name' => 'doesnt_start_with:A',
                'address.street' => 'doesnt_start_with:B',
            ]);

    expect($v->parse(['name' => 'John', 'address' => ['street' => 'Main St']]))->toBe(['name' => 'John', 'address' => ['street' => 'Main St']]);

    expect(fn () => $v->parse(['name' => 'Adam', 'address' => ['street' => 'Main St']]))->toThrow(VParseException::class);
    expect(fn () => $v->parse(['name' => 'John', 'address' => ['street' => 'Broadway']]))->toThrow(VParseException::class);
});
