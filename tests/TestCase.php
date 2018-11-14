<?php

namespace LaravelQless\Tests;

use LaravelQless\LaravelQlessServiceProvider;
use PHPUnit\Framework\TestCase as BaseCase;

class TestCase extends BaseCase
{
    /**
     * Load package service provider
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [LaravelQlessServiceProvider::class];
    }
}
