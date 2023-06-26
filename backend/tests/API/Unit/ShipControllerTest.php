<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\Permission;
use Shipyard\Models\Role;
use Shipyard\Models\Ship;
use Shipyard\Traits\ProcessesSlugs;
use Tests\APITestCase;

class ShipControllerTest extends APITestCase {
    use ProcessesSlugs;

    /**
     * @return void
     */
    public function testCanListShips() {
        $ship = Factory::create('Shipyard\Models\Ship');
        $this->get('api/v1/ship', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $ship->title,
         ]);
    }

    /**
     * @return void
     */
    public function testCannotCreateShipsFromLocalFile() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/ship', ['user_ref' => $user->ref, 'title' => $title, 'file_path' => 'tests/assets/science-vessel.ship'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $ship = Ship::query()->where([['user_id', $user->id], ['title', $title], ['file_path', 'tests/assets/science-vessel.ship']])->first();
        $this->assertNull($ship);
    }

    /**
     * @return void
     */
    public function testCanCreateShipsFromUploadedFile() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph();

        $this->post('api/v1/ship', ['user_ref' => $user->ref, 'title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload()])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $ship = json_decode(Ship::query()->where([['title', $title], ['description', $description]])->with('user')->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $ship);
    }

    /**
     * @return void
     */
    public function testCanEditOwnShips() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/ship/' . $ship->ref, ['user_ref' => $user->ref, 'title' => $title, 'file_path' => '/'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $title,
        ]);

        $ship = json_decode(Ship::query()->where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
        ], $ship);
    }

    /**
     * @return void
     */
    public function testCannotEditOtherShips() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user1->id]);

        $faker = \Faker\Factory::create();
        $oldtitle = $ship->title;
        $title = $faker->words(3, true);

        $this->post('api/v1/ship/' . $ship->ref, ['user_ref' => $user->ref, 'title' => $title, 'file_path' => '/'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);

        $ship = json_decode(Ship::query()->where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $oldtitle,
        ], $ship);
    }

    /**
     * @return void
     */
    public function testCanEditShipsWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\Models\User');
        $role_name = $faker->slug;
        /** @var \Shipyard\Models\Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'edit-ships');
        /** @var \Shipyard\Models\Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $user1 = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user1->id]);

        $title = $faker->words(3, true);
        $description = $faker->paragraph;

        $this->post('api/v1/ship/' . $ship->ref, ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $ship = json_decode(Ship::query()->where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $ship);
    }

    /**
     * @return void
     */
    public function testCanDeleteOwnShips() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);

        $this->assertEquals($ship->ref, Ship::query()->where([['ref', $ship->ref]])->first()->ref);
        $this->delete('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'message' => 'successful'
        ]);

        $this->expectException(ModelNotFoundException::class);
        Ship::query()->findOrFail($ship->id);
    }

    /**
     * @return void
     */
    public function testCannnotDeleteOtherShips() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user1->id]);

        $title = $ship->title;
        $description = $ship->description;

        $this->delete('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);

        $ship = json_decode(Ship::query()->where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $ship);
    }

    /**
     * @return void
     */
    public function testCanDeleteShipsWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\Models\User');
        $role_name = $faker->slug;
        /** @var \Shipyard\Models\Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'delete-ships');
        /** @var \Shipyard\Models\Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $user1 = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user1->id]);

        $this->assertEquals($ship->ref, Ship::query()->where([['ref', $ship->ref]])->first()->ref);
        $this->delete('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'message' => 'successful'
        ]);

        $this->expectException(ModelNotFoundException::class);
        Ship::query()->findOrFail($ship->id);
    }

    /**
     * @return void
     */
    public function testCanViewShips() {
        $ship = Factory::create('Shipyard\Models\Ship');

        $this->get('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $ship->title,
        ]);
    }

    /**
     * @return void
     */
    public function testCanDownloadShips() {
        $ship = Factory::create('Shipyard\Models\Ship');

        $this->get('api/v1/ship/' . $ship->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertNotEquals((string) $this->response->getBody(), '');
        $this->assertEquals((string) $this->response->getBody(), $ship->file_contents());
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . self::slugify($ship->title) . '.ship"');

        $this->get('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'ref' => $ship->ref,
            'title' => $ship->title,
            'downloads' => $ship->downloads+1,
        ]);
    }
}
