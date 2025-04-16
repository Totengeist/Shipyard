<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\FileManager;
use Shipyard\ItemHelper;
use Shipyard\Log;
use Shipyard\Models\Modification;
use Shipyard\Models\Save;
use Shipyard\Models\Screenshot;
use Shipyard\Models\Ship;
use Shipyard\Models\User;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class ItemController extends Controller {
    use ChecksPermissions;
    use ProcessesSlugs;

    /**
     * The full class path of the model type.
     *
     * @var string
     */
    protected $modelType;

    /**
     * The name of the model type when used in errors.
     *
     * @var string
     */
    protected $modelName;

    /**
     * The slug of the model type when used in permission checks and errors.
     *
     * @var string
     */
    protected $modelSlug;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Response $response) {
        $user = Auth::user();
        if ($user == null) {
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = $this->modelType::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orderBy('updated_at', 'DESC');
        } elseif ($user->can('edit-' . $this->modelSlug . 's')) {
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = $this->modelType::with('user', 'primary_screenshot', 'tags')->orderBy('updated_at', 'DESC');
        } else {
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = $this->modelType::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orWhere('user_id', $user->id)->orderBy('updated_at', 'DESC');
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

        Log::get()->channel('files')->error('Attempting to create a ' . $this->modelSlug, $data);

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

        $validator = $this->modelType::validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();
        if (!file_exists($upload->getFilePath()) || is_dir($upload->getFilePath())) {
            $errors = array_merge_recursive($errors, ['errors' => ['file_id' => 'File is missing or incorrect.']]);
        }

        if (count($errors)) {
            return $this->invalid_input_response($errors);
        }
        $model = $this->modelType::query()->create($data);
        $payload = (string) json_encode($model);

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
        $query = $this->modelType::query()->where([['ref', $args['ref']]])->with(['user', 'primary_screenshot', 'tags', 'parent', 'parent.user', 'children', 'children.user']);
        /** @var Modification|Ship|Save $model */
        $model = $query->first();
        if ($model == null) {
            return $this->not_found_response($this->modelName);
        }
        if ($model->isPrivate() && (Auth::user() === null || $model->user_id !== Auth::user()->id)) {
            return $this->not_found_response($this->modelName);
        }
        $payload = (string) json_encode($model);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Display a stub page of the resource.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function show_stub(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->modelType::query()->where([['ref', $args['ref']]])->with(['user', 'primary_screenshot', 'tags', 'parent', 'parent.user', 'children', 'children.user']);
        /** @var Modification|Ship|Save $model */
        $model = $query->first();
        if ($model == null) {
            return $this->not_found_response($this->modelName);
        }
        if ($model->isPrivate() && (Auth::user() === null || $model->user_id !== Auth::user()->id)) {
            return $this->not_found_response($this->modelName);
        }

        $thumb = '';
        if (!$model->primary_screenshot->isEmpty()) {
            $screenshot = $model->primary_screenshot->first();
            if (!$screenshot->thumbnails->isEmpty()) {
                foreach ($model->primary_screenshot->first()->thumbnails as $thumb) {
                    if ($thumb->size == '800') {
                        $thumb = "    <meta content=\"{$_SERVER['BASE_URL_ABS']}/api/v1/screenshot/{$screenshot->ref}/preview/800\" property=\"og:image\" />";
                        break;
                    }
                }
            }
        }

        ob_start();
        require __DIR__ . '/../public/index_stub.html';
        $template = ob_get_contents();
        ob_end_clean();

        $response->getBody()->write((string) $template);

        return $response;
    }

    /**
     * Download the specified resource.
     *
     * @todo test with missing file
     * @todo zip up file and screenshot(s)
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function download(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->modelType::query()->where([['ref', $args['ref']]]);
        /** @var Modification|Ship|Save $model */
        $model = $query->first();

        if ($model == null || $model->file == null || file_exists($model->file->getFilePath()) === false) {
            return $this->not_found_response('file', 'file does not exist');
        }
        if ($model->isPrivate() && (Auth::user() === null || $model->user_id !== Auth::user()->id)) {
            return $this->not_found_response($this->modelName);
        }

        $model->downloads++;
        $model->save();

        $encoding = 'none';
        $file_contents = FileManager::getFileContents($model->file, $request->getHeader('Accept-Encoding'), $encoding);
        $response->getBody()->write($file_contents);

        if ($encoding != 'none') {
            $response = $response->withHeader('Content-Encoding', $encoding);
        }

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . $model->file->filename . '.' . $model->file->extension . '"')
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

        Log::get()->channel('files')->error('Attempting to update a ' . $this->modelSlug, $data);

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->modelType::query()->where([['ref', $args['ref']]]);
        /** @var Modification|Ship|Save $model */
        $model = $query->first();
        if ($model == null) {
            return $this->not_found_response($this->modelName);
        }
        $abort = $this->isOrCan($model->user_id, 'edit-' . $this->modelSlug . 's');
        if ($abort !== true) {
            return $abort;
        }
        if ($model->isLocked() && $this->can('edit-' . $this->modelSlug . 's') !== true) {
            return $this->unauthorized_response(['This ' . $this->modelSlug . ' is locked to editing.']);
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

        $model->fill($final_data);

        if (isset($data['primary_screenshot'])) {
            $ref = strtolower($data['primary_screenshot']);
            /** @var Screenshot $screenshot */
            $screenshot = $model->screenshots()->where([['ref', $ref]])->first();
            if ($screenshot == null) {
                return $this->not_found_response('Screenshot');
            }
            $model->assignScreenshot($screenshot, true);
        }

        ItemHelper::edit_tags($data, $model);

        if (isset($data['primary_screenshot'])) {
            $ref = strtolower($data['primary_screenshot']);
            /** @var Screenshot $screenshot */
            $screenshot = $model->screenshots()->where([['ref', $ref]])->first();
            if ($screenshot == null) {
                return $this->not_found_response('Screenshot');
            }
            $model->assignScreenshot($screenshot, true);
        }

        if (isset($files['file'])) {
            if (is_array($files['file'])) {
                return $this->invalid_input_response(['file' => 'Multiple file uploads are not allowed.']);
            }
            if (strpos($files['file']->getClientFilename(), '__shipyard__blank__') !== 0) {
                if ($model->file != null) {
                    $model->file->delete();
                }
                $model->file_id = FileManager::moveUploadedFile($files['file'])->id;
            }
        }

        if (isset($data['state'])) {
            $model->flags = ItemHelper::get_flags($data['state']);
            unset($data['state']);
        }

        $model->save();
        $payload = (string) json_encode($model);
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Add a new version of an existing modification.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function upgrade(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->modelType::query()->where([['ref', $args['ref']]]);
        /** @var Modification|Ship|Save $parent_mod */
        $parent_mod = $query->first();
        if ($parent_mod == null) {
            return $this->not_found_response($this->modelName);
        }
        if ($parent_mod->isLocked() && $this->can('edit-' . $this->modelSlug . 's') !== true) {
            return $this->unauthorized_response(['This ' . $this->modelSlug . ' is locked to editing.']);
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['parent_id'] = $parent_mod->id;
        $request = $request->withParsedBody($requestbody);

        return $this->store($request, $response);
    }

    /**
     * Add screenshots to a modification.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function index_screenshots(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->modelType::query()->where([['ref', $args['ref']]]);
        /** @var Modification|Ship|Save $model */
        $model = $query->first();
        if ($model == null) {
            return $this->not_found_response($this->modelName);
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['item'] = $model;
        $request = $request->withParsedBody($requestbody);

        return (new ScreenshotController())->index($request, $response);
    }

    /**
     * Add screenshots to an item.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function store_screenshots(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->modelType::query()->where([['ref', $args['ref']]]);
        /** @var Modification|Ship|Save $model */
        $model = $query->first();
        if ($model == null) {
            return $this->not_found_response($this->modelName);
        }
        if ($model->isLocked() && $this->can('edit-' . $this->modelSlug . 's') !== true) {
            return $this->unauthorized_response(['The ' . $this->modelSlug . ' is locked to editing.']);
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['item'] = $model;
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
        $query = $this->modelType::query()->where([['ref', $args['ref']]]);

        /** @var Modification|Ship|Save $model */
        $model = $query->first();
        if ($model == null) {
            return $this->not_found_response($this->modelName);
        }
        $abort = $this->isOrCan($model->user_id, 'delete-' . $this->modelSlug . 's');
        if ($abort !== true) {
            return $abort;
        }
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->modelType::query()->where([['parent_id', $model->id]]);
        $children = $query->get();
        $children->each(function ($child, $key) use ($model) {
            /* @var Modification|Ship|Save $child */
            /* @var Modification|Ship|Save $model */
            $child->update(['parent_id' => $model->parent_id]);
        });
        $model->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
