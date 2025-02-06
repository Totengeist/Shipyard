<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\FileManager;
use Shipyard\ItemHelper;
use Shipyard\Models\Screenshot;
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
            $content = Ship::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orderBy('updated_at', 'DESC');
        } else {
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = Ship::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orWhere('user_id', $user->id)->orderBy('updated_at', 'DESC');
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

        $anonymous = false;
        /** @var User $user */
        $user = Auth::user();
        if ($user == null) {
            $anonymous = true;
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

        if (isset($data['state'])) {
            $data['flags'] = ItemHelper::get_flags($data['state'], $anonymous);
            unset($data['state']);
        }

        $validator = Ship::validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();
        if (!file_exists($upload->getFilePath()) || is_dir($upload->getFilePath())) {
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

        if ($ship == null || $ship->file == null || file_exists($ship->file->getFilePath()) === false) {
            return $this->not_found_response('file', 'file does not exist');
        }
        if ($ship->isPrivate() && (Auth::user() === null || $ship->user_id !== Auth::user()->id)) {
            return $this->not_found_response('Ship');
        }

        $ship->downloads++;
        $ship->save();

        $encoding = 'none';
        $file_contents = FileManager::getFileContents($ship->file, $request->getHeader('Accept-Encoding'), $encoding);
        $response->getBody()->write($file_contents);

        if ($encoding != 'none') {
            $response = $response->withHeader('Content-Encoding', $encoding);
        }

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . $ship->file->filename . '.' . $ship->file->extension . '"')
          ->withHeader('Content-Type', 'text/plain')
          ->withHeader('Content-Length', strval(strlen($file_contents)));
    }

    /**
     * Update the specified resource in storage.
     *
     * @todo Add better udpate validation
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function update(Request $request, Response $response, $args) {
        $data = (array) $request->getParsedBody();
        $files = $request->getUploadedFiles();

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
        if ($ship->isLocked() && $this->can('edit-ships') !== true) {
            return $this->unauthorized_response(['This ship is locked to editing.']);
        }

        $final_data = array_intersect_key($data, array_flip(['title', 'description']));

        if (isset($data['user_ref'])) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['ref', $data['user_ref']]]);
            /** @var User $user */
            $user = $query->first();
            if ($user == null) {
                return $this->not_found_response('User');
            }
            $final_data['user_id'] = $user->id;
        }

        $ship->fill($final_data);

        if (isset($data['primary_screenshot'])) {
            $ref = strtolower($data['primary_screenshot']);
            /** @var Screenshot $screenshot */
            $screenshot = $ship->screenshots()->where([['ref', $ref]])->first();
            if ($screenshot == null) {
                return $this->not_found_response('Screenshot');
            }
            $ship->assignScreenshot($screenshot, true);
        }

        ItemHelper::edit_tags($data, $ship);

        if (isset($files['file'])) {
            if (is_array($files['file'])) {
                return $this->invalid_input_response(['file' => 'Multiple file uploads are not allowed.']);
            }
            if ($ship->file != null) {
                $ship->file->delete();
            }
            $ship->file_id = FileManager::moveUploadedFile($files['file'])->id;
        }

        if (isset($data['state'])) {
            $ship->flags = ItemHelper::get_flags($data['state']);
            unset($data['state']);
        }

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
        if ($parent_ship->isLocked() && $this->can('edit-ships') !== true) {
            return $this->unauthorized_response(['The ship is locked to editing.']);
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
        if ($ship->isLocked() && $this->can('edit-ships') !== true) {
            return $this->unauthorized_response(['The ship is locked to editing.']);
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
