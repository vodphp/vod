<?php

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Types\VEnum;

use function Vod\Vod\v;

it('VEnum()', function () {
    $enum = v()->enum(['red', 'green', 'blue']);

    expect($enum)->toBeInstanceOf(VEnum::class);
    expect($enum->parse('red'))->toBe('red');
    expect($enum->parse('green'))->toBe('green');
    expect($enum->parse('blue'))->toBe('blue');
    expect($enum->optional()->parse(null))->toBeNull();
    $enum->required();
    expect(fn () => $enum->parse('yellow'))->toThrow(Exception::class);
    expect(fn () => $enum->parse(1))->toThrow(Exception::class);

    expect($enum->safeParse('yellow')['errors'])->toBe('Value is not a valid enum member');
    expect($enum->safeParse('yellow')['issues'])->toBeArray()->toHaveLength(1);

    expect($enum->toTypeScript(new MissingSymbolsCollection))->toBe("'red' | 'green' | 'blue'");
});

it('VEnum() with mixed types', function () {
    $enum = v()->enum(['red', 1, true]);

    expect($enum->parse('red'))->toBe('red');
    expect($enum->parse(1))->toBe(1);
    expect($enum->parse(true))->toBe(true);

    expect(fn () => $enum->parse('1'))->toThrow(Exception::class);
    expect(fn () => $enum->parse(false))->toThrow(Exception::class);

    expect($enum->toTypeScript(new MissingSymbolsCollection))->toBe("'red' | 1 | true");
});

it('VEnum() empty()', function () {
    $enum = v()->enum(['red', 'green', 'blue']);
    expect($enum->empty())->toBe(null);

    $emptyEnum = v()->enum([]);
    expect($emptyEnum->empty())->toBeNull();

    $enum = v()->enum(['red', 'green', 'blue'])->default('red')->optional();
    expect($enum->parse(null))->toBe('red');
});
