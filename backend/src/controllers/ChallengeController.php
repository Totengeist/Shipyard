<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Models\Challenge;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class ChallengeController extends Controller {
    use ChecksPermissions;
    use ProcessesSlugs;

    /**
     * Display a listing of the resource.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response, $args) {
        $payload = json_encode(Challenge::all());
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function store(Request $request, Response $response, $args) {
        $data = (array) $request->getParsedBody();
        if (!array_key_exists('slug', $data) || $data['slug'] === null || $data['slug'] === '') {
            $data['slug'] = self::slugify($data['label']);
        }
        $validator = self::slug_validator($data);
        $validator->validate();
        $errors = $validator->errors();

        if (count($errors)) {
            $payload = json_encode(['errors' => $errors]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }
        $payload = json_encode(Challenge::query()->create($data));

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Display the specified resource.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function show(Request $request, Response $response, $args) {
        $payload = json_encode(Challenge::query()->where([['slug', $args['slug']]])->first());

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();

        $permission = Challenge::query()->where([['slug', $args['slug']]])->first();
        if (array_key_exists('slug', $data) && $data['slug'] !== null && $data['slug'] !== '') {
            $permission->slug = $data['slug'];
        }
        $permission->label = $data['label'];
        $permission->save();

        $payload = json_encode($permission);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function destroy(Request $request, Response $response, $args) {
        $role = Challenge::query()->where([['slug', $args['slug']]])->first();
        $role->delete();

        $payload = json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
