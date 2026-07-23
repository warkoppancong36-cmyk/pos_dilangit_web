<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSqliteCompatFunctions();
    }

    /**
     * The app's raw report queries use MySQL date functions. When the test
     * suite runs on sqlite, provide equivalents so those endpoints work.
     */
    private function registerSqliteCompatFunctions(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = $connection->getPdo();

        $pdo->sqliteCreateFunction('HOUR', function ($value) {
            return $value === null ? null : (int) date('G', strtotime($value));
        }, 1);

        // MySQL DAYOFWEEK: 1 = Sunday ... 7 = Saturday
        $pdo->sqliteCreateFunction('DAYOFWEEK', function ($value) {
            return $value === null ? null : ((int) date('w', strtotime($value)) + 1);
        }, 1);

        $pdo->sqliteCreateFunction('DATE_FORMAT', function ($value, $format) {
            if ($value === null) {
                return null;
            }

            $map = [
                '%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%c' => 'n', '%d' => 'd',
                '%e' => 'j', '%H' => 'H', '%k' => 'G', '%i' => 'i', '%s' => 's',
            ];

            return date(strtr($format, $map), strtotime($value));
        }, 2);
    }
}
