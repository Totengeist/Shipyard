<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\FileManager;
use Shipyard\Models\Save;
use Shipyard\Models\User;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class SaveController extends Controller {
    use ChecksPermissions;
    use ProcessesSlugs;

    /**
     * Display a listing of the resource.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response) {
        $payload = (string) json_encode($this->paginate(Save::query()));
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
        $files = $request->getUploadedFiles();

        unset($data['user_id']);
        if (isset($data['user_ref'])) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['ref', $data['user_ref']]]);
            /** @var \Shipyard\Models\User $user */
            $user = $query->first();
            $data['user_id'] = $user->id;
        }
        unset($data['user_ref']);
        unset($data['file_path']);

        if (isset($files['file'])) {
            if (!is_array($files['file'])) {
                $data['file_path'] = FileManager::moveUploadedFile($files['file']);
            } else {
                $payload = (string) json_encode(['errors' => ['file' => 'Multiple file uploads are not allowsed.']]);

                $response->getBody()->write($payload);

                return $response
                  ->withStatus(401)
                  ->withHeader('Content-Type', 'application/json');
            }
        } else {
            $payload = (string) json_encode(['errors' => ['file' => 'File is missing or incorrect.']]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }

        $validator = Save::validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();
        if (!file_exists($data['file_path']) || is_dir($data['file_path'])) {
            $errors = array_merge_recursive($errors, ['errors' => ['file_path' => 'File Path is missing or incorrect.']]);
        }

        if (count($errors)) {
            $payload = (string) json_encode(['errors' => $errors]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }
        $save = Save::query()->create($data);
        $payload = (string) json_encode($save);

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
        $query = Save::query()->where([['ref', $args['ref']]])->with('user');
        $payload = (string) json_encode($query->first());

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Download the specified resource.
     *
     * @todo test with missing file
     *
     * @param array<string,string> $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function download(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var \Shipyard\Models\Save $save */
        $save = $query->first();

        if (file_exists($save->file_path) === false) {
            $response->getBody()->write((string) json_encode(['error' => 'file does not exist']));

            return $response
              ->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write((string) $save->file_contents());
        $save->downloads++;
        $save->save();

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . self::slugify($save->title) . '.space"')
          ->withHeader('Content-Type', 'text/plain');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array<string,string> $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var \Shipyard\Models\Save $save */
        $save = $query->first();
        $abort = $this->isOrCan($save->user_id, 'edit-saves');
        if ($abort !== null) {
            return $abort;
        }

        if (isset($data['user_id'])) {
            $save->user_id = $data['user_id'];
        }
        if (isset($data['title'])) {
            $save->title = $data['title'];
        }
        if (isset($data['description'])) {
            $save->description = $data['description'];
        }
        if (isset($data['file_path'])) {
            $save->file_path = $data['file_path'];
        }

        $save->save();

        $payload = (string) json_encode($save);

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
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var \Shipyard\Models\Save $save */
        $save = $query->first();
        $abort = $this->isOrCan($save->user_id, 'delete-saves');
        if ($abort !== null) {
            return $abort;
        }
        unlink($save->file_path);
        $save->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
