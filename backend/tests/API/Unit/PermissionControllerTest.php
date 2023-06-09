<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\Permission;
use Tests\APITestCase;

class PermissionControllerTest extends APITestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUserCannotListPermissions() {
        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->get('api/v1/permission', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->get('api/v1/permission', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanListPermissions() {
        $admin = Factory::create('Shipyard\Models\\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $permission = Factory::create('Shipyard\Models\\Permission');
        $this->get('api/v1/permission', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $permission->slug,
            'label' => $permission->label,
         ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUserCannotCreatePermissions() {
        $faker = \Faker\Factory::create();
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/permission', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/permission', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanCreatePermissions() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/permission', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $permission = json_decode(Permission::query()->where([['slug', $slug], ['label', $label]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $permission);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCannotCreateEmptyPermissions() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = '';

        $this->post('api/v1/permission', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'label' => ['Label is required'],
        ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUserCannotEditPermissions() {
        $faker = \Faker\Factory::create();
        $permission = Factory::create('Shipyard\Models\\Permission');
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/permission/' . $permission->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/permission/' . $permission->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanEditPermissions() {
        $faker = \Faker\Factory::create();
        $permission = Factory::create('Shipyard\Models\\Permission');
        $admin = Factory::create('Shipyard\Models\\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/permission/' . $permission->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $permission = json_decode(Permission::query()->find($permission->id)->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $permission);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUserCannotDeletePermissions() {
        $permission = Factory::create('Shipyard\Models\\Permission');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->delete('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanDeletePermissions() {
        $permission = Factory::create('Shipyard\Models\\Permission');
        $admin = Factory::create('Shipyard\Models\\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        /** @var \Shipyard\Models\Permission $dbPermission */
        $dbPermission = Permission::query()->find($permission->id);
        $this->assertEquals($permission->id, $dbPermission->id);
        $this->delete('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        Permission::query()->findOrFail($permission->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUserCannotViewAPermission() {
        $permission = Factory::create('Shipyard\Models\\Permission');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->get('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->get('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanViewAPermission() {
        $permission = Factory::create('Shipyard\Models\\Permission');
        $admin = Factory::create('Shipyard\Models\\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->get('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'slug' => $permission->slug,
            'label' => $permission->label,
        ]);
    }
}
