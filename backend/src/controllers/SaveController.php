<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
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
     * @return Response
     */
    public function index(Request $request, Response $response) {
        $user = Auth::user();
        if ($user == null) {
            $payload = (string) json_encode($this->paginate(Save::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')));
        } else {
            $payload = (string) json_encode($this->paginate(Save::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orWhere('user_id', $user->id)));
        }
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

        $validator = Save::validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();
        if (!file_exists($upload->filepath) || is_dir($upload->filepath)) {
            $errors = array_merge_recursive($errors, ['errors' => ['file_id' => 'File is missing or incorrect.']]);
        }

        if (count($errors)) {
            return $this->invalid_input_response($errors);
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
     * @return Response
     */
    public function show(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Save::query()->where([['ref', $args['ref']]])->with(['user', 'primary_screenshot', 'tags']);
        $save = $query->first();
        if ($save == null) {
            return $this->not_found_response('Save');
        }
        if ($save->isPrivate() && (Auth::user() === null || $save->user_id !== Auth::user()->id)) {
            return $this->not_found_response('Save');
        }
        $payload = (string) json_encode($save);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Download the specified resource.
     *
     * @todo test with missing file
     * @todo zip up save file and screenshot
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function download(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var Save $save */
        $save = $query->first();

        if ($save == null || file_exists($save->file->filepath) === false) {
            return $this->not_found_response('file', 'file does not exist');
        }
        if ($save->isPrivate() && (Auth::user() === null || $save->user_id !== Auth::user()->id)) {
            return $this->not_found_response('Save');
        }

        $response->getBody()->write((string) $save->file->file_contents());
        $save->downloads++;
        $save->save();

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . $save->file->filename . '.' . $save->file->extension . '"')
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
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var Save $save */
        $save = $query->first();
        if ($save == null) {
            return $this->not_found_response('Save');
        }
        $abort = $this->isOrCan($save->user_id, 'edit-saves');
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
            $save->user_id = $user->id;
        }
        if (isset($data['title'])) {
            $save->title = $data['title'];
        }
        if (isset($data['description'])) {
            $save->description = $data['description'];
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
        $save->flags = $flags;

        $save->save();

        $payload = (string) json_encode($save);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Add a new version of an existing save.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function upgrade(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var Save $parent_save */
        $parent_save = $query->first();
        if ($parent_save == null) {
            return $this->not_found_response('Save');
        }
        $abort = $this->isOrCan($parent_save->user_id, 'edit-saves');
        if ($abort !== true) {
            return $abort;
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['parent_id'] = $parent_save->id;
        $request = $request->withParsedBody($requestbody);

        return $this->store($request, $response);
    }

    /**
     * Add screenshots to a save.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function index_screenshots(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var Save $save */
        $save = $query->first();
        if ($save == null) {
            return $this->not_found_response('Save');
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['item'] = $save;
        $request = $request->withParsedBody($requestbody);

        return (new ScreenshotController())->index($request, $response);
    }

    /**
     * Add screenshots to a save.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function store_screenshots(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var Save $save */
        $save = $query->first();
        if ($save == null) {
            return $this->not_found_response('Save');
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['item'] = $save;
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
        $query = Save::query()->where([['ref', $args['ref']]]);
        /** @var Save $save */
        $save = $query->first();
        if ($save == null) {
            return $this->not_found_response('Save');
        }
        $abort = $this->isOrCan($save->user_id, 'delete-saves');
        if ($abort !== true) {
            return $abort;
        }
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Save::query()->where([['parent_id', $save->id]]);
        $children = $query->get();
        $children->each(function ($child, $key) use ($save) {
            /* @var \Shipyard\Models\Save $child */
            /* @var \Shipyard\Models\Save $save */
            $child->update(['parent_id' => $save->parent_id]);
        });
        $save->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
