<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Models\Permission;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class PermissionController extends Controller {
    use ChecksPermissions;
    use ProcessesSlugs;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Response $response) {
        if (($perm_check = $this->can('view-permissions')) !== true) {
            return $perm_check;
        }

        $payload = (string) json_encode(Permission::all());
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request, Response $response) {
        if (($perm_check = $this->can('create-permissions')) !== true) {
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
            return $this->invalid_input_response($errors);
        }
        $payload = (string) json_encode(Permission::query()->create($data));

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Display the specified resource.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function show(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('view-permissions')) !== true) {
            return $perm_check;
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where([['slug', $args['slug']]]);
        $permission = $query->first();
        if ($permission == null) {
            return $this->not_found_response('Permission');
        }
        $payload = (string) json_encode($permission);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function update(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('edit-permissions')) !== true) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where([['slug', $args['slug']]]);
        /** @var Permission $permission */
        $permission = $query->first();
        if ($permission == null) {
            return $this->not_found_response('Permission');
        }
        if (array_key_exists('slug', $data) && $data['slug'] !== null && $data['slug'] !== '') {
            $permission->slug = $data['slug'];
        }
        $permission->label = $data['label'];
        $permission->save();

        $payload = (string) json_encode($permission);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function destroy(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('delete-permissions')) !== true) {
            return $perm_check;
        }
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Permission::query()->where([['slug', $args['slug']]]);
        /** @var Permission $permission */
        $permission = $query->first();
        if ($permission == null) {
            return $this->not_found_response('Permission');
        }
        $permission->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
