<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\FileManager;
use Shipyard\ImageHandler;
use Shipyard\Models\Screenshot;
use Shipyard\Traits\ChecksPermissions;

class ScreenshotController extends Controller {
    use ChecksPermissions;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Response $response) {
        $data = (array) $request->getParsedBody();
        $item = $data['item'];
        $screenshots = $item->screenshots()->get()->makeVisible(['pivot'])->toArray();
        $shots = [];
        foreach ($screenshots as $shot) {
            $shot['primary'] = $shot['pivot']['primary'];
            unset($shot['pivot']);
            $shots[] = $shot;
        }

        $payload = (string) json_encode($shots);
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function store(Request $request, Response $response, $args) {
        $data = (array) $request->getParsedBody();
        $item = $data['item'];
        $user_id = $item->user_id;

        if (($perm_check = $this->isOrCan($user_id, 'create-screenshots')) !== true) {
            return $perm_check;
        }
        $files = $request->getUploadedFiles();

        if (!is_array($files['file'])) {
            $files['file'] = [$files['file']];
        }

        if (count($files) === 0) { /** @phpstan-ignore identical.alwaysFalse */
            $validator = Screenshot::validator([]);
            $validator->validate();
            /** @var string[] $errors */
            $errors = $validator->errors();
            $errors = array_merge_recursive($errors, ['errors' => ['files' => 'There was no file included.']]);

            return $this->invalid_input_response($errors);
        }

        for ($i = 0; $i < count($files); $i++) {
            $screenshot = FileManager::moveUploadedFile($files['file'][$i]);
            $screen_data = ['file_id' => $screenshot->id];
            if (isset($data['description']) && isset($data['description'][$i])) {
                $screen_data['description'] = $data['description'][$i];
            }
            $validator = Screenshot::validator($screen_data);
            $validator->validate();
            /** @var string[] $errors */
            $errors = $validator->errors();

            if (count($errors)) {
                return $this->invalid_input_response($errors);
            }

            $screenshot = new Screenshot();
            if (isset($screen_data['description'])) {
                $screenshot->description = $screen_data['description'];
            }
            $screenshot->file_id = $screen_data['file_id'];
            $screenshot->save();
            ImageHandler::generateThumbnails($screenshot);
            $item->assignScreenshot($screenshot);
        }

        $screenshots = $item->screenshots()->get()->makeVisible(['pivot'])->toArray();
        $shots = [];
        foreach ($screenshots as $shot) {
            $shot['primary'] = $shot['pivot']['primary'];
            unset($shot['pivot']);
            $shots[] = $shot;
        }

        $payload = (string) json_encode($shots);
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
        $query = Screenshot::query()->where([['ref', $args['ref']]]);
        $screenshot = $query->first();
        if ($screenshot == null) {
            return $this->not_found_response('Screenshot');
        }
        $payload = (string) json_encode($screenshot);

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
    public function download(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Screenshot::query()->where([['ref', $args['ref']]]);
        /** @var Screenshot $screenshot */
        $screenshot = $query->first();
        if ($screenshot == null) {
            return $this->not_found_response('Screenshot');
        }

        $response->getBody()->write((string) $screenshot->file->file_contents());

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . $screenshot->file->filename . '.' . $screenshot->file->extension . '"')
          ->withHeader('Content-Type', $screenshot->file->media_type);
    }

    /**
     * Display the specified resource.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function preview(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Screenshot::query()->where([['ref', $args['ref']]]);
        /** @var Screenshot $screenshot */
        $screenshot = $query->first();
        if ($screenshot == null) {
            return $this->not_found_response('Screenshot');
        }
        if (!isset($args['size'])) {
            $response->getBody()->write((string) $screenshot->file->file_contents());

            return $response
              ->withHeader('Content-Type', $screenshot->file->media_type);
        }
        $size = intval($args['size']);

        $available = false;
        foreach (ImageHandler::$thumb_sizes as $tsize) {
            if ($tsize[0] == $size) {
                $available = true;
            }
        }
        if (!$available) {
            return $this->not_found_response('Thumbnail');
        }

        $thumbs = $screenshot->thumbnails()->get();
        foreach ($thumbs as $thumbnail) {
            if ($thumbnail['size'] == $size) {
                $response->getBody()->write((string) $thumbnail->file->file_contents());

                return $response
                  ->withHeader('Content-Disposition', 'attachment; filename="' . $thumbnail->file->filename . '.' . $thumbnail->file->extension . '"')
                  ->withHeader('Content-Type', $thumbnail->file->media_type);
            }
        }

        return $this->not_found_response('Thumbnail');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function update(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('edit-screenshots')) !== true) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Screenshot::query()->where([['ref', $args['ref']]]);
        /** @var Screenshot $screenshot */
        $screenshot = $query->first();
        if ($screenshot == null) {
            return $this->not_found_response('Screenshot');
        }
        if (isset($data['description'])) {
            $screenshot->description = $data['description'];
        }
        $screenshot->save();

        $payload = (string) json_encode($screenshot);

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
        if (($perm_check = $this->can('delete-screenshots')) !== true) {
            return $perm_check;
        }
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Screenshot::query()->where([['ref', $args['ref']]]);
        /** @var Screenshot $screenshot */
        $screenshot = $query->first();
        if ($screenshot == null) {
            return $this->not_found_response('Screenshot');
        }
        $screenshot->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
