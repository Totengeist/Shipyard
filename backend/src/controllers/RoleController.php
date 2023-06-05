<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Models\Role;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\HasSlug;

class RoleController extends Controller {
    use ChecksPermissions;
    use HasSlug;

    /**
     * Display a listing of the resource.
     *
     * @group changed
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('view-roles')) !== null) {
            return $perm_check;
        }

        $payload = json_encode(Role::all());
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('create-roles')) !== null) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();
        if (!array_key_exists('slug', $data) || $data['slug'] === null || $data['slug'] === '') {
            $data['slug'] = $this->slugify($data['label']);
        }
        $validator = $this->slug_validator($data);
        $validator->validate();
        $errors = $validator->errors();

        if (count($errors)) {
            $payload = json_encode(['errors' => $errors]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }
        $payload = json_encode(Role::create($data));

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('view-roles')) !== null) {
            return $perm_check;
        }

        $payload = json_encode(Role::where([['slug', $args['slug']]])->first());

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('edit-roles')) !== null) {
            return $perm_check;
        }
        $data = $request->getParsedBody();

        $role = Role::where([['slug', $args['slug']]])->first();
        $role->slug = $data['slug'];
        $role->label = $data['label'];
        $role->save();

        $payload = json_encode($role);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('delete-roles')) !== null) {
            return $perm_check;
        }
        $role = Role::where([['slug', $args['slug']]])->first();
        $role->delete();

        $payload = json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
