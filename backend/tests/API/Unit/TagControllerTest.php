<?php

namespace Tests\Unit\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laracasts\TestDummy\Factory;
use Shipyard\Auth;
use Shipyard\Models\Tag;
use Shipyard\Traits\ProcessesSlugs;
use Tests\APITestCase;

class TagControllerTest extends APITestCase {
    use ProcessesSlugs;

    /**
     * @return void
     */
    public function testCanListTags() {
        $tag = Factory::create('Shipyard\Models\Tag');
        $this->get('api/v1/tag', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $tag->slug,
                 'label' => $tag->label,
             ]);
    }

    /**
     * @return void
     */
    public function testUserCannotCreateTags() {
        $faker = \Faker\Factory::create();
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/tag', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/tag', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function testAdminCanCreateTags() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/tag', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $slug,
                 'label' => $label,
             ]);

        $tag = json_decode(Tag::query()->where([['slug', $slug], ['label', $label]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $tag);
    }

    /**
     * @return void
     */
    public function testAdminCanCreateTagsWithoutSlugs() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $label = $faker->words(3, true);
        /** @var string $label */
        $slug = self::slugify($label);

        $this->post('api/v1/tag', ['label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $slug,
                 'label' => $label,
             ]);

        $tag = json_decode(Tag::query()->where([['label', $label]])->first()->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $tag);
    }

    /**
     * @return void
     */
    public function testAdminCannotCreateEmptyTags() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = '';

        $this->post('api/v1/tag', ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'label' => ['Label is required'],
             ]);
    }

    /**
     * @return void
     */
    public function testUserCannotEditTags() {
        $faker = \Faker\Factory::create();
        $tag = Factory::create('Shipyard\Models\Tag');
        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->post('api/v1/tag/' . $tag->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->post('api/v1/tag/' . $tag->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function testAdminCanEditTags() {
        $faker = \Faker\Factory::create();
        $tag = Factory::create('Shipyard\Models\Tag');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $slug = $faker->slug;
        $label = $faker->words(3, true);

        $this->post('api/v1/tag/' . $tag->slug, ['slug' => $slug, 'label' => $label], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $slug,
                 'label' => $label,
             ]);

        $tag = json_decode(Tag::query()->find($tag->id)->toJson(), true);
        $this->assertJsonFragment([
            'slug' => $slug,
            'label' => $label,
        ], $tag);
    }

    /**
     * @return void
     */
    public function testUserCannotDeleteTags() {
        $tag = Factory::create('Shipyard\Models\Tag');

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);
        $this->delete('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(401);

        $user = Factory::create('Shipyard\Models\User');
        $user->activate();
        Auth::login($user);

        $this->get('api/v1/me', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(200);
        $this->delete('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function testAdminCanDeleteTags() {
        $tag = Factory::create('Shipyard\Models\Tag');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        /** @var Tag $dbTag */
        $dbTag = Tag::query()->find($tag->id);
        $this->assertEquals($tag->id, $dbTag->id);
        $this->delete('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'message' => 'successful',
             ]);

        $this->expectException(ModelNotFoundException::class);
        Tag::query()->findOrFail($tag->id);
    }

    /**
     * @return void
     */
    public function testUserCanViewATag() {
        $tag = Factory::create('Shipyard\Models\Tag');

        $this->get('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $tag->slug,
                 'label' => $tag->label,
             ]);
    }

    /**
     * @return void
     */
    public function testUserCannotViewUpdateOrDeleteANonexistentTag() {
        $faker = \Faker\Factory::create();
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->get('api/v1/tag/' . $faker->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(404);
        $this->post('api/v1/tag/' . $faker->slug, ['label' => 'no'], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(404);
        $this->delete('api/v1/tag/' . $faker->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(404);
    }

    /**
     * @return void
     */
    public function testAdminCanViewATag() {
        $tag = Factory::create('Shipyard\Models\Tag');
        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->get('api/v1/tag/' . $tag->slug, ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $tag->slug,
                 'label' => $tag->label,
             ]);
    }

    /**
     * @return void
     */
    public function testCanSearchForTags() {
        $faker = \Faker\Factory::create();
        /** @var string $label1 */
        $label1 = $faker->words(3, true);
        /** @var string $label2 */
        $label2 = $faker->words(3, true);
        /** @var string $label3 */
        $label3 = $faker->words(3, true);

        $tag1 = Factory::create('Shipyard\Models\Tag', ['label' => 'test' . $label1]);
        $tag2 = Factory::create('Shipyard\Models\Tag', ['label' => 'test' . $label2]);
        $tag3 = Factory::create('Shipyard\Models\Tag', ['label' => 'locked' . $label3, 'locked' => true]);

        $this->get('api/v1/search/tag/test', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $tag1->slug,
                 'label' => $tag1->label,
             ])->assertJsonResponse([
                 'slug' => $tag2->slug,
                 'label' => $tag2->label,
             ]);

        $this->get('api/v1/search/tag/;', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponseEmpty();

        $admin = Factory::create('Shipyard\Models\User');
        $admin->activate();
        $admin->assignRole('administrator');
        Auth::login($admin);

        $this->get('api/v1/search/tag/locked', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'slug' => $tag3->slug,
                 'label' => $tag3->label,
             ]);

        $this->get('api/v1/search/tag/this-cant-find-anything-right', ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
             ->assertJsonResponse([
                 'data' => [],
             ]);
    }
}
