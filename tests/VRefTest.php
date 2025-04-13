<?php

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

use function Vod\Vod\v;

it('VRef can be created', function () {
    $root = v()->object([
        'exampleOfRef' => v()->ref('ref01'),
        'blocks' => v()->array(v()->object([
            'test' => v()->string(),
            'exampleOfRef' => v()->ref('ref01'),
        ])),
    ])
        ->define('ref01', v()->object([
            'hello' => v()->string(),
            'world' => v()->number(),
        ]));

    expect($root->parse([
        'exampleOfRef' => [
            'hello' => 'world',
            'world' => 123,
        ],
        'blocks' => [
            [
                'test' => 'test',
                'exampleOfRef' => [
                    'hello' => 'world',
                    'world' => 123,
                ],
            ],
        ],
    ]))->toBeArray()->toHaveLength(2);

    expect($root->toTypeScript(new MissingSymbolsCollection, 'Root'))->toBe('export type Root = { exampleOfRef: ref01; blocks: { test: string; exampleOfRef: ref01; }[]; }'.PHP_EOL.'export type ref01 = { hello: string; world: number; };');

});

it('VRef can reference through an array', function () {
    $root = v()->object([
        'exampleOfRef' => v()->ref('ref01')->array(),
    ])
        ->define('ref01', v()->object([
            'hello' => v()->string(),
            'world' => v()->number(),
        ]));

    expect($root->toTypeScript(new MissingSymbolsCollection, 'Root'))->toBe('export type Root = { exampleOfRef: ref01[]; }'.PHP_EOL.'export type ref01 = { hello: string; world: number; };');

    expect($root->parse([
        'exampleOfRef' => [[
            'hello' => 'world',
            'world' => 123,
        ]],
    ]))->toBeArray()->toHaveLength(1);
});

it('VRef can be defined on any object in the chain', function () {
    $root = v()->object([
        'base' => v()->object([
            'hello' => v()->string(),
            'world' => v()->number(),
            'other' => v()->object([
                'reffed' => v()->ref('ref01'),
            ]),
        ])->define('ref01', v()->string()),
    ]);

    expect($root->parse([
        'base' => [
            'hello' => 'world',
            'world' => 123,
            'other' => [
                'reffed' => 'world',
            ],
        ],
    ]))->toBeArray()->toHaveLength(1);

});
