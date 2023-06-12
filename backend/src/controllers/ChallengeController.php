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
    public function index(Request $request, Response $response) {
        $payload = (string) json_encode(Challenge::all());
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
        $data = (array) $request->getParsedBody();
        $validator = self::slug_validator($data);
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
        $payload = (string) json_encode(Challenge::query()->create($data));

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
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Challenge::query()->where([['ref', $args['ref']]]);
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
        $data = (array) $request->getParsedBody();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Challenge::query()->where([['ref', $args['ref']]]);
        /** @var \Shipyard\Models\Challenge $challenge */
        $challenge = $query->first();
        if (isset($data['title'])) {
            $challenge->title = $data['title'];
        }
        if (isset($data['description'])) {
            $challenge->description = $data['description'];
        }
        $challenge->save();

        $payload = (string) json_encode($challenge);

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
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Challenge::query()->where([['ref', $args['ref']]]);
        /** @var \Shipyard\Models\Challenge $challenge */
        $challenge = $query->first();
        $challenge->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
