<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\FileManager;
use Shipyard\Models\Screenshot;
use Shipyard\Models\Ship;
use Shipyard\Traits\ChecksPermissions;

class ScreenshotController extends Controller {
    use ChecksPermissions;

    /**
     * Display a listing of the resource.
     *
     * @todo Expand screenshots to items other than ships (item_ref and tag_label?)
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Ship::query()->whereRef($args['ship_ref']);
        /** @var \Shipyard\Models\Ship $ship */
        $ship = $query->firstOrFail();
        $payload = json_encode($ship->screenshots()->get());
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
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Ship::query()->whereRef($args['ship_ref']);
        /** @var \Shipyard\Models\Ship $ship */
        $ship = $query->firstOrFail();
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function show(Request $request, Response $response, $args) {
        $payload = json_encode(Screenshot::query()->where([['ref', $args['ref']]])->first());

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
        if (($perm_check = $this->can('edit-screenshots')) !== null) {
            return $perm_check;
        }
        $data = $request->getParsedBody();

        $screenshot = Screenshot::query()->where([['ref', $args['ref']]])->first();
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function destroy(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('delete-screenshots')) !== null) {
            return $perm_check;
        }
        $screenshot = Screenshot::query()->where([['ref', $args['ref']]])->first();
        $screenshot->delete();

        $payload = json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
