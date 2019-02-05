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
            'redis_connection' => 'qless',
            'queue' => 'default',
        ],
        // ...    
    ],
```

Also you can set Qless queue as default in  `config/queue.php`

```php
    'default' => env('QUEUE_DRIVER', 'qless'),
```

Redis connection in `config/database.php`

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

And add Laravel Qless service provider to app.php

```php
<?php

return [
    //...
    'providers' => [
        //...
        /**
         * Qless
         */
         LaravelQless\LaravelQlessServiceProvider::class,
    ]
];
```

## Usage

Once you completed the configuration you can use Laravel Queue API. If you used other queue drivers you do not need to change anything else. If you do not know how to use Queue API, please refer to the official Laravel documentation: http://laravel.com/docs/queues

Also you can use additional features.

### Topics
Topic help you to put a job to different queues. 
First, you must to create a subscription. You can use pattern for name of topics. 
Symbol `*` - one word, `#` - few words divided by point `.`. 
Examples: `first.second.*`, `*.second.*`, `#.third`.

```php
/**
 * Subscribe
 */

\Queue::subscribe('*.*.test', 'queue1');

\Queue::subscribe('this.*.test', 'queue2');

\Queue::subscribe('this.*.orange', 'queue3');

```

Than you can put job to all subscribers.

```php
/**
 * Put job to few queues
 */
\Queue::pushToTopic('this.is.test', TestQless::class, ['test' => 'test']);
// Push job to queue1 and queue2, but not to queue3

```

### Custom Handler
Job Handler helps you to create a custom route for jobs.

Custom Handler example:

```php

    class CustomHandler implements JobHandler
    {
        public function perform(BaseJob $job): void
        {
            if ($job->getQueue() === 'queue_name') { // by queue
                (new QueueNameJob)->perform($job);
                return;
            }
            
            if ($job->getData()['option_name'] === 'value') { // by payload
                (new OptionNameJob)->perform($job);
                return;
            }
            
            if (in_array('tag_name', $job->getTags())) { // by tag
                (new TagNameJob)->perform($job);
                return;
            }
            
            // other
            
            $job->perform(); // Default
        }
    }
    
```

You must add to a service provider.

```php

    public function boot()
    {
        // other code

        $this->app->bind(JobHandler::class, CustomHandler::class);
        
        // other code
    }

```


### Recurring Jobs
Sometimes it's not enough simply to schedule one job, but you want to run jobs regularly.
In particular, maybe you have some batch operation that needs to get run once an hour and you don't care what
worker runs it. Recurring jobs are specified much like other jobs:

```php
/**
 * Recurring Job
 */
 
\Queue::recur($intervalInSeconds, $jobClass, $data, $queueName); 

```

## Testing

You can run the tests with:

```bash
vendor/bin/phpunit
```

## Contribution

You can contribute to this package by discovering bugs and opening issues. Please, add to which version of package you create pull request or issue. (e.g. [1.2] Fatal error on push job)

## License

Laravel Qless Queue driver is open-sourced software licensed under the MIT License.
See the [`LICENSE.txt`](https://github.com/pdffiller/laravel-queue-qless/blob/master/LICENSE.txt) file for more.


© 2018-2019 PDFfiller<br>

All rights reserved.