<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Shipyard\Models\Permission;
use Shipyard\Models\Role;

echo "Dropping tables.<br>\n";
Capsule::schema()->dropIfExists('item_screenshots');
Capsule::schema()->dropIfExists('screenshots');
Capsule::schema()->dropIfExists('item_tags');
Capsule::schema()->dropIfExists('tags');
Capsule::schema()->dropIfExists('item_releases');
Capsule::schema()->dropIfExists('modifications');
Capsule::schema()->dropIfExists('saves');
Capsule::schema()->dropIfExists('ships');
Capsule::schema()->dropIfExists('files');
Capsule::schema()->dropIfExists('role_user');
Capsule::schema()->dropIfExists('permission_role');
Capsule::schema()->dropIfExists('permissions');
Capsule::schema()->dropIfExists('roles');
Capsule::schema()->dropIfExists('password_resets');
Capsule::schema()->dropIfExists('user_activations');
Capsule::schema()->dropIfExists('users');
Capsule::schema()->dropIfExists('releases');
Capsule::schema()->dropIfExists('meta');

echo "Creating meta table.<br>\n";
Capsule::schema()->create('meta', function ($table) {
    $table->increments('id');
    $table->string('name')->unique();
    $table->string('section');
    $table->string('type');
    $table->string('default');
    $table->string('value')->nullable();
    $table->string('description');
});
echo "Creating releases table.<br>\n";
Capsule::schema()->create('releases', function ($table) {
    $table->increments('id');
    $table->string('slug')->unique();
    $table->string('label')->nullable();
    $table->text('description')->nullable();
    $table->timestamps();
});
echo "Creating users table.<br>\n";
Capsule::schema()->create('users', function ($table) {
    $table->increments('id');
    $table->string('name');
    $table->string('ref')->unique();
    $table->string('email')->unique();
    $table->string('password');
    $table->boolean('activated')->default(false);
    $table->bigInteger('steamid')->unsigned()->nullable();
    $table->bigInteger('discordid')->unsigned()->nullable();
    $table->rememberToken();
    $table->timestamps();
});
echo "Creating user activations table.<br>\n";
Capsule::schema()->create('user_activations', function ($table) {
    $table->string('email');
    $table->string('token')->index();
    $table->timestamp('created_at')->nullable();
});
echo "Creating password resets table.<br>\n";
Capsule::schema()->create('password_resets', function ($table) {
    $table->string('email')->index();
    $table->string('token');
    $table->timestamp('created_at')->nullable();
});
echo "Creating roles table.<br>\n";
Capsule::schema()->create('roles', function ($table) {
    $table->increments('id');
    $table->string('slug')->unique();
    $table->string('label')->nullable();
    $table->timestamps();
});
echo "Creating permissions table.<br>\n";
Capsule::schema()->create('permissions', function ($table) {
    $table->increments('id');
    $table->string('slug')->unique();
    $table->string('label')->nullable();
    $table->timestamps();
});
echo "Creating link table between roles and permissions table.<br>\n";
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
echo "Creating link table between users and roles table.<br>\n";
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

echo "Creating files table.<br>\n";
Capsule::schema()->create('files', function ($table) {
    $table->increments('id')->unsigned();
    $table->string('filename');
    $table->string('media_type')->default(null);
    $table->string('extension');
    $table->string('filepath');
    $table->boolean('compressed')->default(false); // was it compressed by Shipyard?
    $table->timestamps();
});
echo "Creating ships table.<br>\n";
Capsule::schema()->create('ships', function ($table) {
    $table->increments('id')->unsigned();
    $table->bigInteger('parent_id')->unsigned()->nullable();
    $table->string('ref')->unique();
    $table->bigInteger('user_id')->unsigned()->nullable();
    $table->integer('flags')->unsigned()->default(0);
    $table->string('title')->default(false);
    $table->text('description');
    $table->bigInteger('file_id');
    $table->bigInteger('downloads')->unsigned()->nullable(false)->default('0');
    $table->timestamps();
});
echo "Creating saves table.<br>\n";
Capsule::schema()->create('saves', function ($table) {
    $table->increments('id')->unsigned();
    $table->bigInteger('parent_id')->unsigned()->nullable();
    $table->string('ref')->unique();
    $table->bigInteger('user_id')->unsigned()->nullable();
    $table->integer('flags')->unsigned()->default(0);
    $table->string('title')->default(false);
    $table->text('description');
    $table->bigInteger('file_id');
    $table->bigInteger('downloads')->unsigned()->nullable(false)->default('0');
    $table->timestamps();
});
echo "Creating modifications table.<br>\n";
Capsule::schema()->create('modifications', function ($table) {
    $table->increments('id')->unsigned();
    $table->bigInteger('parent_id')->unsigned()->nullable();
    $table->string('ref')->unique();
    $table->bigInteger('user_id')->unsigned()->nullable();
    $table->integer('flags')->unsigned()->default(0);
    $table->string('title')->default(false);
    $table->text('description');
    $table->bigInteger('file_id');
    $table->bigInteger('downloads')->unsigned()->nullable(false)->default('0');
    $table->timestamps();
});
echo "Creating link table between items and releases table.<br>\n";
Capsule::schema()->create('item_releases', function ($table) {
    $table->integer('release_id')->unsigned();
    $table->integer('item_id')->unsigned();
    $table->string('type');
    $table->foreign('release_id')
          ->references('id')
          ->on('releases')
          ->onDelete('cascade');
    $table->primary(['release_id', 'item_id', 'type']);
});
echo "Creating tags table.<br>\n";
Capsule::schema()->create('tags', function ($table) {
    $table->increments('id')->unsigned();
    $table->string('slug')->unique();
    $table->string('label')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
});
echo "Creating link table between items and tags table.<br>\n";
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
echo "Creating screenshots table.<br>\n";
Capsule::schema()->create('screenshots', function ($table) {
    $table->increments('id')->unsigned();
    $table->string('ref')->unique();
    $table->text('description')->nullable();
    $table->bigInteger('file_id');
    $table->timestamps();
});
echo "Creating link table between items and tags table.<br>\n";
Capsule::schema()->create('item_screenshots', function ($table) {
    $table->integer('screenshot_id')->unsigned();
    $table->integer('item_id')->unsigned();
    $table->string('type');
    $table->boolean('primary')->default(false);
    $table->foreign('screenshot_id')
          ->references('id')
          ->on('screenshots')
          ->onDelete('cascade');
    $table->primary(['screenshot_id', 'item_id', 'type']);
});

