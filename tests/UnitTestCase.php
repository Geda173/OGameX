<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base class for unit tests that require database access.
 */
abstract class UnitTestCase extends TestCase
{
    use RefreshDatabase;
}
