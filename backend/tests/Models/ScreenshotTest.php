<?php

namespace Tests\Models;

use Laracasts\TestDummy\Factory;
use Shipyard\Models\Screenshot;
use Tests\TestCase;

class ScreenshotModelTest extends TestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanCreateScreenshot() {
        $faker = \Faker\Factory::create();
        $screenshot1 = Screenshot::query()->create([
            'description' => $faker->words(5, true),
            'file_path' => realpath(__DIR__ . '/../../assets/science-vessel.png'),
        ]);

        $screenshot2 = Screenshot::query()->findOrFail($screenshot1->id);
        $this->assertEquals($screenshot1->label, $screenshot2->label);
    }

    public function testCanAssignScreenshot() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $challenge = Factory::create('Shipyard\Models\Challenge');

        $ship->assignScreenshot($screenshot->ref);
        $this->assertTrue($ship->hasScreenshot($screenshot->ref), 'Failed to assert that a ship has the screenshot ' . $screenshot->label . '.');

        $save->assignScreenshot($screenshot->ref);
        $this->assertTrue($save->hasScreenshot($screenshot->ref), 'Failed to assert that a save has the screenshot ' . $screenshot->label . '.');

        $challenge->assignScreenshot($screenshot->ref);
        $this->assertTrue($challenge->hasScreenshot($screenshot->ref), 'Failed to assert that a challenge has the screenshot ' . $screenshot->label . '.');
    }

    /**
     * @depends testCanAssignScreenshot
     */
    public function testCanRemoveScreenshot() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $challenge = Factory::create('Shipyard\Models\Challenge');

        $ship->assignScreenshot($screenshot->ref);
        $save->assignScreenshot($screenshot->ref);
        $challenge->assignScreenshot($screenshot->ref);

        $ship->removeScreenshot($screenshot);
        $this->assertFalse($ship->hasScreenshot($screenshot->ref), 'Failed to assert that a ship does not have the screenshot ' . $screenshot->label . '.');

        $save->removeScreenshot($screenshot->ref);
        $this->assertFalse($save->hasScreenshot($screenshot->ref), 'Failed to assert that a save does not have the screenshot ' . $screenshot->label . '.');

        $challenge->removeScreenshot($screenshot->ref);
        $this->assertFalse($challenge->hasScreenshot($screenshot->ref), 'Failed to assert that a challenge does not have the screenshot ' . $screenshot->label . '.');
    }

    public function testCanGetScreenshotItems() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');

        $ships = [];
        $saves = [];
        $challenges = [];

        for ($i = 0; $i < 4; $i++) {
            $ships[$i] = Factory::create('Shipyard\Models\Ship');
            $ships[$i]->assignScreenshot($screenshot->ref);
            $ships[$i]->save();
            $saves[$i] = Factory::create('Shipyard\Models\Save');
            $saves[$i]->assignScreenshot($screenshot->ref);
            $saves[$i]->save();
            $challenges[$i] = Factory::create('Shipyard\Models\Challenge');
            $challenges[$i]->assignScreenshot($screenshot->ref);
            $challenges[$i]->save();
        }

        $screenshot = Screenshot::query()->where('ref', $screenshot->ref)->with('ships', 'saves', 'challenges')->first();

        $this->assertEquals(4, count($screenshot->ships), "Failed to find 4 ships with screenshot '{$screenshot->label}'. Found " . count($screenshot->ships));
        $this->assertEquals(4, count($screenshot->saves), "Failed to find 4 saves with screenshot '{$screenshot->label}'. Found " . count($screenshot->ships));
        $this->assertEquals(4, count($screenshot->challenges), "Failed to find 4 challenges with screenshot '{$screenshot->label}'. Found " . count($screenshot->ships));
    }
}
