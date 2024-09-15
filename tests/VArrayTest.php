<?php

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Types\VArray;

use function Vod\Vod\v;

it('VArray()', function () {
    expect(v()->array())->toBeInstanceOf(VArray::class);
    expect(v()->array(v()->string())->parse(['a', 'b', 'c']))->toBe(['a', 'b', 'c']);
    expect(v()->array(v()->number())->parse([1, 2, 3]))->toBe([1, 2, 3]);
    expect(v()->array()->optional()->parse(null))->toBeNull();
    expect(fn () => v()->array(v()->string())->parse([1, 2, 3]))->toThrow(Exception::class);
    expect(fn () => v()->array()->parse('not an array'))->toThrow(Exception::class);

    expect(v()->array()->safeParse('not an array')['errors'])->toBe('Value "not an array" is not an array');
    expect(v()->array()->safeParse('not an array')['issues'])->toBeArray()->toHaveLength(1);
    expect(v()->array(v()->string())->toTypeScript(new MissingSymbolsCollection))->toBe('string[]');
    expect(v()->array(v()->string())->and(v()->number())->toTypeScript(new MissingSymbolsCollection))->toBe('(string[] & number)');
    expect(v()->string()->or(v()->number())->toTypeScript(new MissingSymbolsCollection))->toBe('(string | number)');
    expect(v()->string()->or(v()->number()->optional())->toTypeScript(new MissingSymbolsCollection))->toBe('(string | number | null)');
});
