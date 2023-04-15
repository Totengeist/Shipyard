<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Release;
use Tests\APITestCase;

class ReleaseControllerTest extends APITestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanListReleases() {
        $admin = Factory::create('Shipyard\User');

        $release = Factory::create('Shipyard\Release');
        $this->get('api/v1/release', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $release->slug,
            'label' => $release->label,
         ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotCreateReleases() {
        $faker = \Faker\Factory::create();
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/release', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/release', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanCreateReleases() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/release', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $release = json_decode(Release::where([['slug', $slug], ['label', $label]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $release);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCannotCreateEmptyReleases() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = '';

        $this->post('api/v1/release', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'label' => ['Label is required'],
        ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotEditReleases() {
        $faker = \Faker\Factory::create();
        $release = Factory::create('Shipyard\Release');
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/release/' . $release->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/release/' . $release->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanEditReleases() {
        $faker = \Faker\Factory::create();
        $release = Factory::create('Shipyard\Release');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/release/' . $release->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $release = json_decode(Release::find($release->id)->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $release);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotDeleteReleases() {
        $release = Factory::create('Shipyard\Release');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->delete('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanDeleteReleases() {
        $release = Factory::create('Shipyard\Release');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->assertEquals($release->id, Release::find($release->id)->id);
        $this->delete('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        Release::findOrFail($release->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCanViewARelease() {
        $release = Factory::create('Shipyard\Release');

        $this->get('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $release->slug,
            'label' => $release->label,
        ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanViewARelease() {
        $release = Factory::create('Shipyard\Release');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->get('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $release->slug,
            'label' => $release->label,
        ]);
    }
}
