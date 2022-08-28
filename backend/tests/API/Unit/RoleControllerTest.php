<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Role;
use Tests\APITestCase;

class RoleControllerTest extends APITestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotListRoles() {
        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->get('api/v1/role', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->get('api/v1/role', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanListRoles() {
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $role = Factory::create('Shipyard\Role');
        $this->get('api/v1/role', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'slug' => $role->slug,
            'label' => $role->label,
         ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotCreateRoles() {
        $faker = \Faker\Factory::create();
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/role', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->post('api/v1/role', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanCreateRoles() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/role', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $role = json_decode(Role::where([['slug', $slug], ['label', $label]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $role);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCannotCreateEmptyRoles() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $slug = $faker->slug;
        $label = '';

        $this->post('api/v1/role', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'label' => ['Label is required'],
        ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotEditRoles() {
        $faker = \Faker\Factory::create();
        $role = Factory::create('Shipyard\Role');
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/role/' . $role->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->post('api/v1/role/' . $role->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanEditRoles() {
        $faker = \Faker\Factory::create();
        $role = Factory::create('Shipyard\Role');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/role/' . $role->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'slug' => $slug,
            'label' => $label,
        ]);

        $role = json_decode(Role::find($role->id)->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $role);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotDeleteRoles() {
        $role = Factory::create('Shipyard\Role');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/role/' . $role->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->delete('api/v1/role/' . $role->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanDeleteRoles() {
        $role = Factory::create('Shipyard\Role');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $this->assertEquals($role->id, Role::find($role->id)->id);
        $this->delete('api/v1/role/' . $role->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        Role::findOrFail($role->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNonadminCannotViewARole() {
        $role = Factory::create('Shipyard\Role');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->get('api/v1/role/' . $role->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(200);
        $this->get('api/v1/role/' . $role->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanViewARole() {
        $role = Factory::create('Shipyard\Role');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $this->get('api/v1/role/' . $role->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'slug' => $role->slug,
            'label' => $role->label,
        ]);
    }
}
