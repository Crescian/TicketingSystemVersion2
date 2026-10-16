<?php

namespace Tests;

use App\Support\BusinessClock;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // BusinessClock memoizes holiday lookups in a static; tests share one
        // process, so start each test without another test's cached dates.
        BusinessClock::flushHolidayCache();
    }
}
