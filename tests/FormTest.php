<?php

use Vod\Vod\Types\BaseType;
use Vod\Vod\Vod;

use function Vod\Vod\v;


class SimpleForm extends Vod
{
    public static function theme()
    {
        return v()->object([
            'primaryColor' => v()->string(),
            'primaryFont' => v()->string(),
            'secondaryFont' => v()->string(),
        ]);
    }

    public static function styleRanges()
    {
        return v()->object([
            'from' => v()->number(),
            'to' => v()->number(),
            'style' => v()->enum([
                'bold',
                'italic',
            ]),
        ])->array();
    }

    public static function text()
    {
        return v()->object([
            'text' => v()->string(),
            'styleRanges' => self::styleRanges(),
        ]);
    }

    public static function visibilityRules()
    {
        return v()->object([
            'field' => v()->string(),
            'value' => v()->string()->optional(),
            'conditions' => v()->ref('visibilityRules')->optional(),
            'conjunct' => v()->enum([
                'AND',
                'OR',
            ]),
        ])->array();
    }

    public static function makeField(array $additional = [])
    {
        return v()->object(array_merge([
            'key' => v()->string(),
            'blockType' => v()->literal('field'),
            'title' => v()->ref('text')->optional(),
            'description' => v()->ref('text')->optional(),
            'required' => v()->boolean()->optional(),
            'halfWidth' => v()->boolean()->optional(),
            'hasVisibilityRules' => v()->boolean()->optional(),
            'visibilityRules' => self::visibilityRules()->optional(),
        ], $additional));
    }

    public static function block()
    {
        return v()->anyOf([
            v()->object([
                'key' => v()->string(),
                'blockType' => v()->enum([
                    'header-1',
                    'header-2',
                    'text',
                ]),
                'text' => v()->ref('text'),
            ]),
            v()->object([
                'key' => v()->string(),
                'blockType' => v()->literal('image'),
                'image' => v()->string(),
            ]),
            v()->object([
                'key' => v()->string(),
                'blockType' => v()->literal('video'),
                'video' => v()->string(),
            ]),
            v()->object([
                'key' => v()->string(),
                'blockType' => v()->literal('html'),
                'html' => v()->string(),
            ]),
            self::makeField([
                'fieldType' => v()->literal('text'),
                'placeholder' => v()->ref('text')->optional(),
                'minLength' => v()->number()->optional(),
                'maxLength' => v()->number()->optional(),
                'multiLine' => v()->boolean()->optional(),
                'multiLineRows' => v()->number()->optional(),
                'defaultValue' => v()->string()->optional(),
            ]),
            self::makeField([
                'fieldType' => v()->enum(['email', 'url']),
                'placeholder' => v()->ref('text')->optional(),
                'defaultValue' => v()->string()->optional(),
            ]),

        ]);
    }

    public static function schema()
    {
        $root = v();

        $res = $root->object([
            'theme' => v()->ref('theme'),
            'blocks' => v()->array(
                v()->ref('block')
            ),
        ])
            ->define('text', self::text())
            ->define('theme', self::theme())
            ->define('block', self::block())
            ->define('visibilityRules', self::visibilityRules())
            ->define('styleRanges', self::styleRanges());

        return $res;
    }

    public function v(): BaseType
    {
        return self::schema();
    }
}

it('can load schema', function () {
    $schema = SimpleForm::schema();
    expect($schema)->toBeInstanceOf(BaseType::class);
});

it('can parse a form', function () {

    $formDefinition = json_decode(<<<'JSON'
    {"theme":{"primaryColor":"#4A90E2","primaryFont":"Arial","secondaryFont":"Helvetica"},"blocks":[{"key":"welcome-header","blockType":"header-1","text":{"text":"Welcome to Our Form","styleRanges":[]}},{"key":"intro-text","blockType":"text","text":{"text":"Please provide your details below.","styleRanges":[]}},{"key":"name-field","blockType":"field","title":{"text":"Your Name","styleRanges":[]},"description":null,"halfWidth":null,"hasVisibilityRules":null,"visibilityRules":null,"fieldType":"text","placeholder":{"text":"Enter your full name","styleRanges":[]},"multiLine":null,"multiLineRows":1,"defaultValue":null},{"key":"phone-field","blockType":"field","title":{"text":"Phone Number","styleRanges":[]},"description":null,"halfWidth":null,"hasVisibilityRules":null,"visibilityRules":null,"fieldType":"text","placeholder":{"text":"Enter your phone number","styleRanges":[]},"multiLine":null,"multiLineRows":1,"defaultValue":null}]}
    JSON, true);
    $schema = SimpleForm::schema();
    $form = $schema->parse($formDefinition);
    expect($form)->toBeArray();
});
