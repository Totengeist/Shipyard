<?php

namespace Tests\API\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\User;
use Shipyard\Models\UserActivation;
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

        /** @var User $user */
        $user = User::query()->where([['email', $email]])->first();
        $user->makeVisible(['email']);
        $user_json = json_decode($user->toJson(), true);
        $this->assertJsonFragment([
            'name' => $name,
            'email' => $email,
        ], $user_json);
        $this->assertEquals(false, $user->active());

        $user_activation = json_decode(UserActivation::query()->where('email', $email)->firstOrFail()->toJson(), true);
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
        $user = Factory::create('Shipyard\Models\User');
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
        $user = Factory::create('Shipyard\Models\User');
        $activation = $user->create_activation();

        $this->get('api/v1/activate/' . $activation->token, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'name' => $user->name,
                 'email' => $user->email,
             ]);

        /** @var User $real_user */
        $real_user = User::query()->where('ref', $user->ref)->first();
        $this->assertTrue($real_user->active());

        $this->assertTrue(UserActivation::query()->where('email', $user->email)->get()->isEmpty());
        $this->assertTrue($real_user->ref == $user->ref);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanLoginValidUser() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();

        $this->post('api/v1/login', [
            'email' => $user->email,
            'password' => 'secret',
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'name' => $user->name,
             ]);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'name' => $user->name,
             ]);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanLogoutValidUser() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse(['email' => $user->email]);

        $this->post('api/v1/logout', [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
         ->assertJsonResponse([
             'message' => 'You have been logged out.',
         ]);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCannotLoginInvalidUser() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();

        $this->post('api/v1/login', [
            'email' => $user->email,
            'password' => 'notsecret',
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'errors' => ['These credentials do not match our records.'],
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
        $user = Factory::create('Shipyard\Models\User');
        $user->create_activation();

        $this->post('api/v1/login', [
            'email' => $user->email,
            'password' => 'secret',
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonresponse([
            'errors' => ['This account has not been activated. Please check your email.'],
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
        $user = Factory::create('Shipyard\Models\User');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        $newName = $faker->name;
        $newEmail = $faker->email;
        $newPass = password_hash('secret', PASSWORD_BCRYPT);
        Auth::login($admin);

        $this->post('api/v1/user/' . $user->ref, ['name' => $newName], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse([
            'name' => $newName,
            'email' => $user->email,
        ]);
        $this->post('api/v1/user/' . $user->ref, ['name' => $user->name, 'email' => $newEmail], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse([
            'name' => $user->name,
            'email' => $newEmail,
        ]);
        $this->post('api/v1/user/' . $user->ref, ['password' => $newPass, 'password_confirmation' => $newPass], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse([
            'name' => $user->name,
            'email' => $newEmail,
        ]);

        $user2 = json_decode(User::query()->where([['name', $user->name], ['email', $newEmail]])->first()->makeVisible(['email'])->toJson(), true); // @phpstan-ignore-line
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
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        $newName = $faker->name;
        $newEmail = $faker->email;
        $newPass = password_hash('secret', PASSWORD_BCRYPT);
        Auth::login($user);

        $this->post('api/v1/user/' . $user->ref, ['name' => $newName], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse([
            'name' => $newName,
            'email' => $user->email,
        ]);
        $this->post('api/v1/user/' . $user->ref, ['name' => $user->name, 'email' => $newEmail], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse([
            'name' => $user->name,
            'email' => $newEmail,
        ]);
        $this->post('api/v1/user/' . $user->ref, ['password' => $newPass, 'password_confirmation' => $newPass], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse([
            'name' => $user->name,
            'email' => $newEmail,
        ]);

        $user2 = json_decode(User::query()->where([['name', $user->name], ['email', $newEmail]])->first()->makeVisible(['email'])->toJson(), true); // @phpstan-ignore-line
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
        $user = Factory::create('Shipyard\Models\User');
        $other = Factory::create('Shipyard\Models\User');
        $user->activate();
        $other->activate();
        $newName = $faker->name;
        Auth::login($other);

        $this->post('api/v1/user/' . $user->ref, ['name' => $newName], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertStatus(403);

        $user2 = json_decode(User::query()->where([['name', $user->name], ['email', $user->email]])->first()->makeVisible(['email'])->toJson(), true); // @phpstan-ignore-line
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
        $user = Factory::create('Shipyard\Models\User');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->delete('api/v1/user/' . $user->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse([
            'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        User::query()->findOrFail($user->ref);
        $this->expectException(ModelNotFoundException::class);
        UserActivation::query()->where('email', $user->email)->firstOrFail();
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCanSelfDeleteUser() {
        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->delete('api/v1/user/' . $user->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonResponse([
            'message' => 'successful',
        ]);

        $this->expectException(ModelNotFoundException::class);
        User::query()->findOrFail($user->ref);
        $this->expectException(ModelNotFoundException::class);
        UserActivation::query()->where('email', $user->email)->firstOrFail();
    }

    /**
     * Insert a user item without a custom slug.
     *
     * @return void
     */
    public function testCannotDeleteAnotherUser() {
        $user = Factory::create('Shipyard\Models\User');
        $other = Factory::create('Shipyard\Models\User');
        $user->activate();
        $other->activate();
        Auth::login($other);

        $this->delete('api/v1/user/' . $user->ref, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
        ->assertStatus(403);

        $user2 = json_decode(User::query()->where([['name', $user->name], ['email', $user->email]])->first()->makeVisible(['email'])->toJson(), true); // @phpstan-ignore-line
        $this->assertJsonFragment([
            'name' => $user->name,
            'email' => $user->email,
        ], $user2);
    }
}
