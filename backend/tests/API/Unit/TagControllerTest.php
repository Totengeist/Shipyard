<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Tag;
use Tests\APITestCase;

class TagControllerTest extends APITestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanListTags() {
        $admin = Factory::create('Shipyard\User');

        $tag = Factory::create('Shipyard\Tag');
        $this->get('api/v1/tag', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $tag->slug,
            'label' => $tag->label,
         ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotCreateTags() {
        $faker = \Faker\Factory::create();
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/tag', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/tag', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanCreateTags() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/tag', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $tag = json_decode(Tag::where([['slug', $slug], ['label', $label]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $tag);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCannotCreateEmptyTags() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = '';

        $this->post('api/v1/tag', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'label' => ['Label is required'],
        ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotEditTags() {
        $faker = \Faker\Factory::create();
        $tag = Factory::create('Shipyard\Tag');
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/tag/' . $tag->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/tag/' . $tag->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanEditTags() {
        $faker = \Faker\Factory::create();
        $tag = Factory::create('Shipyard\Tag');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/tag/' . $tag->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $tag = json_decode(Tag::find($tag->id)->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $tag);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotDeleteTags() {
        $tag = Factory::create('Shipyard\Tag');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->delete('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanDeleteTags() {
        $tag = Factory::create('Shipyard\Tag');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->assertEquals($tag->id, Tag::find($tag->id)->id);
        $this->delete('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        Tag::findOrFail($tag->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCanViewATag() {
        $tag = Factory::create('Shipyard\Tag');

        $this->get('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $tag->slug,
            'label' => $tag->label,
        ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanViewATag() {
        $tag = Factory::create('Shipyard\Tag');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->get('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $tag->slug,
            'label' => $tag->label,
        ]);
    }
}