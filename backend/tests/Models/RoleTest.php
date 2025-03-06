<?php

namespace Tests\Models;

use Laracasts\TestDummy\Factory;
use Shipyard\Models\Role;
use Tests\TestCase;

class RoleModelTest extends TestCase {
    /**
     * @return void
     */
    public function testCanCreateRole() {
        $faker = \Faker\Factory::create();
        /** @var Role $role1 */
        $role1 = Role::query()->create([
            'slug' => $faker->slug,
            'label' => $faker->words(3, true),
        ]);

        /** @var Role $role2 */
        $role2 = Role::query()->findOrFail($role1->id);
        $this->assertEquals($role1->label, $role2->label);
    }

    public function testCanCheckPermissionsOfEmptyRole() {
        $role = Factory::create('Shipyard\Models\Role');

        $this->assertTrue($role->permissions()->get()->isEmpty());
    }

    public function testCanAddAndRemovePermissionOnRole() {
        $role = Factory::create('Shipyard\Models\Role');
        $perm = Factory::create('Shipyard\Models\Permission');

        $role->givePermissionTo($perm);
        $this->assertFalse($role->permissions()->get()->isEmpty());
        $this->assertEquals($perm->id, $role->permissions->first()->id);

        $role->removePermissionTo($perm);

        $this->assertTrue($role->permissions()->get()->isEmpty());
    }
}
