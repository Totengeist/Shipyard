<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\Permission;
use Shipyard\Models\Role;
use Shipyard\Models\Ship;
use Tests\APITestCase;

class ShipControllerTest extends APITestCase {
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

        $this->post('api/v1/ship', ['title' => $title, 'file_path' => 'tests/assets/science-vessel.ship'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $ship = Ship::query()->where([['user_id', $user->id], ['title', $title]])->first();
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

        $this->post('api/v1/ship', ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload()])
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

        $this->post('api/v1/ship/' . $ship->ref, ['title' => $title], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
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

        $this->post('api/v1/ship/' . $ship->ref, ['title' => $title], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
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
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'edit-ships');
        /** @var Permission $permission */
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
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'delete-ships');
        /** @var Permission $permission */
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
        $this->assertEquals((string) $this->response->getBody(), $ship->file->file_contents());
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $ship->file->filename . '.' . $ship->file->extension . '"');

        $this->get('api/v1/ship/' . $ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'ref' => $ship->ref,
            'title' => $ship->title,
            'downloads' => $ship->downloads+1,
        ]);
    }

    /**
     * @return void
     */
    public function testCanUpgradeShip() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph(3, true);

        $this->post('api/v1/ship/' . $ship->ref . '/upgrade', ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload()])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $ship2_json = json_decode((string) $this->response->getBody(), true);
        $ship2_object = Ship::query()->where([['ref', $ship2_json['ref']]])->firstOrFail();
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $ship2_json);
        $this->assertEquals($ship2_object->parent->ref, $ship->ref);
    }

    /**
     * @return void
     */
    public function testCanDeleteShipVersion() {
        $user = Factory::create('Shipyard\Models\User');
        $faker = \Faker\Factory::create();
        $role_name = $faker->slug;
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'delete-ships');
        /** @var Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $ship1 = Factory::create('Shipyard\Models\Ship');
        $ship2 = Factory::create('Shipyard\Models\Ship', ['parent_id' => $ship1->id]);
        $ship3 = Factory::create('Shipyard\Models\Ship', ['parent_id' => $ship2->id]);
        $ship4 = Factory::create('Shipyard\Models\Ship', ['parent_id' => $ship3->id]);
        $ship5 = Factory::create('Shipyard\Models\Ship', ['parent_id' => $ship4->id]);

        // delete first
        $this->delete('api/v1/ship/' . $ship1->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($ship5->parent->parent->parent->parent, null);
        $this->assertEquals($ship5->parent->parent->parent->parent_id, null);

        // delete middle
        $ship5 = Ship::query()->where([['ref', $ship5->ref]])->firstOrFail();
        $this->delete('api/v1/ship/' . $ship3->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($ship5->parent->parent->getKey(), $ship2->getKey());
        $this->assertEquals($ship5->parent->parent->child->getKey(), $ship4->getKey());
        $this->assertEquals($ship5->parent->parent_id, $ship2->id);

        // delete last
        $ship5 = Ship::query()->where([['ref', $ship5->ref]])->firstOrFail();
        $this->delete('api/v1/ship/' . $ship5->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($ship5->parent->parent->getKey(), $ship2->getKey());
        $this->assertEquals($ship5->parent->parent_id, $ship2->id);
    }
}
