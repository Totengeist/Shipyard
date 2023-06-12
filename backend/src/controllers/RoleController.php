<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Models\Role;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class RoleController extends Controller {
    use ChecksPermissions;
    use ProcessesSlugs;

    /**
     * Display a listing of the resource.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response) {
        if (($perm_check = $this->can('view-roles')) !== null) {
            return $perm_check;
        }

        $payload = (string) json_encode(Role::all());
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function store(Request $request, Response $response) {
        if (($perm_check = $this->can('create-roles')) !== null) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();
        if (!array_key_exists('slug', $data) || $data['slug'] === null || $data['slug'] === '') {
            $data['slug'] = self::slugify($data['label']);
        }
        $validator = $this->slug_validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();

        if (count($errors)) {
            $payload = (string) json_encode(['errors' => $errors]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }
        $payload = (string) json_encode(Role::query()->create($data));

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Display the specified resource.
     *
     * @param array<string,string> $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function show(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('view-roles')) !== null) {
            return $perm_check;
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Role::query()->where([['slug', $args['slug']]]);
        $payload = (string) json_encode($query->first());

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array<string,string> $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('edit-roles')) !== null) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Role::query()->where([['slug', $args['slug']]]);
        /** @var \Shipyard\Models\Role $role */
        $role = $query->first();
        $role->slug = $data['slug'];
        $role->label = $data['label'];
        $role->save();

        $payload = (string) json_encode($role);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param array<string,string> $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function destroy(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('delete-roles')) !== null) {
            return $perm_check;
        }
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Role::query()->where([['slug', $args['slug']]]);
        /** @var \Shipyard\Models\Role $role */
        $role = $query->first();
        $role->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
