<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SectionController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @group changed
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Response $response, $args) {
        $payload = json_encode(Section::all());
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
        $data = (array) $request->getParsedBody();
        if (!array_key_exists('slug', $data) || $data['slug'] === null || $data['slug'] === '') {
            $data['slug'] = SlugModel::slugify($data['label']);
        }
        $validator = SlugModel::slug_validator($data);
        $validator->validate();
        $errors = $validator->errors();

        if (count($errors)) {
            $payload = json_encode(['errors' => $errors]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }
        $payload = json_encode(Section::create($data));

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
        $payload = json_encode(Section::where([['slug', $args['slug']]])->first());

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();

        $permission = Section::where([['slug', $args['slug']]])->first();
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
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Response $response, $args) {
        $role = Section::where([['slug', $args['slug']]])->first();
        $role->delete();

        $payload = json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}