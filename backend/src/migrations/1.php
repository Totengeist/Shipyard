<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Shipyard\Permission;
use Shipyard\Role;

echo 'Dropping tables.<br>';
Capsule::schema()->dropIfExists('item_tags');
Capsule::schema()->dropIfExists('tags');
Capsule::schema()->dropIfExists('challenges');
Capsule::schema()->dropIfExists('saves');
Capsule::schema()->dropIfExists('ships');
Capsule::schema()->dropIfExists('role_user');
Capsule::schema()->dropIfExists('permission_role');
Capsule::schema()->dropIfExists('permissions');
Capsule::schema()->dropIfExists('roles');
Capsule::schema()->dropIfExists('password_resets');
Capsule::schema()->dropIfExists('user_activations');
Capsule::schema()->dropIfExists('users');
Capsule::schema()->dropIfExists('meta');

echo 'Creating meta table.<br>';
Capsule::schema()->create('meta', function ($table) {
    $table->increments('id');
    $table->string('name')->unique();
    $table->string('section');
    $table->string('type');
    $table->string('default');
    $table->string('value')->nullable();
    $table->string('description');
});
echo 'Creating users table.<br>';
Capsule::schema()->create('users', function ($table) {
    $table->increments('id');
    $table->string('name');
    $table->string('ref')->unique();
    $table->string('email')->unique();
    $table->string('password');
    $table->boolean('activated')->default(false);
    $table->rememberToken();
    $table->timestamps();
});
echo 'Creating user activations table.<br>';
Capsule::schema()->create('user_activations', function ($table) {
    $table->string('email');
    $table->string('token')->index();
    $table->timestamp('created_at')->nullable();
});
echo 'Creating password resets table.<br>';
Capsule::schema()->create('password_resets', function ($table) {
    $table->string('email')->index();
    $table->string('token');
    $table->timestamp('created_at')->nullable();
});
echo 'Creating roles table.<br>';
Capsule::schema()->create('roles', function ($table) {
    $table->increments('id');
    $table->string('slug')->unique();
    $table->string('label')->nullable();
    $table->timestamps();
});
echo 'Creating permissions table.<br>';
Capsule::schema()->create('permissions', function ($table) {
    $table->increments('id');
    $table->string('slug')->unique();
    $table->string('label')->nullable();
    $table->timestamps();
});
echo 'Creating link table between roles and permissions table.<br>';
Capsule::schema()->create('permission_role', function ($table) {
    $table->integer('permission_id')->unsigned();
    $table->integer('role_id')->unsigned();
    $table->foreign('permission_id')
          ->references('id')
          ->on('permissions')
          ->onDelete('cascade');
    $table->foreign('role_id')
          ->references('id')
          ->on('roles')
          ->onDelete('cascade');
    $table->primary(['permission_id', 'role_id']);
});
echo 'Creating link table between users and roles table.<br>';
Capsule::schema()->create('role_user', function ($table) {
    $table->integer('role_id')->unsigned();
    $table->integer('user_id')->unsigned();
    $table->foreign('role_id')
          ->references('id')
          ->on('roles')
          ->onDelete('cascade');
    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->onDelete('cascade');
    $table->primary(['role_id', 'user_id']);
});

