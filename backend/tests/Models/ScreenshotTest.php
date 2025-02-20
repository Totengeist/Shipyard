<?php

namespace Tests\Models;

use Laracasts\TestDummy\Factory;
use Shipyard\Models\Screenshot;
use Tests\TestCase;

class ScreenshotModelTest extends TestCase {
    /**
     * @return void
     */
    public function testCanCreateScreenshot() {
        $faker = \Faker\Factory::create();
        /** @var Screenshot $screenshot1 */
        $screenshot1 = Screenshot::query()->create([
            'description' => $faker->words(5, true),
            'file_id' => $faker->randomDigit(),
        ]);

        /** @var Screenshot $screenshot2 */
        $screenshot2 = Screenshot::query()->findOrFail($screenshot1->id);
        $this->assertEquals($screenshot1->description, $screenshot2->description);
    }

    public function testCanAssignScreenshot() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $modification = Factory::create('Shipyard\Models\Modification');

        $ship->assignScreenshot($screenshot->ref);
        $this->assertTrue($ship->hasScreenshot($screenshot->ref), 'Failed to assert that a ship has the screenshot ' . $screenshot->ref . '.');

        $save->assignScreenshot($screenshot->ref);
        $this->assertTrue($save->hasScreenshot($screenshot->ref), 'Failed to assert that a save has the screenshot ' . $screenshot->ref . '.');

        $modification->assignScreenshot($screenshot->ref);
        $this->assertTrue($modification->hasScreenshot($screenshot->ref), 'Failed to assert that a modification has the screenshot ' . $screenshot->ref . '.');
    }

    /**
     * @return void
     */
    public function testFirstScreenshotBecomesPrimary() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship = Factory::create('Shipyard\Models\Ship');

        $ship->assignScreenshot($screenshot->ref);
        $this->assertEquals(1, count($ship->primary_screenshot));
        $this->assertEquals($screenshot->description, $ship->primary_screenshot[0]->description);
    }

    /**
     * @return void
     */
    public function testCanSetFutureScreenshotPrimary() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $screenshot2 = Factory::create('Shipyard\Models\Screenshot');
        $ship = Factory::create('Shipyard\Models\Ship');

        $ship->assignScreenshot($screenshot->ref);
        $ship->assignScreenshot($screenshot2->ref, true);
        $this->assertEquals(1, count($ship->primary_screenshot));
        $this->assertEquals($screenshot2->description, $ship->primary_screenshot[0]->description);
    }

    /**
     * @depends testCanAssignScreenshot
     */
    public function testCanRemoveScreenshot() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $modification = Factory::create('Shipyard\Models\Modification');

        $ship->assignScreenshot($screenshot->ref);
        $save->assignScreenshot($screenshot->ref);
        $modification->assignScreenshot($screenshot->ref);

        $ship->removeScreenshot($screenshot);
        $this->assertFalse($ship->hasScreenshot($screenshot->ref), 'Failed to assert that a ship does not have the screenshot ' . $screenshot->ref . '.');

        $save->removeScreenshot($screenshot->ref);
        $this->assertFalse($save->hasScreenshot($screenshot->ref), 'Failed to assert that a save does not have the screenshot ' . $screenshot->ref . '.');

        $modification->removeScreenshot($screenshot->ref);
        $this->assertFalse($modification->hasScreenshot($screenshot->ref), 'Failed to assert that a modification does not have the screenshot ' . $screenshot->ref . '.');
    }

    public function testCanGetScreenshotItems() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');

        $ships = [];
        $saves = [];
        $modifications = [];

        for ($i = 0; $i < 4; $i++) {
            $ships[$i] = Factory::create('Shipyard\Models\Ship');
            $ships[$i]->assignScreenshot($screenshot->ref);
            $ships[$i]->save();
            $saves[$i] = Factory::create('Shipyard\Models\Save');
            $saves[$i]->assignScreenshot($screenshot->ref);
            $saves[$i]->save();
            $modifications[$i] = Factory::create('Shipyard\Models\Modification');
            $modifications[$i]->assignScreenshot($screenshot->ref);
            $modifications[$i]->save();
        }

        /** @var Screenshot $screenshot */
        $screenshot = Screenshot::query()->where('ref', $screenshot->ref)->with(['ships', 'saves', 'modifications'])->first();

        $this->assertEquals(4, count($screenshot->ships), "Failed to find 4 ships with screenshot '{$screenshot->ref}'. Found " . count($screenshot->ships));
        $this->assertEquals(4, count($screenshot->saves), "Failed to find 4 saves with screenshot '{$screenshot->ref}'. Found " . count($screenshot->ships));
        $this->assertEquals(4, count($screenshot->modifications), "Failed to find 4 modifications with screenshot '{$screenshot->ref}'. Found " . count($screenshot->ships));
    }
}
