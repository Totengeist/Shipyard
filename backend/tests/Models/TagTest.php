<?php

namespace Tests\Models;

use Laracasts\TestDummy\Factory;
use Shipyard\Models\Tag;
use Tests\TestCase;

class TagModelTest extends TestCase {
    /**
     * @return void
     */
    public function testCanCreateTag() {
        $faker = \Faker\Factory::create();
        /** @var Tag $tag1 */
        $tag1 = Tag::query()->create([
            'label' => $faker->words(5, true)
        ]);

        /** @var Tag $tag2 */
        $tag2 = Tag::query()->findOrFail($tag1->id);
        $this->assertEquals($tag1->label, $tag2->label);
    }

    public function testCanAssignTag() {
        $tag = Factory::create('Shipyard\Models\Tag');
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $challenge = Factory::create('Shipyard\Models\Challenge');

        $ship->assignTag($tag->slug);
        $this->assertTrue($ship->hasTag($tag->slug), 'Failed to assert that a ship has the tag ' . $tag->label . '.');

        $save->assignTag($tag->slug);
        $this->assertTrue($save->hasTag($tag->slug), 'Failed to assert that a save has the tag ' . $tag->label . '.');

        $challenge->assignTag($tag->slug);
        $this->assertTrue($challenge->hasTag($tag->slug), 'Failed to assert that a challenge has the tag ' . $tag->label . '.');
    }

    /**
     * @depends testCanAssignTag
     */
    public function testCanRemoveTag() {
        $tag = Factory::create('Shipyard\Models\Tag');
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $challenge = Factory::create('Shipyard\Models\Challenge');

        $ship->assignTag($tag->slug);
        $save->assignTag($tag->slug);
        $challenge->assignTag($tag->slug);

        $ship->removeTag($tag);
        $this->assertFalse($ship->hasTag($tag->slug), 'Failed to assert that a ship does not have the tag ' . $tag->label . '.');

        $save->removeTag($tag->slug);
        $this->assertFalse($save->hasTag($tag->slug), 'Failed to assert that a save does not have the tag ' . $tag->label . '.');

        $challenge->removeTag($tag->slug);
        $this->assertFalse($challenge->hasTag($tag->slug), 'Failed to assert that a challenge does not have the tag ' . $tag->label . '.');
    }

    public function testCanGetTagItems() {
        $tag = Factory::create('Shipyard\Models\Tag');

        $ships = [];
        $saves = [];
        $challenges = [];

        for ($i = 0; $i < 4; $i++) {
            $ships[$i] = Factory::create('Shipyard\Models\Ship');
            $ships[$i]->assignTag($tag->slug);
            $ships[$i]->save();
            $saves[$i] = Factory::create('Shipyard\Models\Save');
            $saves[$i]->assignTag($tag->slug);
            $saves[$i]->save();
            $challenges[$i] = Factory::create('Shipyard\Models\Challenge');
            $challenges[$i]->assignTag($tag->slug);
            $challenges[$i]->save();
        }

        /** @var Tag $tag */
        $tag = Tag::query()->where('slug', $tag->slug)->with(['ships', 'saves', 'challenges'])->first();

        $this->assertEquals(4, count($tag->ships), "Failed to find 4 ships with tag '{$tag->label}'. Found " . count($tag->ships));
        $this->assertEquals(4, count($tag->saves), "Failed to find 4 saves with tag '{$tag->label}'. Found " . count($tag->ships));
        $this->assertEquals(4, count($tag->challenges), "Failed to find 4 challenges with tag '{$tag->label}'. Found " . count($tag->ships));
    }
}
