Qless Queue driver for Laravel
======================

## Installation

You can install this package via composer using this command:

```
composer require pdffiller/laravel-queue-qless
```

The package will automatically register itself using Laravel auto-discovery.

Setup connection in `config/queue.php`

```php
    'connections' => [
        // ...
        'qless' => [
            'driver' => 'qless',
            'connection' => 'qless',
            'queue' => 'default',
        ],
        // ...    
    ],
```

Also you can set Qless queue as default in  `config/queue.php`

```php
    'default' => env('QUEUE_DRIVER', 'qless'),
```

And redis connection in `config/database.php`

```php
    'redis' => [

        'client' => 'predis',

        // ...
        'qless' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
        // ...
    ],
```

## Usage

Once you completed the configuration you can use Laravel Queue API. If you used other queue drivers you do not need to change anything else. If you do not know how to use Queue API, please refer to the official Laravel documentation: http://laravel.com/docs/queues


## Testing

You can run the tests with:

```bash
vendor/bin/phpunit
```

## Contribution

You can contribute to this package by discovering bugs and opening issues. Please, add to which version of package you create pull request or issue. (e.g. [1.2] Fatal error on push job)
