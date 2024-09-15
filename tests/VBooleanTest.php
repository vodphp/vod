<?php

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Types\VBoolean;

use function Vod\Vod\v;

it('VBoolean()', function () {
    expect(v()->boolean())->toBeInstanceOf(VBoolean::class);
    expect(v()->boolean()->parse(true))->toBe(true);
    expect(v()->boolean()->parse(false))->toBe(false);
    expect(v()->boolean()->optional()->parse(null))->toBeNull();
    expect(fn () => v()->boolean()->parse('true'))->toThrow(Exception::class);
    expect(fn () => v()->boolean()->parse(1))->toThrow(Exception::class);
    expect(fn () => v()->boolean()->parse([]))->toThrow(Exception::class);

    expect(v()->boolean()->safeParse('true')['errors'])->toBe('Value "true" is not a boolean');
    expect(v()->boolean()->safeParse('true')['issues'])->toBeArray()->toHaveLength(1);
    expect(v()->boolean()->toTypeScript(new MissingSymbolsCollection))->toBe('boolean');
});
