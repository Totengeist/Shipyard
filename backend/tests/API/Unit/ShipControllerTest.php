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
             ->assertStatus(422);

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
    public function testCanCreateUnlistedAndPrivateShips() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph();

        $this->post('api/v1/ship', ['title' => $title, 'description' => $description, 'state' => ['unlisted', 'private', 'locked']], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload()])
             ->assertJsonResponse([
                 'title' => $title,
                 'description' => $description,
             ]);

        /** @var Ship $ship */
        $ship = Ship::query()->where([['title', $title], ['description', $description]])->first();
        $this->assertEquals(7, $ship->flags);
        $this->assertTrue($ship->isUnlisted());
        $this->assertFalse($ship->isListed());
        $this->assertTrue($ship->isPrivate());
        $this->assertFalse($ship->isPublic());
        $this->assertTrue($ship->isLocked());
    }

    /**
     * @return void
     */
    public function testListedAndUnlistedShipsShowAppropriately() {
        $user = Factory::create('Shipyard\Models\User');
        $ship1 = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id, 'flags' => 0]);
        $ship2 = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id, 'flags' => 1]);
        $ship3 = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id, 'flags' => 2]);
        $ship4 = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id, 'flags' => 3]);

        $this->get('api/v1/ship', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $ship1->ref,
             ])
             ->assertJsonResponse([
                 'ref' => $ship2->ref,
             ], true)
             ->assertJsonResponse([
                 'ref' => $ship3->ref,
             ], true)
             ->assertJsonResponse([
                 'ref' => $ship4->ref,
             ], true);

        Auth::login($user);

        $this->get('api/v1/ship', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $ship1->ref,
             ])
             ->assertJsonResponse([
                 'ref' => $ship2->ref,
             ])
             ->assertJsonResponse([
                 'ref' => $ship3->ref,
             ])
             ->assertJsonResponse([
                 'ref' => $ship4->ref,
             ]);
    }

    /**
     * @return void
     */
    public function testCanEditOwnShips() {
        $user = Factory::create('Shipyard\Models\User');
        $user2 = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/ship/' . $ship->ref, ['title' => $title, 'user_ref' => $user2->ref], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'title' => $title,
             ]);

        $ship = json_decode(Ship::query()->where([['ref', $ship->ref]])->with('user')->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'ref' => $user2->ref,
        ], $ship);
    }

    /**
     * @return void
     */
    public function testCanUnlistAndPrivateAndLockShips() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);

        $this->post('api/v1/ship/' . $ship->ref, ['state' => ['unlisted', 'private', 'locked']], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'flags' => 7,
             ]);

        $ship = json_decode(Ship::query()->where([['ref', $ship->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'flags' => 7,
        ], $ship);
    }

    /**
     * @return void
     */
    public function testCannotEditOtherShips() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

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

        $stream = gzopen($ship->file->getFilePath(), 'r');

        $this->assertNotEquals((string) $this->response->getBody(), '');
        $this->assertNotFalse($stream);
        $this->assertEquals((string) $this->response->getBody(), stream_get_contents($stream));
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
    public function testCanDownloadUnlistedButNotPrivateShips() {
        $user = Factory::create('Shipyard\Models\User');
        $ship1 = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id, 'flags' => 0]);
        $ship2 = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id, 'flags' => 1]);
        $ship3 = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id, 'flags' => 2]);
        $ship4 = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id, 'flags' => 3]);

        $this->get('api/v1/ship/' . $ship1->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $ship1->file->filename . '.' . $ship1->file->extension . '"');
        $this->get('api/v1/ship/' . $ship3->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $ship3->file->filename . '.' . $ship3->file->extension . '"');

        $this->get('api/v1/ship/' . $ship2->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse(['errors' => ['Ship not found']]);
        $this->get('api/v1/ship/' . $ship4->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse(['errors' => ['Ship not found']]);

        Auth::login($user);

        $this->get('api/v1/ship/' . $ship2->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $ship1->file->filename . '.' . $ship1->file->extension . '"');
        $this->get('api/v1/ship/' . $ship4->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $ship1->file->filename . '.' . $ship1->file->extension . '"');
    }

    /**
     * @return void
     */
    public function testCanUpgradeShip() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
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
        $this->assertEquals($ship5->parent->parent->children->first()->getKey(), $ship4->getKey());
        $this->assertEquals($ship5->parent->parent_id, $ship2->id);

        // delete last
        $ship5 = Ship::query()->where([['ref', $ship5->ref]])->firstOrFail();
        $this->delete('api/v1/ship/' . $ship5->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($ship5->parent->parent->getKey(), $ship2->getKey());
        $this->assertEquals($ship5->parent->parent_id, $ship2->id);
    }
}
