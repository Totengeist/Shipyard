<?php

namespace Tests\Unit\API;

use Laracasts\TestDummy\Factory;
use Shipyard\Tag;
use Tests\TestCase;

class TagModelTest extends TestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanCreateTag() {
        $faker = \Faker\Factory::create();
        $tag1 = Tag::create([
            'label' => $faker->words(5, true)
        ]);

        $tag2 = Tag::findOrFail($tag1->id);
        $this->assertEquals($tag1->label, $tag2->label);
    }

    public function testCanAssignTag() {
        $tag = Factory::create('Shipyard\Tag');
        $ship = Factory::create('Shipyard\Ship');
        $save = Factory::create('Shipyard\Save');
        $challenge = Factory::create('Shipyard\Challenge');

        $ship->assignTag($tag->slug);
        $this->assertTrue($ship->hasTag($tag->slug));

        $save->assignTag($tag->slug);
        $this->assertTrue($save->hasTag($tag->slug));

        $challenge->assignTag($tag->slug);
        $this->assertTrue($challenge->hasTag($tag->slug));
    }
}
