<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Models\Modification;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class ModificationController extends Controller {
    use ChecksPermissions;
    use ProcessesSlugs;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Response $response) {
        /** @var \Illuminate\Database\Eloquent\Builder $builder */
        $builder = Modification::query();
        $payload = (string) json_encode($this->paginate($builder));
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
        $payload = (string) json_encode(Modification::query()->create($data));

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
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Modification::query()->where([['ref', $args['ref']]]);
        $modification = $query->first();
        if ($modification == null) {
            return $this->not_found_response('Modification');
        }
        $payload = (string) json_encode($modification);

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
        $data = (array) $request->getParsedBody();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Modification::query()->where([['ref', $args['ref']]]);
        /** @var Modification $modification */
        $modification = $query->first();
        if ($modification == null) {
            return $this->not_found_response('Modification');
        }
        if (isset($data['title'])) {
            $modification->title = $data['title'];
        }
        if (isset($data['description'])) {
            $modification->description = $data['description'];
        }
        $modification->save();

        $payload = (string) json_encode($modification);

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
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Modification::query()->where([['ref', $args['ref']]]);
        /** @var Modification $modification */
        $modification = $query->first();
        if ($modification == null) {
            return $this->not_found_response('Modification');
        }
        $modification->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
