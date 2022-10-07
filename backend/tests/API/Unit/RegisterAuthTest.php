<?php

namespace Tests\API\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\User;
use Shipyard\UserActivation;
use Tests\APITestCase;

class UserControllerTest extends APITestCase {
    /*
     * Things to test:
     *  * List users
     *  * Login another user while a user is logged in
     *  * Remove a user
     *
     * Once groups/roles are functional, tests will need to be updated.
     */

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanRetrieveVersion() {
        $this->get('api/v1/version', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'app' => $_ENV['APP_TITLE'],
         ]);
    }

    /**
     * Check logged in user when not logged in.
     *
     * @return void
     */
    public function testCannotCheckLoggedInUserWhenNotLoggedIn() {
        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanRegisterUsers() {
        $faker = \Faker\Factory::create();
        $name = $faker->name;
        $email = $faker->unique()->safeEmail;
        $this->post('api/v1/register', [
            'name' => $name,
            'email' => $email,
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'name' => $name,
                'email' => $email,
         ]);

        $user = User::where([['email', $email]])->first();
        $user_json = json_decode($user->toJson(), true);
        $this->assertJsonFragment([
            'name' => $name,
            'email' => $email,
        ], $user_json);
        $this->assertEquals(false, $user->active());

        $user_activation = json_decode(UserActivation::where('email', $email)->firstOrFail()->toJson(), true);
        $this->assertJsonFragment([
            'email' => $email,
        ], $user_activation);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @depends testCanRegisterUsers
     *
     * @return void
     */
    public function testCannotRegisterUserWithExistingEmail() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\User');
        $user->activate();
        $password = password_hash('secret', PASSWORD_BCRYPT);
        $this->post('api/v1/register', [
            'name' => $faker->name,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'errors' => [
                'email' => ['Email is not unique.'],
            ],
        ]);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @depends testCanRegisterUsers
     *
     * @return void
     */
    public function testCanActivateUser() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\User');
        $activation = $user->create_activation();

        $this->post('api/v1/activate/' . $activation->token, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'name' => $user->name,
                'email' => $user->email,
         ]);

        $real_user = User::findOrFail($user->id);
        $this->assertTrue($real_user->active());

        $activation = !(UserActivation::where('email', $user->email)->get()->isEmpty());
        $this->assertFalse($activation);
        $this->assertTrue($real_user->id == $user->id);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanLoginValidUser() {
        $user = Factory::create('Shipyard\User');
        $user->activate();

        $this->post('api/v1/login', [
            'email' => $user->email,
            'password' => 'secret',
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                'access_token',
         ]);

        $token = json_decode($this->response->getBody(), true)['access_token'];
        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token])
             ->assertJsonResponse([
                'email' => $user->email,
        ]);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanLogoutValidUser() {
        $this->markTestIncomplete('Must implement proper token invalidation.');
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse(['email' => $user->email]);

        $this->get('api/v1/logout', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
         ->assertJsonResponse([
             'message' => 'You have been logged out.',
         ]);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
             ->assertStatus(401);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCannotLoginInvalidUser() {
        $user = Factory::create('Shipyard\User');
        $user->activate();

        $this->post('api/v1/login', [
            'email' => $user->email,
            'password' => 'notsecret',
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
            'message' => 'These credentials do not match our records.',
        ]);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCannotLoginInactiveUser() {
        $user = Factory::create('Shipyard\User');
        $user->create_activation();

        $this->post('api/v1/login', [
            'email' => $user->email,
            'password' => 'secret',
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonresponse([
            'message' => 'This account has not been activated. Please check your email.',
        ]);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testAdminCanEditUser() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\User');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        $newName = $faker->name;
        $newEmail = $faker->email;
        $newPass = password_hash('secret', PASSWORD_BCRYPT);
        Auth::login($admin);
        $token = Auth::generate_token();

        $this->post('api/v1/user/' . $user->id, ['name' => $newName], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse([
            'name' => $newName,
            'email' => $user->email,
        ]);
        $this->post('api/v1/user/' . $user->id, ['name' => $user->name, 'email' => $newEmail], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse([
            'name' => $user->name,
            'email' => $newEmail,
        ]);
        $this->post('api/v1/user/' . $user->id, ['password' => $newPass, 'password_confirmation' => $newPass], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse([
            'name' => $user->name,
            'email' => $newEmail,
        ]);

        $user2 = json_decode(User::where([['name', $user->name], ['email', $newEmail]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'name' => $user->name,
            'email' => $newEmail,
        ], $user2);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanSelfEditUser() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\User');
        $user->activate();
        $newName = $faker->name;
        $newEmail = $faker->email;
        $newPass = password_hash('secret', PASSWORD_BCRYPT);
        Auth::login($user);
        $token = Auth::generate_token();

        $this->post('api/v1/user/' . $user->id, ['name' => $newName], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse([
            'name' => $newName,
            'email' => $user->email,
        ]);
        $this->post('api/v1/user/' . $user->id, ['name' => $user->name, 'email' => $newEmail], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse([
            'name' => $user->name,
            'email' => $newEmail,
        ]);
        $this->post('api/v1/user/' . $user->id, ['password' => $newPass, 'password_confirmation' => $newPass], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse([
            'name' => $user->name,
            'email' => $newEmail,
        ]);

        $user2 = json_decode(User::where([['name', $user->name], ['email', $newEmail]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'name' => $user->name,
            'email' => $newEmail,
        ], $user2);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCannotEditOtherUser() {
        $faker = \Faker\Factory::create();
        $user = Factory::create('Shipyard\User');
        $other = Factory::create('Shipyard\User');
        $user->activate();
        $other->activate();
        $newName = $faker->name;
        Auth::login($other);
        $token = Auth::generate_token();

        $this->post('api/v1/user/' . $user->id, ['name' => $newName], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertStatus(403);

        $user2 = json_decode(User::where([['name', $user->name], ['email', $user->email]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'name' => $user->name,
            'email' => $user->email,
        ], $user2);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testAdminCanDeleteUser() {
        $user = Factory::create('Shipyard\User');
        $admin = Factory::create('Shipyard\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);
        $token = Auth::generate_token();

        $this->delete('api/v1/user/' . $user->id, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse([
            'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        User::findOrFail($user->id);
        $this->expectException(ModelNotFoundException::class);
        UserActivation::where('email', $user->email)->firstOrFail();
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanSelfDeleteUser() {
        $user = Factory::create('Shipyard\User');
        $user->activate();
        Auth::login($user);
        $token = Auth::generate_token();

        $this->delete('api/v1/user/' . $user->id, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertJsonResponse([
            'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        User::findOrFail($user->id);
        $this->expectException(ModelNotFoundException::class);
        UserActivation::where('email', $user->email)->firstOrFail();
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCannotDeleteAnotherUser() {
        $user = Factory::create('Shipyard\User');
        $other = Factory::create('Shipyard\User');
        $user->activate();
        $other->activate();
        Auth::login($other);
        $token = Auth::generate_token();

        $this->delete('api/v1/user/' . $user->id, ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Authorization' => 'Bearer ' . $token->toString()])
        ->assertStatus(403);

        $user2 = json_decode(User::where([['name', $user->name], ['email', $user->email]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'name' => $user->name,
            'email' => $user->email,
        ], $user2);
    }
}
