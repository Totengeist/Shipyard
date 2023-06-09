<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\FileManager;
use Shipyard\Models\Ship;
use Shipyard\Models\User;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class ShipController extends Controller {
    use ChecksPermissions;
    use ProcessesSlugs;

    /**
     * Display a listing of the resource.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response, $args) {
        $payload = json_encode(Ship::all());
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
        $files = $request->getUploadedFiles();

        unset($data['user_id']);
        if (isset($data['user_ref'])) {
            $user = User::query()->where([['ref', $data['user_ref']]])->first();
            $data['user_id'] = $user->id;
        }
        unset($data['user_ref']);
        unset($data['file_path']);

        if (isset($files['file'])) {
            if (!is_array($files['file'])) {
                $data['file_path'] = FileManager::moveUploadedFile($files['file']);
            } else {
                $payload = json_encode(['errors' => ['file' => 'Multiple file uploads are not allowsed.']]);

                $response->getBody()->write($payload);

                return $response
                  ->withStatus(401)
                  ->withHeader('Content-Type', 'application/json');
            }
        } else {
            $payload = json_encode(['errors' => ['file' => 'File is missing or incorrect.']]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }

        $validator = Ship::validator($data);
        $validator->validate();
        $errors = $validator->errors();
        if (isset($data['file_path']) && (!file_exists($data['file_path']) || is_dir($data['file_path']))) {
            $errors = array_merge_recursive($errors, ['errors' => ['file_path' => 'File Path is missing or incorrect.']]);
        }

        if (count($errors)) {
            $payload = json_encode(['errors' => $errors]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }
        $ship = Ship::query()->create($data);
        $payload = json_encode($ship);

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
        $payload = json_encode(Ship::query()->where([['ref', $args['ref']]])->with('user')->first());

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Download the specified resource.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function download(Request $request, Response $response, $args) {
        $item = Ship::query()->where([['ref', $args['ref']]])->first();

        if (file_exists($item->file_path) === false) {
            $response->getBody()->write(['error' => 'file does not exist']);

            return $response
              ->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write($item->file_contents());
        $item->downloads++;
        $item->save();

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . self::slugify($item->title) . '.ship"')
          ->withHeader('Content-Type', 'text/plain');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();

        $ship = Ship::query()->where([['ref', $args['ref']]])->first();
        $abort = $this->isOrCan($ship->user_id, 'edit-ships');
        if ($abort !== null) {
            return $abort;
        }

        if (isset($data['user_id'])) {
            $ship->user_id = $data['user_id'];
        }
        if (isset($data['title'])) {
            $ship->title = $data['title'];
        }
        if (isset($data['description'])) {
            $ship->description = $data['description'];
        }
        if (isset($data['file_path'])) {
            $ship->file_path = $data['file_path'];
        }

        $ship->save();

        $payload = json_encode($ship);

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
        $ship = Ship::query()->where([['ref', $args['ref']]])->first();
        $abort = $this->isOrCan($ship->user_id, 'delete-ships');
        if ($abort !== null) {
            return $abort;
        }
        $ship->delete();

        $payload = json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
