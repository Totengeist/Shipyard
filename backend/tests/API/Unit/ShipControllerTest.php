<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Permission;
use Shipyard\Role;
use Shipyard\Ship;
use Tests\APITestCase;

class ShipControllerTest extends APITestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanListShips() {
        $ship = Factory::create('Shipyard\Ship');
        $this->get('api/v1/ship', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $ship->title,
         ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCannotCreateShipsFromLocalFileWhenDisabled() {
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $_ENV['ALLOW_LOCAL'] = 'false';
        $user = Factory::create('Shipyard\User');
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/ship', ['user_ref' => $user->ref, 'title' => $title, 'file_path' => 'tests/testPaper.pdf'], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(401);

        $ship = Ship::where([['user_id', $user->id], ['title', $title], ['file_path', 'tests/testPaper.pdf']])->first();
        $this->assertNull($ship);
        unset($_ENV['ALLOW_LOCAL']);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanCreateShipsFromLocalFileWhenEnabled() {
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $_ENV['ALLOW_LOCAL'] = 'true';
        $user = Factory::create('Shipyard\User');
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph();

        $this->post('api/v1/ship', ['user_ref' => $user->ref, 'title' => $title, 'description' => $description, 'file_path' => 'tests/science-vessel.ship'], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $ship = json_decode(Ship::where([['title', $title], ['description', $description], ['file_path', 'tests/science-vessel.ship']])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $ship);
        unset($_ENV['ALLOW_LOCAL']);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanCreateShipsFromUploadedFile() {
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph();

        $this->post('api/v1/ship', ['user_ref' => $user->ref, 'title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()], ['file' => $this->createSampleUpload()])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $ship = json_decode(Ship::where([['title', $title], ['description', $description]])->with('user')->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $ship);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanEditOwnShips() {
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();
        $faker = \Faker\Factory::create();
        $ship = Factory::create('Shipyard\Ship');
        $ship->user_id = $user->id;
        $ship->save();

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/ship/' . $ship->ref, ['user_ref' => $user->ref, 'title' => $title, 'file_path' => '/'], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'title' => $title,
        ]);

        $ship = json_decode(Ship::where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
        ], $ship);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCannotEditOtherShips() {
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\User');
        $ship = Factory::create('Shipyard\Ship');
        $ship->user_id = $user1->id;
        $ship->save();

        $faker = \Faker\Factory::create();
        $oldtitle = $ship->title;
        $title = $faker->words(3, true);

        $this->post('api/v1/ship/' . $ship->ref, ['user_ref' => $user->ref, 'title' => $title, 'file_path' => '/'], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);

        $ship = json_decode(Ship::where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $oldtitle,
        ], $ship);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanEditShipsWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\User');
        $role_name = $faker->slug;
        $role = Role::create(['slug' => $role_name, 'label' => $faker->name]);
        $role->givePermissionTo(Permission::whereSlug('edit_ships')->first());
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $user1 = Factory::create('Shipyard\User');
        $ship = Factory::create('Shipyard\Ship');
        $ship->user_id = $user1->id;
        $ship->save();

        $title = $faker->words(3, true);
        $description = $faker->paragraph;

        $this->post('api/v1/ship/' . $ship->ref, ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $ship = json_decode(Ship::where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $ship);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanDeleteOwnShips() {
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();
        $faker = \Faker\Factory::create();
        $ship = Factory::create('Shipyard\Ship');
        $ship->user_id = $user->id;
        $ship->save();

        $this->assertEquals($ship->ref, Ship::where([['ref', $ship->ref]])->first()->ref);
        $this->delete('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
                'message' => 'successful'
        ]);

        $this->expectException(ModelNotFoundException::class);
        Ship::findOrFail($ship->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCannnotDeleteOtherShips() {
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\User');
        $ship = Factory::create('Shipyard\Ship');
        $ship->user_id = $user1->id;
        $ship->save();

        $title = $ship->title;
        $description = $ship->description;

        $this->delete('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(403);

        $ship = json_decode(Ship::where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $ship);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanDeleteShipsWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\User');
        $role_name = $faker->slug;
        $role = Role::create(['slug' => $role_name, 'label' => $faker->name]);
        $role->givePermissionTo(Permission::whereSlug('edit_ships')->first());
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $user1 = Factory::create('Shipyard\User');
        $ship = Factory::create('Shipyard\Ship');
        $ship->user_id = $user1->id;
        $ship->save();

        $this->assertEquals($ship->ref, Ship::where([['ref', $ship->ref]])->first()->ref);
        $this->delete('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertJsonResponse([
                'message' => 'successful'
        ]);

        $this->expectException(ModelNotFoundException::class);
        Ship::findOrFail($ship->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanViewShips() {
        $ship = Factory::create('Shipyard\Ship');

        $this->get('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $ship->title,
        ]);
    }
}
