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
     * A basic test example.
     *
     * @return void
     */
    public function testCanListScreenshots() {
        $ship1 = Factory::create('Shipyard\Models\Ship');
        $ship2 = Factory::create('Shipyard\Models\Ship');
        $screenshot1 = Factory::create('Shipyard\Models\Screenshot');
        $screenshot2 = Factory::create('Shipyard\Models\Screenshot');
        $ship1->assignScreenshot($screenshot1);
        $ship2->assignScreenshot($screenshot2);

        $this->get('api/v1/screenshots/' . $ship1->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'ref' => $screenshot1->ref,
            'description' => $screenshot1->description,
         ])->assertJsonResponse([
            'ref' => $screenshot2->ref,
            'description' => $screenshot2->description,
         ], true);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testOwnerCanCreateScreenshots() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);
        $description = $faker->paragraph();
        $user->activate();
        Auth::login($user);

        $return = $this->post('api/v1/screenshots/' . $ship->ref, ['description' => [$description]], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [$this->createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
            'description' => $description,
        ]);

        $screenshot = json_decode(Screenshot::whereHas('ships', function ($q) use ($ship) {
            $q->where('id', $ship->id);
        })->first()->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanCreateScreenshots() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $ship = Factory::create('Shipyard\Models\Ship');
        $description = $faker->paragraph();

        $this->post('api/v1/screenshots/' . $ship->ref, ['description' => [$description]], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [$this->createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
            'description' => $description,
        ]);

        $screenshot = json_decode(Screenshot::whereHas('ships', function ($q) use ($ship) {
            $q->where('id', $ship->id);
        })->first()->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * A basic test example.
     *
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

        $return = $this->post('api/v1/screenshots/' . $ship->ref, ['description' => [$description]], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [$this->createSampleUpload('science-vessel.png')]])
             ->assertStatus(403);

        $screenshot = Screenshot::whereHas('ships', function ($q) use ($ship) {
            $q->where('id', $ship->id);
        })->first();
        $this->assertNull($screenshot);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanCreateEmptyScreenshots() {
        $faker = \Faker\Factory::create();
        $ship = Factory::create('Shipyard\Models\Ship');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = '';

        $this->post('api/v1/screenshots/' . $ship->ref, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [$this->createSampleUpload('science-vessel.png')]])
             ->assertJsonResponse([
            'description' => null,
        ]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUserCannotEditScreenshots() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\Models\User');
        $ship = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);
        $screenshot = $ship->screenshots()->create([
            'description' => $faker->paragraph(),
            'file_path' => realpath(__DIR__ . '/../../assets/science-vessel.png'),
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
        $this->post('api/v1/screenshot/' . $screenshot->ref, ['description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], ['file' => [$this->createSampleUpload('science-vessel.png')]])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanEditScreenshots() {
        $faker = \Faker\Factory::create();
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $description = $faker->paragraph();

        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->post('api/v1/screenshot/' . $screenshot->ref, ['description' => $description], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'description' => $description,
        ]);

        $screenshot = json_decode(Screenshot::find($screenshot->id)->toJson(), true);
        $this->assertJsonFragment([
            'description' => $description,
        ], $screenshot);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUserCannotDeleteScreenshots() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->delete('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAdminCanDeleteScreenshots() {
        $screenshot = Factory::create('Shipyard\Models\Screenshot');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->assertEquals($screenshot->id, Screenshot::find($screenshot->id)->id);
        $this->delete('api/v1/screenshot/' . $screenshot->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        Screenshot::findOrFail($screenshot->id);
    }

    /**
     * A basic test example.
     *
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
