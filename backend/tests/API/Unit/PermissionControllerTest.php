<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Permission;
use Tests\APITestCase;

class PermissionControllerTest extends APITestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotListPermissions() {
        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->get('api/v1/permission', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->get('api/v1/permission', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanListPermissions() {
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $permission = Factory::create('Shipyard\Permission');
        $this->get('api/v1/permission', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
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
    public function testNonadminCannotCreatePermissions() {
        $faker = \Faker\Factory::create();
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/permission', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->post('api/v1/permission', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanCreatePermissions() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/permission', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $permission = json_decode(Permission::where([['slug', $slug], ['label', $label]])->first()->toJson(), true);
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
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $slug = $faker->slug;
        $label = '';

        $this->post('api/v1/permission', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'label' => ['Label is required'],
        ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotEditPermissions() {
        $faker = \Faker\Factory::create();
        $permission = Factory::create('Shipyard\Permission');
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/permission/' . $permission->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->post('api/v1/permission/' . $permission->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanEditPermissions() {
        $faker = \Faker\Factory::create();
        $permission = Factory::create('Shipyard\Permission');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/permission/' . $permission->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $permission = json_decode(Permission::find($permission->id)->toJson(), true);
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
    public function testNonadminCannotDeletePermissions() {
        $permission = Factory::create('Shipyard\Permission');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->delete('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanDeletePermissions() {
        $permission = Factory::create('Shipyard\Permission');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $this->assertEquals($permission->id, Permission::find($permission->id)->id);
        $this->delete('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
                'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        Permission::findOrFail($permission->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotViewAPermission() {
        $permission = Factory::create('Shipyard\Permission');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->get('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->get('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanViewAPermission() {
        $permission = Factory::create('Shipyard\Permission');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $this->get('api/v1/permission/' . $permission->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'slug' => $permission->slug,
            'label' => $permission->label,
        ]);
    }
}
