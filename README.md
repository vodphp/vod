# Vod - PHP Validation and Object Definition Library

Vod is a powerful PHP library for validating and defining object structures. It provides a fluent API for creating schemas, parsing data, and generating TypeScript definitions and JSON schemas.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/deanmcpherson/vod.svg?style=flat-square)](https://packagist.org/packages/deanmcpherson/vod)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/deanmcpherson/vod/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/deanmcpherson/vod/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/deanmcpherson/vod/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/deanmcpherson/vod/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/deanmcpherson/vod.svg?style=flat-square)](https://packagist.org/packages/deanmcpherson/vod)


## Installation

You can install Vod via Composer:

```bash
composer require deanmcpherson/vod
```
## Testing

```bash
composer test
```
## Usage

### Basic Schema Creation

To create a schema, use the `v()` function to access the Vod API:

```php
use function Vod\Vod\v;
$schema = v()->object([
    'name' => v()->string(),
    'age' => v()->number()->int(),
    'email' => v()->string()->optional(),
]);
```

### Parsing Data

You can parse data against your schema:

```php
$data = ['name' => 'John', 'age' => 30];
$result = $schema->parse($data);
```


### Available Types

Vod supports various types:

- `v()->string()`: String type
- `v()->number()`: Number type (can be further specified as `int()` or `float()`)
- `v()->boolean()`: Boolean type
- `v()->array()`: Array type
- `v()->object()`: Object type
- `v()->enum()`: Enum type
- `v()->any()`: Any type
- `v()->date()`: Date type
- `v()->tuple()`: Tuple type
- `v()->union()`: Union type
- `v()->anyOf()`: Alias of Union type
- `v()->intersection()`: Intersection type
- `v()->all()`: Alias of Intersection type


### Optional Fields

Make fields optional:

```php
v()->string()->optional()
```
### Default Values

Set default values:

```php
v()->string()->default('default value')
```
Note that default values are only used currently if the field is not provided data AND is optional.


### Using Rules

If you are using inside of Laravel, you can use the `rules` method to add rules to your schema. This relies on the Laravel Validator facade, so will only work if it is available.

```php
  v()->string()->rules('email')->parse('not an email') // throws an exception
  v()->string()->rules('email')->parse('dean@example.com') // returns dean@example.com
```

### Adding and referencing definitions

Add definitions to your schema for reusable components:

```php
$schema = v()->object([
    'user' => v()->ref('userSchema'),
    'posts' => v()->array(v()->ref('postSchema')),
])
->define('userSchema', v()->object([
    'id' => v()->number()->int(),
    'name' => v()->string(),
    'email' => v()->string(),
]))
->define('postSchema', v()->object([
    'id' => v()->number()->int(),
    'title' => v()->string(),
    'content' => v()->string(),
]));
```
To add a reference to a defined schema, use `v()->ref('schemaName')`.

Note: Definitions can only be added to a top-level object schema. They are not available for nested objects or other types.

Using definitions can help you create more modular and reusable schemas, especially for complex data structures.

### Generating TypeScript Definitions

Generate TypeScript definitions:

```php
$typescript = $schema->toTypescript();
```

### Generating JSON Schemas

Generate JSON schemas:

```php
$jsonSchema = $schema->toJsonSchema();
```

### Adding Descriptions

Add descriptions to your schema for better documentation - this is only used in json schemas currently.

```php
$schema = v()->object([
        'name' => v()->string()->description('The user\'s full name'),
        'age' => v()->number()->int()->description('The user\'s age in years'),
])->description('User information schema');
```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dean McPherson](https://github.com/deanmcpherson)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
