<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\Modification;
use Shipyard\Models\Permission;
use Shipyard\Models\Role;
use Tests\APITestCase;

class ModificationControllerTest extends APITestCase {
    /**
     * @return void
     */
    public function testCanListModifications() {
        $modification = Factory::create('Shipyard\Models\Modification');
        $this->get('api/v1/modification', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $modification->title,
         ]);
    }

    /**
     * @return void
     */
    public function testCannotCreateModificationsFromLocalFile() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/modification', ['title' => $title, 'file_path' => 'tests/test.sav'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $modification = Modification::query()->where([['user_id', $user->id], ['title', $title]])->first();
        $this->assertNull($modification);
    }

    /**
     * @return void
     */
    public function testCanCreateModificationsFromUploadedFile() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph();

        $this->post('api/v1/modification', ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload('Battle.space')])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $modification = json_decode(Modification::query()->where([['title', $title], ['description', $description]])->with('user')->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $modification);
    }

    /**
     * @return void
     */
    public function testCanEditOwnModifications() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $modification = Factory::create('Shipyard\Models\Modification', ['user_id' => $user->id]);

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/modification/' . $modification->ref, ['title' => $title], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $title,
        ]);

        $modification = json_decode(Modification::query()->where([['ref', $modification->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
        ], $modification);
    }

    /**
     * @return void
     */
    public function testCannotEditOtherModifications() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\Models\User');
        $modification = Factory::create('Shipyard\Models\Modification', ['user_id' => $user1->id]);

        $faker = \Faker\Factory::create();
        $oldtitle = $modification->title;
        $title = $faker->words(3, true);

        $this->post('api/v1/modification/' . $modification->ref, ['title' => $title], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);

        $modification = json_decode(Modification::query()->where([['ref', $modification->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $oldtitle,
        ], $modification);
    }

    /**
     * @return void
     */
    public function testCanEditModificationsWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\Models\User');
        $role_name = $faker->slug;
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'edit-modifications');
        /** @var Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $user1 = Factory::create('Shipyard\Models\User');
        $modification = Factory::create('Shipyard\Models\Modification', ['user_id' => $user1->id]);

        $title = $faker->words(3, true);
        $description = $faker->paragraph;

        $this->post('api/v1/modification/' . $modification->ref, ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $modification = json_decode(Modification::query()->where([['ref', $modification->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $modification);
    }

    /**
     * @return void
     */
    public function testCanDeleteOwnModifications() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $modification = Factory::create('Shipyard\Models\Modification', ['user_id' => $user->id]);

        $this->assertEquals($modification->ref, Modification::query()->where([['ref', $modification->ref]])->first()->ref);
        $this->delete('api/v1/modification/' . $modification->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'message' => 'successful'
        ]);

        $this->expectException(ModelNotFoundException::class);
        Modification::query()->findOrFail($modification->id);
    }

    /**
     * @return void
     */
    public function testCannnotDeleteOtherModifications() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\Models\User');
        $modification = Factory::create('Shipyard\Models\Modification', ['user_id' => $user1->id]);

        $title = $modification->title;
        $description = $modification->description;

        $this->delete('api/v1/modification/' . $modification->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);

        $modification = json_decode(Modification::query()->where([['ref', $modification->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $modification);
    }

    /**
     * @return void
     */
    public function testCanDeleteModificationsWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\Models\User');
        $role_name = $faker->slug;
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'delete-modifications');
        /** @var Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $user1 = Factory::create('Shipyard\Models\User');
        $modification = Factory::create('Shipyard\Models\Modification', ['user_id' => $user1->id]);

        $this->assertEquals($modification->ref, Modification::query()->where([['ref', $modification->ref]])->first()->ref);
        $this->delete('api/v1/modification/' . $modification->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'message' => 'successful'
        ]);

        $this->expectException(ModelNotFoundException::class);
        Modification::query()->findOrFail($modification->id);
    }

    /**
     * @return void
     */
    public function testCanViewModifications() {
        $modification = Factory::create('Shipyard\Models\Modification');

        $this->get('api/v1/modification/' . $modification->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $modification->title,
        ]);
    }

    /**
     * @return void
     */
    public function testCanDownloadModifications() {
        $modification = Factory::create('Shipyard\Models\Modification');

        $this->get('api/v1/modification/' . $modification->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertNotEquals((string) $this->response->getBody(), '');
        $this->assertEquals((string) $this->response->getBody(), $modification->file->file_contents());
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $modification->file->filename . '.' . $modification->file->extension . '"');

        $this->get('api/v1/modification/' . $modification->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'ref' => $modification->ref,
            'title' => $modification->title,
            'downloads' => $modification->downloads+1,
        ]);
    }

    /**
     * @return void
     */
    public function testCanUpgradeModification() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $modification = Factory::create('Shipyard\Models\Modification', ['user_id' => $user->id]);

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph(3, true);

        $this->post('api/v1/modification/' . $modification->ref . '/upgrade', ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload()])
             ->assertJsonResponse([
            'title' => $title,
            'description' => $description,
        ]);

        $modification2_json = json_decode((string) $this->response->getBody(), true);
        $modification2_object = Modification::query()->where([['ref', $modification2_json['ref']]])->firstOrFail();
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $modification2_json);
        $this->assertEquals($modification2_object->parent->ref, $modification->ref);
    }

    /**
     * @return void
     */
    public function testCanDeleteModificationVersion() {
        $user = Factory::create('Shipyard\Models\User');
        $faker = \Faker\Factory::create();
        $role_name = $faker->slug;
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'delete-modifications');
        /** @var Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $modification1 = Factory::create('Shipyard\Models\Modification');
        $modification2 = Factory::create('Shipyard\Models\Modification', ['parent_id' => $modification1->id]);
        $modification3 = Factory::create('Shipyard\Models\Modification', ['parent_id' => $modification2->id]);
        $modification4 = Factory::create('Shipyard\Models\Modification', ['parent_id' => $modification3->id]);
        $modification5 = Factory::create('Shipyard\Models\Modification', ['parent_id' => $modification4->id]);

        // delete first
        $this->delete('api/v1/modification/' . $modification1->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($modification5->parent->parent->parent->parent, null);
        $this->assertEquals($modification5->parent->parent->parent->parent_id, null);

        // delete middle
        $modification5 = Modification::query()->where([['ref', $modification5->ref]])->firstOrFail();
        $this->delete('api/v1/modification/' . $modification3->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($modification5->parent->parent->getKey(), $modification2->getKey());
        $this->assertEquals($modification5->parent->parent->child->getKey(), $modification4->getKey());
        $this->assertEquals($modification5->parent->parent_id, $modification2->id);

        // delete last
        $modification5 = Modification::query()->where([['ref', $modification5->ref]])->firstOrFail();
        $this->delete('api/v1/modification/' . $modification5->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($modification5->parent->parent->getKey(), $modification2->getKey());
        $this->assertEquals($modification5->parent->parent_id, $modification2->id);
    }
}
