<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
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
     * @return Response
     */
    public function index(Request $request, Response $response) {
        $user = Auth::user();
        if ($user == null) {
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = Ship::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)');
        } else {
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = Ship::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orWhere('user_id', $user->id);
        }
        $payload = (string) json_encode($this->paginate($content));
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
        $files = $request->getUploadedFiles();

        /** @var User $user */
        $user = Auth::user();
        if ($user == null) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['ref', 'system']]);
            /** @var User $user */
            $user = $query->first();
        }
        $data['user_id'] = $user->id;
        unset($data['file_id']);

        if (isset($files['file'])) {
            if (!is_array($files['file'])) {
                $upload = FileManager::moveUploadedFile($files['file']);
                $data['file_id'] = $upload->id;
            } else {
                return $this->invalid_input_response(['file' => 'Multiple file uploads are not allowed.']);
            }
        } else {
            return $this->invalid_input_response(['file' => 'File is missing or incorrect.']);
        }

        $flags = 0;
        if (isset($data['state'])) {
            foreach ($data['state'] as $flag) {
                switch ($flag) {
                    case 'private':
                        // Anonymized uploads cannot be marked private during creation. That's a mod-only action.
                        if ($user->ref !== 'system') {
                            $flags++;
                        }
                        break;
                    case 'unlisted':
                        $flags += 2;
                        break;
                    case 'locked':
                        $flags += 4;
                }
            }
            unset($data['state']);
        }
        $data['flags'] = $flags;

        $validator = Ship::validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();
        if (!file_exists($upload->filepath) || is_dir($upload->filepath)) {
            $errors = array_merge_recursive($errors, ['errors' => ['file_id' => 'File is missing or incorrect.']]);
        }

        if (count($errors)) {
            return $this->invalid_input_response($errors);
        }
        $ship = Ship::query()->create($data);
        $payload = (string) json_encode($ship);

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
        $query = Ship::query()->where([['ref', $args['ref']]])->with(['user', 'primary_screenshot', 'tags', 'parent', 'parent.user', 'children', 'children.user']);
        /** @var Ship $ship */
        $ship = $query->first();
        if ($ship == null) {
            return $this->not_found_response('Ship');
        }
        if ($ship->isPrivate() && (Auth::user() === null || $ship->user_id !== Auth::user()->id)) {
            return $this->not_found_response('Ship');
        }
        $payload = (string) json_encode($ship);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Download the specified resource.
     *
     * @todo test with missing file
     * @todo zip up ship file and screenshot
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function download(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Ship::query()->where([['ref', $args['ref']]]);
        /** @var Ship $ship */
        $ship = $query->first();

        if ($ship == null || file_exists($ship->file->filepath) === false) {
            return $this->not_found_response('file', 'file does not exist');
        }
        if ($ship->isPrivate() && (Auth::user() === null || $ship->user_id !== Auth::user()->id)) {
            return $this->not_found_response('Ship');
        }

        $response->getBody()->write((string) $ship->file->file_contents());
        $ship->downloads++;
        $ship->save();

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . $ship->file->filename . '.' . $ship->file->extension . '"')
          ->withHeader('Content-Type', 'text/plain');
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
        $query = Ship::query()->where([['ref', $args['ref']]]);
        /** @var Ship $ship */
        $ship = $query->first();
        if ($ship == null) {
            return $this->not_found_response('Ship');
        }
        $abort = $this->isOrCan($ship->user_id, 'edit-ships');
        if ($abort !== true) {
            return $abort;
        }

        if (isset($data['user_ref'])) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['ref', $data['user_ref']]]);
            /** @var User $user */
            $user = $query->first();
            if ($user == null) {
                return $this->not_found_response('User');
            }
            $ship->user_id = $user->id;
        }
        if (isset($data['title'])) {
            $ship->title = $data['title'];
        }
        if (isset($data['description'])) {
            $ship->description = $data['description'];
        }

        $flags = 0;
        if (isset($data['state'])) {
            foreach ($data['state'] as $flag) {
                switch ($flag) {
                    case 'private':
                        $flags++;
                        break;
                    case 'unlisted':
                        $flags += 2;
                        break;
                    case 'locked':
                        $flags += 4;
                }
            }
            unset($data['state']);
        }
        $ship->flags = $flags;

        $ship->save();

        $payload = (string) json_encode($ship);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Add a new version of an existing ship.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function upgrade(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Ship::query()->where([['ref', $args['ref']]]);
        /** @var Ship $parent_ship */
        $parent_ship = $query->first();
        if ($parent_ship == null) {
            return $this->not_found_response('Ship');
        }
        $abort = $this->isOrCan($parent_ship->user_id, 'edit-saves');
        if ($abort !== true) {
            return $abort;
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['parent_id'] = $parent_ship->id;
        $request = $request->withParsedBody($requestbody);

        return $this->store($request, $response);
    }

    /**
     * Add screenshots to a ship.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function index_screenshots(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Ship::query()->where([['ref', $args['ref']]]);
        /** @var Ship $ship */
        $ship = $query->first();
        if ($ship == null) {
            return $this->not_found_response('Ship');
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['item'] = $ship;
        $request = $request->withParsedBody($requestbody);

        return (new ScreenshotController())->index($request, $response);
    }

    /**
     * Add screenshots to a ship.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function store_screenshots(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Ship::query()->where([['ref', $args['ref']]]);
        /** @var Ship $ship */
        $ship = $query->first();
        if ($ship == null) {
            return $this->not_found_response('Ship');
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['item'] = $ship;
        $request = $request->withParsedBody($requestbody);

        return (new ScreenshotController())->store($request, $response, $args);
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
        $query = Ship::query()->where([['ref', $args['ref']]]);
        /** @var Ship $ship */
        $ship = $query->first();
        if ($ship == null) {
            return $this->not_found_response('Ship');
        }
        $abort = $this->isOrCan($ship->user_id, 'delete-ships');
        if ($abort !== true) {
            return $abort;
        }
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Ship::query()->where([['parent_id', $ship->id]]);
        $children = $query->get();
        $children->each(function ($child, $key) use ($ship) {
            /* @var \Shipyard\Models\Ship $child */
            /* @var \Shipyard\Models\Ship $ship */
            $child->update(['parent_id' => $ship->parent_id]);
        });
        $ship->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
