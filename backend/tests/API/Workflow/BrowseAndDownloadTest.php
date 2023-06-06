<?php

namespace Tests\Unit\API;

use Laracasts\TestDummy\Factory;
use Shipyard\Models\Ship;
use Shipyard\Traits\ProcessesSlugs;
use Tests\APITestCase;

class BrowseAndDownloadWorkflowTest extends APITestCase {
    use ProcessesSlugs;

    /**
     * Test all API calls neede when a user browses for and downloads a ship (without searching).
     *
     * Workflow:
     *  1. Visit homepage
     *  2. Choose a ship
     *  3. Download the ship (increment download counter)
     *  4. Follow instructions to install the ship
     *
     * @return void
     */
    public function testCanBrowseToAndDownloadAShip() {
        // Create a user and assign 5 ships to them to populate the ship list.
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\Models\User');
        $ships = [];
        for ($i = 0; $i < 5; $i++) {
            $ships[$i] = Factory::create('Shipyard\Models\Ship', ['user_id' => $user->id]);
        }

        $chosen_ship = $ships[rand(0, 4)];

        $this->get('api/v1/ship', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'title' => $ships[0]->title,
        ]);

        $this->get('api/v1/ship/' . $chosen_ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'ref' => $chosen_ship->ref,
            'title' => $chosen_ship->title,
        ]);

        $this->get('api/v1/ship/' . $chosen_ship->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertNotEquals((string) $this->response->getBody(), '');
        $this->assertEquals((string) $this->response->getBody(), $chosen_ship->file_contents());
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . self::slugify($chosen_ship->title) . '.ship"');

        $this->get('api/v1/ship/' . $chosen_ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'ref' => $chosen_ship->ref,
            'title' => $chosen_ship->title,
            'downloads' => $chosen_ship->downloads+1,
        ]);
    }
}
