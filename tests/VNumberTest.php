<?php

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Types\VNumber;
use function Vod\Vod\v;


it('VNumber()', function () {
    expect(v()->number())->toBeInstanceOf(VNumber::class);
    expect(v()->number()->optional()->int()->parse(123))->toBe(123);
    expect(v()->number()->optional()->parse(null))->toBeNull();
    expect(v()->number()->optional()->int()->parse('123'))->toBe(null);
    expect(v()->number()->optional()->float()->parse(123.123))->toBe(123.123);
    expect(v()->number()->optional()->int()->parse('123.123'))->toBe(null);
    expect(v()->number()->optional()->float()->parse(123))->toBe(123);
    expect(fn() => v()->number()->parse('123 adsa'))->toThrow(Exception::class);
    expect(fn() => v()->number()->parse([]))->toThrow(Exception::class);
    expect(fn() => v()->number()->parse(new stdClass()))->toThrow(Exception::class);

    expect(v()->number()->safeParse(new stdClass())['errors'])->toBe("Value is not a number");
    expect((v()->number()->safeParse(new stdClass())['issues']))->toBeArray()->toHaveLength(1);
    expect(v()->number()->toTypeScript(new MissingSymbolsCollection()))->toBe('number');
});

