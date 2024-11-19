<?php

namespace Tests\Unit\API;

use Laracasts\TestDummy\Factory;
use Shipyard\Traits\ProcessesSlugs;
use Tests\APITestCase;

class BrowseAndDownloadWorkflowTest extends APITestCase {
    use ProcessesSlugs;

    /**
     * Test all API calls needed when a user browses for and downloads a ship (without searching).
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
        $this->assertEquals((string) $this->response->getBody(), stream_get_contents(gzopen($chosen_ship->file->getFilePath(), 'r')));
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $chosen_ship->file->filename . '.' . $chosen_ship->file->extension . '"');

        $this->get('api/v1/ship/' . $chosen_ship->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $chosen_ship->ref,
                 'title' => $chosen_ship->title,
                 'downloads' => $chosen_ship->downloads+1,
             ]);
    }

    /**
     * Test all API calls needed when a user browses for and downloads a save (without searching).
     *
     * Workflow:
     *  1. Visit homepage
     *  2. Choose a save
     *  3. Download the save (increment download counter)
     *  4. Follow instructions to install the save
     *
     * @return void
     */
    public function testCanBrowseToAndDownloadASave() {
        // Create a user and assign 5 saves to them to populate the save list.
        $user = Factory::create('Shipyard\Models\User');
        $saves = [];
        for ($i = 0; $i < 5; $i++) {
            $saves[$i] = Factory::create('Shipyard\Models\Save', ['user_id' => $user->id]);
        }

        $chosen_save = $saves[rand(0, 4)];

        $this->get('api/v1/save', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'title' => $saves[0]->title,
             ]);

        $this->get('api/v1/save/' . $chosen_save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $chosen_save->ref,
                 'title' => $chosen_save->title,
             ]);

        $this->get('api/v1/save/' . $chosen_save->ref . '/download', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertNotEquals((string) $this->response->getBody(), '');
        $this->assertEquals((string) $this->response->getBody(), stream_get_contents(gzopen($chosen_save->file->getFilePath(), 'r')));
        $this->assertEquals($this->response->getHeader('Content-Disposition')[0], 'attachment; filename="' . $chosen_save->file->filename . '.' . $chosen_save->file->extension . '"');

        $this->get('api/v1/save/' . $chosen_save->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'ref' => $chosen_save->ref,
                 'title' => $chosen_save->title,
                 'downloads' => $chosen_save->downloads+1,
             ]);
    }
}
