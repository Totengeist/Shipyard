<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase {
    protected $http = null;

    public function setUp(): void {
        parent::setUp();
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' =>'localhost',
            'database' => 'shipyard',
            'username' => 'root',
            'password' => ''
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    public function assertJsonFragment(array $dataExpected, array $dataActual, $negate = false) {
        $actual = substr(json_encode(Arr::sortRecursive($dataActual)), 1, -1);

        foreach (Arr::sortRecursive($dataExpected) as $key => $value) {
            $expected = substr(json_encode([$key => $value]), 1, -1);

            if ($negate) {
                BaseTestCase::assertFalse(
                    Str::contains($actual, $expected),
                    'Found unexpected JSON fragment: ' . PHP_EOL . PHP_EOL .
                    "[{$expected}]" . PHP_EOL . PHP_EOL .
                    'within' . PHP_EOL . PHP_EOL .
                    "[{$actual}]."
                );
            } else {
                BaseTestCase::assertTrue(
                    Str::contains($actual, $expected),
                    'Unable to find JSON fragment: ' . PHP_EOL . PHP_EOL .
                    "[{$expected}]" . PHP_EOL . PHP_EOL .
                    'within' . PHP_EOL . PHP_EOL .
                    "[{$actual}]."
                );
            }
        }

        return $this;
    }
}
