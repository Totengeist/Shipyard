<?php

namespace Tests;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase {
    protected $http = null;

    public function setUp(): void {
        parent::setUp();
        $dotenv = Dotenv::createImmutable(realpath(__DIR__ . '/..'));
        $dotenv->load();
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
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
