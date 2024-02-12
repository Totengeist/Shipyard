<?php

namespace Tests\Models;

use Laracasts\TestDummy\Factory;
use Shipyard\Models\Release;
use Tests\TestCase;

class ReleaseModelTest extends TestCase {
    /**
     * @return void
     */
    public function testCanCreateRelease() {
        $faker = \Faker\Factory::create();
        /** @var Release $release1 */
        $release1 = Release::query()->create([
            'label' => $faker->words(5, true)
        ]);

        /** @var Release $release2 */
        $release2 = Release::query()->findOrFail($release1->id);
        $this->assertEquals($release1->label, $release2->label);
    }

    public function testCanAssignRelease() {
        $release = Factory::create('Shipyard\Models\Release');
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $modification = Factory::create('Shipyard\Models\Modification');

        $ship->assignRelease($release->slug);
        $this->assertTrue($ship->hasRelease($release->slug), 'Failed to assert that a ship has the release ' . $release->label . '.');

        $save->assignRelease($release->slug);
        $this->assertTrue($save->hasRelease($release->slug), 'Failed to assert that a save has the release ' . $release->label . '.');

        $modification->assignRelease($release->slug);
        $this->assertTrue($modification->hasRelease($release->slug), 'Failed to assert that a modification has the release ' . $release->label . '.');
    }

    /**
     * @depends testCanAssignRelease
     */
    public function testCanRemoveRelease() {
        $release = Factory::create('Shipyard\Models\Release');
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $modification = Factory::create('Shipyard\Models\Modification');

        $ship->assignRelease($release->slug);
        $save->assignRelease($release->slug);
        $modification->assignRelease($release->slug);

        $ship->removeRelease($release);
        $this->assertFalse($ship->hasRelease($release), 'Failed to assert that a ship does not have the release ' . $release->label . '.');

        $save->removeRelease($release->slug);
        $this->assertFalse($save->hasRelease($release->slug), 'Failed to assert that a save does not have the release ' . $release->label . '.');

        $modification->removeRelease($release->slug);
        $this->assertFalse($modification->hasRelease($release->slug), 'Failed to assert that a modification does not have the release ' . $release->label . '.');
    }

    public function testCanGetReleaseItems() {
        $release = Factory::create('Shipyard\Models\Release');

        $ships = [];
        $saves = [];
        $modifications = [];

        for ($i = 0; $i < 4; $i++) {
            $ships[$i] = Factory::create('Shipyard\Models\Ship');
            $ships[$i]->assignRelease($release->slug);
            $ships[$i]->save();
            $saves[$i] = Factory::create('Shipyard\Models\Save');
            $saves[$i]->assignRelease($release->slug);
            $saves[$i]->save();
            $modifications[$i] = Factory::create('Shipyard\Models\Modification');
            $modifications[$i]->assignRelease($release->slug);
            $modifications[$i]->save();
        }

        /** @var Release $release */
        $release = Release::query()->where('slug', $release->slug)->with(['ships', 'saves', 'modifications'])->first();

        $this->assertEquals(4, count($release->ships), "Failed to find 4 ships with release '{$release->label}'. Found " . count($release->ships));
        $this->assertEquals(4, count($release->saves), "Failed to find 4 saves with release '{$release->label}'. Found " . count($release->ships));
        $this->assertEquals(4, count($release->modifications), "Failed to find 4 modifications with release '{$release->label}'. Found " . count($release->ships));
    }
}
