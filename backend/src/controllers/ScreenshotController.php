<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\ChecksPermissions;
use Shipyard\FileManager;
use Shipyard\Screenshot;
use Shipyard\Ship;

class ScreenshotController extends Controller {
    use ChecksPermissions;

    /**
     * Display a listing of the resource.
     *
     * @group changed
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Response $response, $args) {
        $payload = json_encode(Ship::whereRef($args['ship_ref'])->firstOrFail()->screenshots()->get());
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
        $ship = Ship::whereRef($args['ship_ref'])->firstOrFail();
        $user_id = $ship->user_id;

        if (($perm_check = $this->isOrCan($user_id, 'create-screenshots')) !== null) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();
        $files = $request->getUploadedFiles();

        if (count($files) == 0) {
            $validator = Screenshot::validator([]);
            $validator->validate();
            $errors = $validator->errors();
            $payload = json_encode(['errors' => $errors]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }

        for ($i = 0; $i < count($files); $i++) {
            $screen_data = ['file_path' => FileManager::moveUploadedFile($files['file'][$i])];
            if (isset($data['description']) && isset($data['description'][$i])) {
                $screen_data['description'] = $data['description'][$i];
            }
            $validator = Screenshot::validator($screen_data);
            $validator->validate();
            $errors = $validator->errors();

            if (count($errors)) {
                $payload = json_encode(['errors' => $errors]);

                $response->getBody()->write($payload);

                return $response
                  ->withStatus(401)
                  ->withHeader('Content-Type', 'application/json');
            }

            $screenshot = new Screenshot();
            if (isset($screen_data['description'])) {
                $screenshot->description = $screen_data['description'];
            }
            $screenshot->file_path = $screen_data['file_path'];
            $screenshot->save();
            $ship->assignScreenshot($screenshot);
        }

        $payload = json_encode($ship->screenshots()->get());

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
        $payload = json_encode(Screenshot::where([['ref', $args['ref']]])->first());

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
        if (($perm_check = $this->can('edit-screenshots')) !== null) {
            return $perm_check;
        }
        $data = $request->getParsedBody();

        $screenshot = Screenshot::where([['ref', $args['ref']]])->first();
        $screenshot->description = $data['description'];
        $screenshot->save();

        $payload = json_encode($screenshot);

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
        if (($perm_check = $this->can('delete-screenshots')) !== null) {
            return $perm_check;
        }
        $screenshot = Screenshot::where([['ref', $args['ref']]])->first();
        $screenshot->delete();

        $payload = json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}