echo "Set schema version.<br>\n";
Capsule::table('meta')->insert(
    ['name' => 'schema_version', 'section' => 'hidden', 'type' => 'integer', 'default' => '1', 'description' => 'The schema version.']
);

/**
 * @param array<string, string> $params
 *
 * @return Permission
 */
function create_permission($params) {
    /** @var Permission $return */
    $return = Permission::query()->create($params);

    return $return;
}

// ships
$edit_ships = create_permission(['slug' => 'edit-ships', 'label' => 'edit ships']);
$delete_ships = create_permission(['slug' => 'delete-ships', 'label' => 'delete ships']);
$edit_saves = create_permission(['slug' => 'edit-saves', 'label' => 'edit saves']);
$delete_saves = create_permission(['slug' => 'delete-saves', 'label' => 'delete saves']);
$edit_modifications = create_permission(['slug' => 'edit-modifications', 'label' => 'edit modifications']);
$delete_modifications = create_permission(['slug' => 'delete-modifications', 'label' => 'delete modifications']);

// users
$delete_users = create_permission(['slug' => 'delete-users', 'label' => 'delete users']);
$edit_users   = create_permission(['slug' => 'edit-users',   'label' => 'edit users']);

// roles
$view_roles   = create_permission(['slug' => 'view-roles',   'label' => 'view roles']);
$create_roles = create_permission(['slug' => 'create-roles', 'label' => 'create roles']);
$edit_roles   = create_permission(['slug' => 'edit-roles',   'label' => 'edit roles']);
$delete_roles = create_permission(['slug' => 'delete-roles', 'label' => 'delete roles']);

// permissions
$view_permissions    = create_permission(['slug' => 'view-permissions',   'label' => 'view permissions']);
$create_permissions  = create_permission(['slug' => 'create-permissions', 'label' => 'create permissions']);
$edit_permissions    = create_permission(['slug' => 'edit-permissions',   'label' => 'edit permissions']);
$delete_permissions  = create_permission(['slug' => 'delete-permissions', 'label' => 'delete permissions']);

// tags
$create_tags  = create_permission(['slug' => 'create-tags', 'label' => 'create tags']);
$edit_tags    = create_permission(['slug' => 'edit-tags',   'label' => 'edit tags']);
$delete_tags  = create_permission(['slug' => 'delete-tags', 'label' => 'delete tags']);

// releases
$create_releases  = create_permission(['slug' => 'create-releases', 'label' => 'create releases']);
$edit_releases    = create_permission(['slug' => 'edit-releases',   'label' => 'edit releases']);
$delete_releases  = create_permission(['slug' => 'delete-releases', 'label' => 'delete releases']);

// screenshots
$create_screenshots  = create_permission(['slug' => 'create-screenshots', 'label' => 'create screenshots']);
$edit_screenshots    = create_permission(['slug' => 'edit-screenshots',   'label' => 'edit screenshots']);
$delete_screenshots  = create_permission(['slug' => 'delete-screenshots', 'label' => 'delete screenshots']);

// administrator permissions
/**
 * @var Role $admin
 */
$admin = Role::query()->create(['slug' => 'administrator', 'label' => 'Administrator']);
$admin->givePermissionTo($edit_ships);
$admin->givePermissionTo($edit_saves);
$admin->givePermissionTo($edit_modifications);
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
$admin->givePermissionTo($create_tags);
$admin->givePermissionTo($edit_tags);
$admin->givePermissionTo($delete_tags);
$admin->givePermissionTo($create_releases);
$admin->givePermissionTo($edit_releases);
$admin->givePermissionTo($delete_releases);
$admin->givePermissionTo($create_screenshots);
$admin->givePermissionTo($edit_screenshots);
$admin->givePermissionTo($delete_screenshots);
