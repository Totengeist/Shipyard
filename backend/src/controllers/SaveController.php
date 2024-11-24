<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\FileManager;
use Shipyard\Models\Save;
use Shipyard\Models\Tag;
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
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = Save::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orderBy('updated_at', 'DESC');
        } else {
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = Save::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orWhere('user_id', $user->id)->orderBy('updated_at', 'DESC');
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
            $data['flags'] = $this->get_flags($data['state'], $anonymous);
            unset($data['state']);
        }

        $validator = Save::validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();
        if (!file_exists($upload->getFilePath()) || is_dir($upload->getFilePath())) {
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
        /** @var Save $save */
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

        if ($save == null || $save->file == null || file_exists($save->file->getFilePath()) === false) {
            return $this->not_found_response('file', 'file does not exist');
        }
        if ($save->isPrivate() && (Auth::user() === null || $save->user_id !== Auth::user()->id)) {
            return $this->not_found_response('Save');
        }

        $save->downloads++;
        $save->save();
        if ($save->file->compressed) {
            if (($file = gzopen($save->file->getFilePath(), 'r')) === false || ($str = stream_get_contents($file)) === false) {
                throw new \Exception('Unable to open file: ' . json_encode($save));
            }
        } else {
            if (($file = fopen($save->file->getFilePath(), 'r')) === false || ($str = stream_get_contents($file)) === false) {
                throw new \Exception('Unable to open file: ' . json_encode($save));
            }
        }
        $response->getBody()->write($str);

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
        $files = $request->getUploadedFiles();

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

        $this->edit_tags($data, $save);

        if (isset($files['file'])) {
            if (!is_array($files['file'])) {
                if ($save->file != null) {
                    $save->file->delete();
                }
                $save->file_id = FileManager::moveUploadedFile($files['file'])->id;
            } else {
                return $this->invalid_input_response(['file' => 'Multiple file uploads are not allowed.']);
            }
        }

        if (isset($data['state'])) {
            $save->flags = $this->get_flags($data['state']);
            unset($data['state']);
        }

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

    /**
     * Check and set flags for an item.
     *
     * @param string[] $data             the data submitted
     * @param bool     $anonymous_create whether this is an anonymous item creation
     *
     * @return int the flag bitfield
     */
    public function get_flags($data, $anonymous_create = false) {
        $flags = 0;
        foreach ($data as $flag) {
            switch ($flag) {
                case 'private':
                    // Anonymized uploads cannot be marked private during creation. That's a mod-only action.
                    if (!$anonymous_create) {
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

        return $flags;
    }

    /**
     * Add and remove tags from a model.
     *
     * @param array<string, string> $data  the submitted data
     * @param Save                  $model the model to add and remove tags from
     *
     * @return void
     */
    public function edit_tags($data, $model) {
        if (isset($data['remove_tags'])) {
            $tag_query = preg_replace('/[^0-9a-z-_,]/i', '', $data['remove_tags']);
            if ($tag_query !== null) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query = Tag::query();
                $remove_tags = $query->whereIn('slug', explode(',', $tag_query))->get();
                $tag_ids = [];
                foreach ($remove_tags as $remove_tag) {
                    $tag_ids[] = $remove_tag->id;
                }
                $model->tags()->detach($tag_ids);
            }
        }
        if (isset($data['add_tags'])) {
            $tag_query = preg_replace('/[^0-9a-z-_,]/i', '', $data['add_tags']);
            if ($tag_query !== null) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query = Tag::query();
                $add_tags = $query->whereIn('slug', explode(',', $tag_query))->get();
                $tag_ids = [];
                foreach ($add_tags as $add_tag) {
                    $tag_ids[] = $add_tag->id;
                }
                $model->tags()->attach($tag_ids, ['type' => get_class($model)::$tag_label]);
            }
        }
    }
}
