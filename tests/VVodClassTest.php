<?php

use Vod\Vod\Exceptions\VParseException;
use Vod\Vod\Types\VObject;
use Vod\Vod\Vod;

use function Vod\Vod\v;

class TestSchema extends Vod
{
    public static function schema(): VObject
    {
        return v()->object([
            'name' => v()->string(),
        ]);
    }
}

class Test2Schema extends Vod
{
    public static function schema(): VObject
    {
        return v()->object([
            'blah' => v()->string(),
        ]);
    }
}

it('can be used without a class', function () {
    $testSchema = v()->vodClass();

    $testItem = [
        'name' => v()->string(),
    ];

    $testItem2 = new TestSchema([
        'name' => 'test 123',
    ]);

    expect(fn () => $testSchema->parse($testItem))->toThrow(VParseException::class, 'No schema Vod class has been set', 'Calling parse before a schema is set throws an exception');
    expect(fn () => $testSchema->parse($testItem2))->toThrow(VParseException::class, 'No schema Vod class has been set', 'Calling parse before a schema is set throws an exception');

    expect($testSchema->parse(TestSchema::class))->toBeString()->toBe(TestSchema::class, 'Calling parse with a class name returns the class name, and sets this as the schema for future calls');

    expect($testSchema->parse([
        'name' => 'test 123',
    ]))->toBeInstanceOf(TestSchema::class, 'Calling parse with a parsable value after a schema is set will return a validated instance of the schema');

    $test2 = $testSchema->parse(
        $testItem2
    );
    expect($test2
    )
        ->toBe($testItem2)
        ->toBeInstanceOf(TestSchema::class, 'Calling parse with an instance of the schema returns the same instance of the schema');

    $testSchema2Value = [
        'blah' => 'test 123',
    ];

    expect(fn () => $testSchema->parse($testSchema2Value))->toThrow(VParseException::class);

    expect($testSchema->parse(Test2Schema::class))->toBeString()->toBe(Test2Schema::class);

    expect($testSchema->parse($testSchema2Value))->toBeInstanceOf(Test2Schema::class);

});
