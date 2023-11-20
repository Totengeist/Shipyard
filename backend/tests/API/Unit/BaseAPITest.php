<?php

namespace Tests\Unit\API;

use Tests\APITestCase;

class BaseAPITest extends APITestCase {
    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanRetrieveVersion() {
        $this->get('api/v1/version', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'app' => $_SERVER['APP_TITLE'],
         ]);
    }

    /**
     * @return void
     */
    public function testPathDoesNotExist() {
        $this->get('api/v1/blarg', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(404);
    }
}
