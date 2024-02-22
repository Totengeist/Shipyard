<?php

namespace Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Shipyard\Log;

/**
 * @property int $id
 */
class TestCase extends BaseTestCase {
    public function setUp(): void {
        parent::setUp();
        (new Log())->setAsGlobal();
    }

    /**
     * @param mixed[] $dataExpected
     * @param mixed[] $dataActual
     *
     * @return $this
     */
    public function assertJsonFragment(array $dataExpected, array $dataActual, bool $negate = false) {
        $actual = substr((string) json_encode(Arr::sortRecursive($dataActual)), 1, -1);

        foreach (Arr::sortRecursive($dataExpected) as $key => $value) {
            $expected = substr((string) json_encode([$key => $value]), 1, -1);

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
