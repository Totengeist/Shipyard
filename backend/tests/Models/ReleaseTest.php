<?php

namespace Tests\Unit\API;

use Laracasts\TestDummy\Factory;
use Shipyard\Release;
use Tests\TestCase;

class ReleaseModelTest extends TestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanCreatRelease() {
        $faker = \Faker\Factory::create();
        $release1 = Release::create([
            'label' => $faker->words(5, true)
        ]);

        $release2 = Release::findOrFail($release1->id);
        $this->assertEquals($release1->label, $release2->label);
    }

    public function testCanAssignRelease() {
        $release = Factory::create('Shipyard\Release');
        $ship = Factory::create('Shipyard\Ship');
        $save = Factory::create('Shipyard\Save');
        $challenge = Factory::create('Shipyard\Challenge');

        $ship->assignRelease($release->slug);
        $this->assertTrue($ship->hasRelease($release->slug), 'Failed to assert that a ship has the release ' . $release->label . '.');

        $save->assignRelease($release->slug);
        $this->assertTrue($save->hasRelease($release->slug), 'Failed to assert that a save has the release ' . $release->label . '.');

        $challenge->assignRelease($release->slug);
        $this->assertTrue($challenge->hasRelease($release->slug), 'Failed to assert that a challenge has the release ' . $release->label . '.');
    }

    /**
     * @depends testCanAssignRelease
     */
    public function testCanRemoveRelease() {
        $release = Factory::create('Shipyard\Release');
        $ship = Factory::create('Shipyard\Ship');
        $save = Factory::create('Shipyard\Save');
        $challenge = Factory::create('Shipyard\Challenge');

        $ship->assignRelease($release->slug);
        $save->assignRelease($release->slug);
        $challenge->assignRelease($release->slug);

        $ship->removeRelease($release);
        $this->assertFalse($ship->hasRelease($release->slug), 'Failed to assert that a ship does not have the release ' . $release->label . '.');

        $save->removeRelease($release->slug);
        $this->assertFalse($save->hasRelease($release->slug), 'Failed to assert that a save does not have the release ' . $release->label . '.');

        $challenge->removeRelease($release->slug);
        $this->assertFalse($challenge->hasRelease($release->slug), 'Failed to assert that a challenge does not have the release ' . $release->label . '.');
    }

    public function testCanGetReleaseItems() {
        $release = Factory::create('Shipyard\Release');

        $ships = [];
        $saves = [];
        $challenges = [];

        for ($i = 0; $i < 4; $i++) {
            $ships[$i] = Factory::create('Shipyard\Ship');
            $ships[$i]->assignRelease($release->slug);
            $ships[$i]->save();
            $saves[$i] = Factory::create('Shipyard\Save');
            $saves[$i]->assignRelease($release->slug);
            $saves[$i]->save();
            $challenges[$i] = Factory::create('Shipyard\Challenge');
            $challenges[$i]->assignRelease($release->slug);
            $challenges[$i]->save();
        }

        $release = Release::where('slug', $release->slug)->with('ships', 'saves', 'challenges')->first();

        $this->assertEquals(4, count($release->ships), "Failed to find 4 ships with release '{$release->label}'. Found " . count($release->ships));
        $this->assertEquals(4, count($release->saves), "Failed to find 4 saves with release '{$release->label}'. Found " . count($release->ships));
        $this->assertEquals(4, count($release->challenges), "Failed to find 4 challenges with release '{$release->label}'. Found " . count($release->ships));
    }
}
