# Laramix

[![Latest Version on Packagist](https://img.shields.io/packagist/v/deanmcpherson/laramix.svg?style=flat-square)](https://packagist.org/packages/deanmcpherson/laramix)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/deanmcpherson/laramix/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/deanmcpherson/laramix/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/deanmcpherson/laramix/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/deanmcpherson/laramix/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/deanmcpherson/laramix.svg?style=flat-square)](https://packagist.org/packages/deanmcpherson/laramix)

This is a very raw, experimental package that expands on top of [Inertia.js](https://inertiajs.com/) to provide a more tightly integrated front end developement experience between Laravel and front end frameworks (currently only React).

The core idea is to implement Remix style file based routing - which allows routes to be defined and understood by both the front and backend. The benefits this gets us are:

1. **Type Safety**: We can define the shape of the data that is expected to be returned from the backend, and the shape of the data that is expected to be sent to the backend.

2. **Nested routes**: We can rely on the remix style route nesting to automatically load all the required data for a page, with nested route components that all their own data needs.

2. **Automatic RPCs** - We can automatically expose server side functions to the routes they're called in.

3. **Eager rendering/Cached props/SWR** - We can override the default behaviour of Inertia.js and render routes instantly, while refreshing data in the background.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/Laramix.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/Laramix)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require deanmcpherson/laramix
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laramix-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laramix-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laramix-views"
```

## Usage

```php
$laramix = new Vod\Vod();
echo $laramix->echoPhrase('Hello, Laramix!');
```

## Testing

```bash
composer test
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
