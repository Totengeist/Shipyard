<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\Release;
use Tests\APITestCase;

class ReleaseControllerTest extends APITestCase {
    /**
     * @return void
     */
    public function testCanListReleases() {
        $release = Factory::create('Shipyard\Models\Release');
        $this->get('api/v1/release', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $release->slug,
                 'label' => $release->label,
             ]);
    }

    /**
     * @return void
     */
    public function testUserCannotCreateReleases() {
        $faker = \Faker\Factory::create();
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/release', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/release', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function testAdminCanCreateReleases() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
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

        $release = json_decode(Release::query()->where([['slug', $slug], ['label', $label]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $release);
    }

    /**
     * @return void
     */
    public function testAdminCannotCreateEmptyReleases() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
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
     * @return void
     */
    public function testUserCannotEditReleases() {
        $faker = \Faker\Factory::create();
        $release = Factory::create('Shipyard\Models\Release');
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/release/' . $release->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/release/' . $release->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function testAdminCanEditReleases() {
        $faker = \Faker\Factory::create();
        $release = Factory::create('Shipyard\Models\Release');
        $admin = Factory::create('Shipyard\Models\User');
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

        $release = json_decode(Release::query()->find($release->id)->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $release);
    }

    /**
     * @return void
     */
    public function testUserCannotDeleteReleases() {
        $release = Factory::create('Shipyard\Models\Release');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->delete('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function testAdminCanDeleteReleases() {
        $release = Factory::create('Shipyard\Models\Release');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        /** @var Release $dbRelease */
        $dbRelease = Release::query()->find($release->id);
        $this->assertEquals($release->id, $dbRelease->id);
        $this->delete('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'message' => 'successful',
             ]);

        $this->expectException(ModelNotFoundException::class);
        Release::query()->findOrFail($release->id);
    }

    /**
     * @return void
     */
    public function testUserCanViewARelease() {
        $release = Factory::create('Shipyard\Models\Release');

        $this->get('api/v1/release/' . $release->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $release->slug,
                 'label' => $release->label,
             ]);
    }

    /**
     * @return void
     */
    public function testAdminCanViewARelease() {
        $release = Factory::create('Shipyard\Models\Release');
        $admin = Factory::create('Shipyard\Models\User');
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
