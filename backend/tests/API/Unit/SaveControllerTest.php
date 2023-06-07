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
     * A basic test example.
     *
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
     * A basic test example.
     *
     * @return void
     */
    public function testCannotCreateSavesFromLocalFile() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $user = Factory::create('Shipyard\Models\User');
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/save', ['user_ref' => $user->ref, 'title' => $title, 'file_path' => 'tests/test.sav'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $save = Save::query()->where([['user_id', $user->id], ['title', $title], ['file_path', 'tests/test.sav']])->first();
        $this->assertNull($save);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanCreateSavesFromUploadedFile() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $description = $faker->paragraph();

        $this->post('api/v1/save', ['user_ref' => $user->ref, 'title' => $title, 'description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => $this->createSampleUpload('test.save')])
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
     * A basic test example.
     *
     * @return void
     */
    public function testCanEditOwnSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $save = Factory::create('Shipyard\Models\Save');
        $save->user_id = $user->id;
        $save->save();

        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);

        $this->post('api/v1/save/' . $save->ref, ['user_ref' => $user->ref, 'title' => $title, 'file_path' => '/'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $title,
        ]);

        $save = json_decode(Save::query()->where([['ref', $save->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $title,
        ], $save);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCannotEditOtherSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\Models\User');
        $save = Factory::create('Shipyard\Models\Save');
        $save->user_id = $user1->id;
        $save->save();

        $faker = \Faker\Factory::create();
        $oldtitle = $save->title;
        $title = $faker->words(3, true);

        $this->post('api/v1/save/' . $save->ref, ['user_ref' => $user->ref, 'title' => $title, 'file_path' => '/'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);

        $save = json_decode(Save::query()->where([['ref', $save->ref]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'title' => $oldtitle,
        ], $save);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanEditSavesWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\Models\User');
        $role_name = $faker->slug;
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        $role->givePermissionTo(Permission::query()->whereSlug('edit-saves')->first());
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $user1 = Factory::create('Shipyard\Models\User');
        $save = Factory::create('Shipyard\Models\Save');
        $save->user_id = $user1->id;
        $save->save();

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
     * A basic test example.
     *
     * @return void
     */
    public function testCanDeleteOwnSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();
        $save = Factory::create('Shipyard\Models\Save');
        $save->user_id = $user->id;
        $save->save();

        $this->assertEquals($save->ref, Save::query()->where([['ref', $save->ref]])->first()->ref);
        $this->delete('api/v1/save/' . $save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'message' => 'successful'
        ]);

        $this->expectException(ModelNotFoundException::class);
        Save::query()->findOrFail($save->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCannnotDeleteOtherSaves() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);
        $faker = \Faker\Factory::create();

        $user1 = Factory::create('Shipyard\Models\User');
        $save = Factory::create('Shipyard\Models\Save');
        $save->user_id = $user1->id;
        $save->save();

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
     * A basic test example.
     *
     * @return void
     */
    public function testCanDeleteSavesWithRole() {
        $faker = \Faker\Factory::create();

        $user = Factory::create('Shipyard\Models\User');
        $role_name = $faker->slug;
        $role = Role::query()->create(['slug' => $role_name, 'label' => $faker->name]);
        $role->givePermissionTo(Permission::query()->whereSlug('delete-saves')->first());
        $user->assignRole($role_name);
        $user->activate();
        Auth::login($user);

        $user1 = Factory::create('Shipyard\Models\User');
        $save = Factory::create('Shipyard\Models\Save');
        $save->user_id = $user1->id;
        $save->save();

        $this->assertEquals($save->ref, Save::query()->where([['ref', $save->ref]])->first()->ref);
        $this->delete('api/v1/save/' . $save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'message' => 'successful'
        ]);

        $this->expectException(ModelNotFoundException::class);
        Save::query()->findOrFail($save->id);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCanViewSaves() {
        $save = Factory::create('Shipyard\Models\Save');

        $this->get('api/v1/save/' . $save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $save->title,
        ]);
    }
}
