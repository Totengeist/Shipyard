<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\FileManager;
use Shipyard\Models\Screenshot;
use Shipyard\Traits\ChecksPermissions;

class ScreenshotController extends Controller {
    use ChecksPermissions;

    /**
     * Display a listing of the resource.
     *
     * @todo expand screenshots to items other than ships (item_ref and tag_label?)
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

        if (count($files) == 0) {
            $validator = Screenshot::validator([]);
            $validator->validate();
            $errors = $validator->errors();
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
            $item->assignScreenshot($screenshot);
        }

        $payload = (string) json_encode($item->screenshots()->get());

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
        $payload = (string) json_encode($screenshot);

        $response->getBody()->write((string) $screenshot->file->file_contents());

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . $screenshot->file->filename . '.' . $screenshot->file->extension . '"')
          ->withHeader('Content-Type', $screenshot->file->media_type);
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
        $screenshot->description = $data['description'];
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
