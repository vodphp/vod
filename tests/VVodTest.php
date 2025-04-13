<?php

use Vod\Vod\Any;
use Vod\Vod\Exceptions\VParseException;
use Vod\Vod\Vod;
use Vod\Vod\Types\VObject;
use function Vod\Vod\v;

class Test extends Vod {

    public static function schema(): VObject {
        return v()->object([
            'name' => v()->string(),
        ]);
    }

    public function hi() {
        return 'hi ' . $this->name;
    }
}

it('can be used as a class', function () {
   
    $testSchema = v()->vod(Test::class);
    $test = $testSchema->parse([
        'name' => 'test',
    ]);
    expect($test)->toBeInstanceOf(Test::class);
    
    $test2 = $testSchema->parse(
        new Test([
            'name' => 'test',
        ])
    );
    expect($test2)->toBeInstanceOf(Test::class);

    $test2Schema = v()->vod(Test::class)->array();
    $test2 = $test2Schema->parse([
        [
            'name' => 'test',
        ],
    ]);
    expect($test2)->toBeArray();
    expect($test2[0])->toBeInstanceOf(Test::class);
    expect($test2[0]->hi())->toBe('hi test');
});
