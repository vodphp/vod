<?php

use Vod\Vod\Vod;

use function Vod\Vod\v;

it('Vod', function () {


    class Tmp extends Vod {
       public static function schema() {
        return v()->object([
            'name' => v()->string(),
            'age' => v()->number(),
            'address' => v()->object([
                'city' => v()->string(),
                'country' => v()->string(),
            ]),
        ]);
       }
    }

    $instance = new Tmp(['name' => 'John', 'age' => 30, 'address' => ['city' => 'London', 'country' => 'UK']]);
    $age = $instance('age');
    expect($instance('name'))->toBe('John');
    expect($instance('age'))->toBe(30);
    expect($instance('address.city'))->toBe('London');
    expect($instance('address.country'))->toBe('UK');
});
