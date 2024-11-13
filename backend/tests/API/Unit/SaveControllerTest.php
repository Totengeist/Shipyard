<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\Permission;
use Shipyard\Models\Role;
use Shipyard\Models\Save;
use Tests\APITestCase;

class SaveControllerTest extends APITestCase {
    /**
     * @return void
     */
    public function testCanListSaves() {
        $save = Factory::create('Shipyard\Models\Save');
        $this->get('api/v1/save', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'title' => $save->title,
             ]);
    }

    /**
     * @return void
     */
    public function testCannotCreateSavesFromLocalFile() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/save', ['title' => $title, 'file_path' => 'tests/test.sav'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $save = Save::query()->where([['user_id', $user->id], ['title', $title]])->first();
        $this->assertNull($save);
    }

    /**
     * @return void
     */
    public function testCanCreateSavesFromUploadedFile() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph();

        $this->post('api/v1/save', ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload('Battle.space')])
             ->assertJsonResponse([
                 'title' => $title,
                 'description' => $description,
             ]);

        $save = json_decode(Save::query()->where([['title', $title], ['description', $description]])->with('user')->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $save);
    }

    /**
     * @return void
     */
    public function testCanCreateUnlistedAndPrivateAndLockedSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph();

        $this->post('api/v1/save', ['title' => $title, 'description' => $description, 'state' => ['unlisted', 'private', 'locked']], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload('Battle.space')])
             ->assertJsonResponse([
                 'title' => $title,
                 'description' => $description,
             ]);

        $save = Save::query()->where([['title', $title], ['description', $description]])->first();
        $this->assertEquals($save->flags, 7);
        $this->assertTrue($save->isUnlisted());
        $this->assertFalse($save->isListed());
        $this->assertTrue($save->isPrivate());
        $this->assertFalse($save->isPublic());
        $this->assertTrue($save->isLocked());
    }

    /**
     * @return void
     */
    public function testListedAndUnlistedSavesShowAppropriately() {
        $user = Factory::create('Shipyard\Models\User');
        $save1 = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id, 'flags' => 0]);
        $save2 = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id, 'flags' => 1]);
        $save3 = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id, 'flags' => 2]);
        $save4 = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id, 'flags' => 3]);

        $this->get('api/v1/save', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $save1->ref,
             ])
             ->assertJsonResponse([
                 'ref' => $save2->ref,
                 'ref' => $save3->ref,
                 'ref' => $save4->ref,
             ], true);

        Auth::login($user);

        $this->get('api/v1/save', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $save1->ref,
                 'ref' => $save2->ref,
                 'ref' => $save3->ref,
                 'ref' => $save4->ref,
             ]);
    }

    /**
     * @return void
     */
    public function testCanEditOwnSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user2 = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $save = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id]);

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/save/' . $save->ref, ['title' => $title, 'user_ref' => $user2->ref], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'title' => $title,
             ]);

        $save = json_decode(Save::query()->where([['ref', $save->ref]])->with('user')->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'ref' => $user2->ref,
        ], $save);
    }

    /**
     * @return void
     */
    public function testCanUnlistAndPrivateAndLockSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $save = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id]);

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/save/' . $save->ref, ['state' => ['unlisted', 'private', 'locked']], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'flags' => 7,
             ]);

        $save = json_decode(Save::query()->where([['ref', $save->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'flags' => 7,
        ], $save);
    }

    /**
     * @return void
     */
    public function testCannotEditOtherSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\Models\User');
        $save = Factory::create('Shipyard\Models\Save', ['user_id' => $user1->id]);

        $faker = \Faker\Factory::create();
        $oldtitle = $save->title;
        $title = $faker->words(3, true);

        $this->post('api/v1/save/' . $save->ref, ['title' => $title], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);

        $save = json_decode(Save::query()->where([['ref', $save->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $oldtitle,
        ], $save);
    }

    /**
     * @return void
     */
    public function testCanEditSavesWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\Models\User');
        $role_name = $faker->slug;
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'edit-saves');
        /** @var Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $user1 = Factory::create('Shipyard\Models\User');
        $save = Factory::create('Shipyard\Models\Save', ['user_id' => $user1->id]);

        $title = $faker->words(3, true);
        $description = $faker->paragraph;

        $this->post('api/v1/save/' . $save->ref, ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'title' => $title,
                 'description' => $description,
             ]);

        $save = json_decode(Save::query()->where([['ref', $save->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $save);
    }

    /**
     * @return void
     */
    public function testCanDeleteOwnSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $save = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id]);

        $this->assertEquals($save->ref, Save::query()->where([['ref', $save->ref]])->first()->ref);
        $this->delete('api/v1/save/' . $save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'message' => 'successful'
             ]);

        $this->expectException(ModelNotFoundException::class);
        Save::query()->findOrFail($save->id);
    }

    /**
     * @return void
     */
    public function testCannnotDeleteOtherSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\Models\User');
        $save = Factory::create('Shipyard\Models\Save', ['user_id' => $user1->id]);

        $title = $save->title;
        $description = $save->description;

        $this->delete('api/v1/save/' . $save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);

        $save = json_decode(Save::query()->where([['ref', $save->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $save);
    }

    /**
     * @return void
     */
    public function testCanDeleteSavesWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\Models\User');
        $role_name = $faker->slug;
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'delete-saves');
        /** @var Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $user1 = Factory::create('Shipyard\Models\User');
        $save = Factory::create('Shipyard\Models\Save', ['user_id' => $user1->id]);

        $this->assertEquals($save->ref, Save::query()->where([['ref', $save->ref]])->first()->ref);
        $this->delete('api/v1/save/' . $save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'message' => 'successful'
             ]);

        $this->expectException(ModelNotFoundException::class);
        Save::query()->findOrFail($save->id);
    }

    /**
     * @return void
     */
    public function testCanViewSaves() {
        $save = Factory::create('Shipyard\Models\Save');

        $this->get('api/v1/save/' . $save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'title' => $save->title,
             ]);
    }

    /**
     * @return void
     */
    public function testCanDownloadSaves() {
        $save = Factory::create('Shipyard\Models\Save');

        $this->get('api/v1/save/' . $save->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertNotEquals((string) $this->response->getBody(), '');
        $this->assertEquals((string) $this->response->getBody(), $save->file->file_contents());
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $save->file->filename . '.' . $save->file->extension . '"');

        $this->get('api/v1/save/' . $save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $save->ref,
                 'title' => $save->title,
                 'downloads' => $save->downloads+1,
             ]);
    }

    /**
     * @return void
     */
    public function testCanDownloadUnlistedButNotPrivateSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $save1 = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id, 'flags' => 0]);
        $save2 = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id, 'flags' => 1]);
        $save3 = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id, 'flags' => 2]);
        $save4 = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id, 'flags' => 3]);

        $this->get('api/v1/save/' . $save1->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $save1->file->filename . '.' . $save1->file->extension . '"');
        $this->get('api/v1/save/' . $save3->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $save3->file->filename . '.' . $save3->file->extension . '"');

        $this->get('api/v1/save/' . $save2->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse(['errors' => ['Save not found']]);
        $this->get('api/v1/save/' . $save4->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse(['errors' => ['Save not found']]);

        Auth::login($user);

        $this->get('api/v1/save/' . $save2->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $save1->file->filename . '.' . $save1->file->extension . '"');
        $this->get('api/v1/save/' . $save4->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $save1->file->filename . '.' . $save1->file->extension . '"');
    }

    /**
     * @return void
     */
    public function testCanUpgradeSave() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $save = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id]);

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph(3, true);

        $this->post('api/v1/save/' . $save->ref . '/upgrade', ['title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => self::createSampleUpload()])
             ->assertJsonResponse([
                 'title' => $title,
                 'description' => $description,
             ]);

        $save2_json = json_decode((string) $this->response->getBody(), true);
        $save2_object = Save::query()->where([['ref', $save2_json['ref']]])->firstOrFail();
        $this->assertJsonFragment([
            'title' => $title,
            'description' => $description,
        ], $save2_json);
        $this->assertEquals($save2_object->parent->ref, $save->ref);
    }

    /**
     * @return void
     */
    public function testCanDeleteSaveVersion() {
        $user = Factory::create('Shipyard\Models\User');
        $faker = \Faker\Factory::create();
        $role_name = $faker->slug;
        /** @var Role $role */
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where('slug', 'delete-saves');
        /** @var Permission $permission */
        $permission = $query->first();
        $role->givePermissionTo($permission);
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $save1 = Factory::create('Shipyard\Models\Save');
        $save2 = Factory::create('Shipyard\Models\Save', ['parent_id' => $save1->id]);
        $save3 = Factory::create('Shipyard\Models\Save', ['parent_id' => $save2->id]);
        $save4 = Factory::create('Shipyard\Models\Save', ['parent_id' => $save3->id]);
        $save5 = Factory::create('Shipyard\Models\Save', ['parent_id' => $save4->id]);

        // delete first
        $this->delete('api/v1/save/' . $save1->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($save5->parent->parent->parent->parent, null);
        $this->assertEquals($save5->parent->parent->parent->parent_id, null);

        // delete middle
        $save5 = Save::query()->where([['ref', $save5->ref]])->firstOrFail();
        $this->delete('api/v1/save/' . $save3->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($save5->parent->parent->getKey(), $save2->getKey());
        $this->assertEquals($save5->parent->parent->child->getKey(), $save4->getKey());
        $this->assertEquals($save5->parent->parent_id, $save2->id);

        // delete last
        $save5 = Save::query()->where([['ref', $save5->ref]])->firstOrFail();
        $this->delete('api/v1/save/' . $save5->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertEquals($save5->parent->parent->getKey(), $save2->getKey());
        $this->assertEquals($save5->parent->parent_id, $save2->id);
    }
}
