<?php

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Types\VNumber;
use Vod\Vod\Types\VString;
use Vod\Vod\V;

use function Vod\Vod\v;

it('can test', function () {
    expect(true)->toBeTrue();
});


it('v is V', function () {
    expect(v())->toBeInstanceOf(V::class);
});

it('VString()', function () {
    expect(v()->string())->toBeInstanceOf(VString::class);
    expect(v()->string()->optional()->parse('123'))->toBe('123');
    expect(v()->string()->optional()->parse(null))->toBeNull();
    expect(v()->string()->optional()->parse(123))->toBe(null);
    expect(fn() => v()->string()->parse(123))->toThrow(Exception::class);
    expect(fn() => v()->string()->parse([]))->toThrow(Exception::class);
    expect(fn() => v()->string()->parse(new stdClass()))->toThrow(Exception::class);

    expect(v()->string()->safeParse(new stdClass())['errors'])->toBe("Value is not a string");
    expect((v()->string()->safeParse(new stdClass())['issues']))->toBeArray()->toHaveLength(1);

    expect(v()->string()->default('123')->optional()->parse(null))->toBe('123');
    //expect(v()->string()->default('123')->toTypeScript(new MissingSymbolsCollection()))->toBe('string');
});
