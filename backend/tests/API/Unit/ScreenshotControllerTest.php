<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\Screenshot;
use Shipyard\Models\Ship;
use Tests\APITestCase;

class ScreenshotControllerTest extends APITestCase {
    /**
     * @return void
     */
    public function testCanListShipScreenshots() {
        $ship1 = Factory::create('Shipyard\Models\Ship');
        $ship2 = Factory::create('Shipyard\Models\Ship');
        $screenshot1 = Factory::create('Shipyard\Models\Screenshot');
        $screenshot2 = Factory::create('Shipyard\Models\Screenshot');
        $ship1->assignScreenshot($screenshot1);
        $ship2->assignScreenshot($screenshot2);

        $this->get('api/v1/ship/' . $ship1->ref . '/screenshots', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $screenshot1->ref,
                 'description' => $screenshot1->description,
             ])->assertJsonResponse([
                 'ref' => $screenshot2->ref,
                 'description' => $screenshot2->description,
             ], true);
    }

    /**
     * @return void
     */
    public function testCanListSaveScreenshots() {
        $save1 = Factory::create('Shipyard\Models\Save');
        $save2 = Factory::create('Shipyard\Models\Save');
        $screenshot1 = Factory::create('Shipyard\Models\Screenshot');
        $screenshot2 = Factory::create('Shipyard\Models\Screenshot');
        $save1->assignScreenshot($screenshot1);
        $save2->assignScreenshot($screenshot2);

        $this->get('api/v1/save/' . $save1->ref . '/screenshots', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $screenshot1->ref,
                 'description' => $screenshot1->description,
             ])->assertJsonResponse([
                 'ref' => $screenshot2->ref,
                 'description' => $screenshot2->description,
             ], true);
    }

    /**
     * @return void
     */
    public function testCanListModificationScreenshots() {
        $modification1 = Factory::create('Shipyard\Models\Modification');
        $modification2 = Factory::create('Shipyard\Models\Modification');
        $screenshot1 = Factory::create('Shipyard\Models\Screenshot');
        $screenshot2 = Factory::create('Shipyard\Models\Screenshot');
        $modification1->assignScreenshot($screenshot1);
        $modification2->assignScreenshot($screenshot2);

        $this->get('api/v1/modification/' . $modification1->ref . '/screenshots', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $screenshot1->ref,
                 'description' => $screenshot1->description,
             ])->assertJsonResponse([
                 'ref' => $screenshot2->ref,
                 'description' => $screenshot2->description,
             ], true);
    }

    /**
     * @return void
     */
    public function testOwnerCanCreateShipScreenshots() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);
        $description = $faker->paragraph();
        $user->activate();
        Auth::login($user);

        $this->post('api/v1/ship/' . $ship->ref . '/screenshots', ['description' => [$description]], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [self::createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
                 'description' => $description,
             ]);

        $screenshot = json_decode(Screenshot::query()->whereHas('ships', function ($q) use ($ship) {
            $q->where('id', $ship->id);
        })->first()->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * @return void
     */
    public function testAdminCanCreateShipScreenshots() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $ship = Factory::create('Shipyard\Models\Ship');
        $description = $faker->paragraph();

        $this->post('api/v1/ship/' . $ship->ref . '/screenshots', ['description' => [$description]], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [self::createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
                 'description' => $description,
             ]);

        $screenshot = json_decode(Screenshot::query()->whereHas('ships', function ($q) use ($ship) {
            $q->where('id', $ship->id);
        })->first()->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * @return void
     */
    public function testAdminCanCreateSaveScreenshots() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $save = Factory::create('Shipyard\Models\Save');
        $description = $faker->paragraph();

        $this->post('api/v1/save/' . $save->ref . '/screenshots', ['description' => [$description]], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [self::createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
                 'description' => $description,
             ]);

        $screenshot = json_decode(Screenshot::query()->whereHas('saves', function ($q) use ($save) {
            $q->where('id', $save->id);
        })->first()->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * @return void
     */
    public function testAdminCanCreateModScreenshots() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $modification = Factory::create('Shipyard\Models\Modification');
        $description = $faker->paragraph();

        $this->post('api/v1/modification/' . $modification->ref . '/screenshots', ['description' => [$description]], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [self::createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
                 'description' => $description,
             ]);

        $screenshot = json_decode(Screenshot::query()->whereHas('modifications', function ($q) use ($modification) {
            $q->where('id', $modification->id);
        })->first()->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * @return void
     */
    public function testNonOwnerCannotCreateScreenshots() {
        $faker = \Faker\Factory::create();
        $user1 = Factory::create('Shipyard\Models\User');
        $user2 = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user1->id]);
        $description = $faker->paragraph();
        $user2->activate();
        Auth::login($user2);

        $this->post('api/v1/ship/' . $ship->ref . '/screenshots', ['description' => [$description]], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [self::createSampleUpload('science-vessel.png')]])
             ->assertStatus(403);

        $screenshot = Screenshot::query()->whereHas('ships', function ($q) use ($ship) {
            $q->where('id', $ship->id);
        })->first();
        $this->assertNull($screenshot);
    }

    /**
     * @return void
     */
    public function testAdminCanCreateEmptyScreenshots() {
        $ship = Factory::create('Shipyard\Models\Ship');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->post('api/v1/ship/' . $ship->ref . '/screenshots', [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [self::createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
                 'description' => null,
             ]);
    }

    /**
     * @return void
     */
    public function testOtherUserCannotEditScreenshots() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);
        $screenshot = $ship->screenshots()->create([
            'description' => $faker->paragraph(),
            'file_id' => $faker->randomDigit(),
        ], ['type' => Ship::$tag_label]);
        $description = $faker->paragraph();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/screenshot/' . $screenshot->ref, ['description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user2 = Factory::create('Shipyard\Models\User');
        $user2->activate();
        Auth::login($user2);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/screenshot/' . $screenshot->ref, ['description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [self::createSampleUpload('science-vessel.png')]])
             ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function testUserCanEditScreenshots() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);
        $screenshot = $ship->screenshots()->create([
            'description' => $faker->paragraph(),
            'file_id' => $faker->randomDigit(),
        ], ['type' => Ship::$tag_label]);
        $description = $faker->paragraph();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/screenshot/' . $screenshot->ref, ['description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/screenshot/' . $screenshot->ref, ['description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [self::createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
                 'description' => $description,
             ]);

        $screenshot = json_decode(Screenshot::query()->find($screenshot->id)->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * @return void
     */
    public function testAdminCanEditScreenshots() {
        $faker = \Faker\Factory::create();
        $ship = Factory::create('Shipyard\Models\Ship');
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship->screenshots()->attach($screenshot, ['type' => 'ship']);
        $description = $faker->paragraph();

        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->post('api/v1/screenshot/' . $screenshot->ref, ['description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'description' => $description,
             ]);

        $screenshot = json_decode(Screenshot::query()->find($screenshot->id)->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * @return void
     */
    public function testCanDownloadScreenshots() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');

        $this->get('api/v1/screenshot/' . $screenshot->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertEquals((string) $this->response->getBody(), $screenshot->file->file_contents());
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'filename="' . $screenshot->file->filename . '.' . $screenshot->file->extension . '"');

        $this->get('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $screenshot->ref,
                 'description' => $screenshot->description,
             ]);
    }

    /**
     * @return void
     */
    public function testOtherUserCannotDeleteScreenshots() {
        $user = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship->screenshots()->attach($screenshot, ['type' => 'ship']);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user2 = Factory::create('Shipyard\Models\User');
        $user2->activate();
        Auth::login($user2);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->delete('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function testUserCanDeleteScreenshots() {
        $user = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship->screenshots()->attach($screenshot, ['type' => 'ship']);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->delete('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'message' => 'successful',
             ]);

        $this->expectException(ModelNotFoundException::class);
        Screenshot::query()->findOrFail($screenshot->id);
    }

    /**
     * @return void
     */
    public function testAdminCanDeleteScreenshots() {
        $ship = Factory::create('Shipyard\Models\Ship');
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $ship->screenshots()->attach($screenshot, ['type' => 'ship']);
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        /** @var Screenshot $dbScreenshot */
        $dbScreenshot = Screenshot::query()->find($screenshot->id);
        $this->assertEquals($screenshot->id, $dbScreenshot->id);
        $this->delete('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'message' => 'successful',
             ]);

        $this->expectException(ModelNotFoundException::class);
        Screenshot::query()->findOrFail($screenshot->id);
    }

    /**
     * @return void
     */
    public function testGuestCanViewAScreenshot() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');

        $this->get('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'description' => $screenshot->description,
             ]);
    }
}
