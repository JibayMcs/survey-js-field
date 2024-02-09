# This is my package survey-js-field

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jibaymcs/survey-js-field.svg?style=flat-square)](https://packagist.org/packages/jibaymcs/survey-js-field)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jibaymcs/survey-js-field/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jibaymcs/survey-js-field/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jibaymcs/survey-js-field/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jibaymcs/survey-js-field/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jibaymcs/survey-js-field.svg?style=flat-square)](https://packagist.org/packages/jibaymcs/survey-js-field)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require jibaymcs/survey-js-field
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="survey-js-field-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="survey-js-field-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="survey-js-field-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$surveyJsField = new JibayMcs\SurveyJsField();
echo $surveyJsField->echoPhrase('Hello, JibayMcs!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jean-Baptiste Macias](https://github.com/JibayMcs)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