echo 'Creating ships table.<br>';
Capsule::schema()->create('ships', function ($table) {
    $table->increments('id')->unsigned();
    $table->string('ref')->unique();
    $table->bigInteger('user_id')->unsigned()->nullable();
    $table->string('title')->default(false);
    $table->string('description')->unique();
    $table->string('file_path');
    $table->bigInteger('downloads')->unsigned()->nullable(false)->default('0');
    $table->timestamps();
});
echo 'Creating saves table.<br>';
Capsule::schema()->create('saves', function ($table) {
    $table->increments('id')->unsigned();
    $table->string('ref')->unique();
    $table->bigInteger('user_id')->unsigned()->nullable();
    $table->string('title')->default(false);
    $table->string('description')->unique();
    $table->string('file_path');
    $table->bigInteger('downloads')->unsigned()->nullable(false)->default('0');
    $table->timestamps();
});
echo 'Creating challenges table.<br>';
Capsule::schema()->create('challenges', function ($table) {
    $table->increments('id')->unsigned();
    $table->string('ref')->unique();
    $table->bigInteger('user_id')->unsigned()->nullable();
    $table->bigInteger('save_id')->unsigned()->nullable();
    $table->string('title')->default(false);
    $table->string('description')->unique();
    $table->timestamps();
});
echo 'Creating tags table.<br>';
Capsule::schema()->create('tags', function ($table) {
    $table->increments('id')->unsigned();
    $table->string('slug')->unique();
    $table->string('label')->unique();
    $table->string('description')->nullable();
    $table->timestamps();
});
echo 'Creating link table between items and tags table.<br>';
Capsule::schema()->create('item_tags', function ($table) {
    $table->integer('tag_id')->unsigned();
    $table->integer('item_id')->unsigned();
    $table->string('type');
    $table->foreign('tag_id')
          ->references('id')
          ->on('tags')
          ->onDelete('cascade');
    $table->primary(['tag_id', 'item_id', 'type']);
});

echo 'Set schema version.<br>';
Capsule::table('meta')->insert(
    ['name' => 'schema_version', 'section' => 'hidden', 'type' => 'integer', 'default' => '1', 'description' => 'The schema version.']
);
// ships
$edit_ships = Permission::create(['slug' => 'edit-ships', 'label' => 'edit ships']);
$edit_ships = Permission::create(['slug' => 'delete-ships', 'label' => 'delete ships']);
$edit_saves = Permission::create(['slug' => 'edit-saves', 'label' => 'edit saves']);
$edit_saves = Permission::create(['slug' => 'delete-saves', 'label' => 'delete saves']);
$edit_challenges = Permission::create(['slug' => 'edit-challenges', 'label' => 'edit challenges']);
$edit_challenges = Permission::create(['slug' => 'delete-challenges', 'label' => 'delete challenges']);

// users
$delete_users = Permission::create(['slug' => 'delete-users', 'label' => 'delete users']);
$edit_users   = Permission::create(['slug' => 'edit-users',   'label' => 'edit users']);

// roles
$view_roles   = Permission::create(['slug' => 'view-roles',   'label' => 'view roles']);
$create_roles = Permission::create(['slug' => 'create-roles', 'label' => 'create roles']);
$edit_roles   = Permission::create(['slug' => 'edit-roles',   'label' => 'edit roles']);
$delete_roles = Permission::create(['slug' => 'delete-roles', 'label' => 'delete roles']);

// permissions
$view_permissions    = Permission::create(['slug' => 'view-permissions',   'label' => 'view permissions']);
$create_permissions  = Permission::create(['slug' => 'create-permissions', 'label' => 'create permissions']);
$edit_permissions    = Permission::create(['slug' => 'edit-permissions',   'label' => 'edit permissions']);
$delete_permissions  = Permission::create(['slug' => 'delete-permissions', 'label' => 'delete permissions']);

// administrator permissions
$admin = Role::create(['slug' => 'administrator', 'label' => 'Administrator']);
$admin->givePermissionTo($edit_ships);
$admin->givePermissionTo($edit_saves);
$admin->givePermissionTo($edit_challenges);
$admin->givePermissionTo($delete_users);
$admin->givePermissionTo($edit_users);
$admin->givePermissionTo($view_roles);
$admin->givePermissionTo($create_roles);
$admin->givePermissionTo($edit_roles);
$admin->givePermissionTo($delete_roles);
$admin->givePermissionTo($view_permissions);
$admin->givePermissionTo($create_permissions);
$admin->givePermissionTo($edit_permissions);
$admin->givePermissionTo($delete_permissions);