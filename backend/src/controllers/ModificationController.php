<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\FileManager;
use Shipyard\Models\Modification;
use Shipyard\Models\Screenshot;
use Shipyard\Models\Tag;
use Shipyard\Models\User;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class ModificationController extends Controller {
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
            $content = Modification::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orderBy('updated_at', 'DESC');
        } else {
            /** @var \Illuminate\Database\Eloquent\Builder $content */
            $content = Modification::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orWhere('user_id', $user->id)->orderBy('updated_at', 'DESC');
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

        $validator = Modification::validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();
        if (!file_exists($upload->getFilePath()) || is_dir($upload->getFilePath())) {
            $errors = array_merge_recursive($errors, ['errors' => ['file_id' => 'File is missing or incorrect.']]);
        }

        if (count($errors)) {
            return $this->invalid_input_response($errors);
        }
        $modification = Modification::query()->create($data);
        $payload = (string) json_encode($modification);

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
        $query = Modification::query()->where([['ref', $args['ref']]])->with(['user', 'primary_screenshot', 'tags']);
        /** @var Modification $modification */
        $modification = $query->first();
        if ($modification == null) {
            return $this->not_found_response('Modification');
        }
        if ($modification->isPrivate() && (Auth::user() === null || $modification->user_id !== Auth::user()->id)) {
            return $this->not_found_response('Modification');
        }
        $payload = (string) json_encode($modification);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Download the specified resource.
     *
     * @todo test with missing file
     * @todo zip up mod file and screenshot
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function download(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Modification::query()->where([['ref', $args['ref']]]);
        /** @var Modification $modification */
        $modification = $query->first();

        if ($modification == null || $modification->file == null || file_exists($modification->file->getFilePath()) === false) {
            return $this->not_found_response('file', 'file does not exist');
        }
        if ($modification->isPrivate() && (Auth::user() === null || $modification->user_id !== Auth::user()->id)) {
            return $this->not_found_response('Modification');
        }

        $modification->downloads++;
        $modification->save();

        $encoding = 'none';
        $file_contents = FileManager::getFileContents($modification->file, $request->getHeader('Accept-Encoding'), $encoding);
        $response->getBody()->write($file_contents);

        if ($encoding != 'none') {
            $response = $response->withHeader('Content-Encoding', $encoding);
        }

        return $response
          ->withHeader('Content-Disposition', 'attachment; filename="' . $modification->file->filename . '.' . $modification->file->extension . '"')
          ->withHeader('Content-Type', $modification->file->media_type)
          ->withHeader('Content-Length', strval(strlen($file_contents)));
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
        $query = Modification::query()->where([['ref', $args['ref']]]);
        /** @var Modification $modification */
        $modification = $query->first();
        if ($modification == null) {
            return $this->not_found_response('Modification');
        }
        $abort = $this->isOrCan($modification->user_id, 'edit-modifications');
        if ($abort !== true) {
            return $abort;
        }

        if ($modification->isLocked() && $this->can('edit-modifications') !== true) {
            return $this->unauthorized_response(['This modification is locked to editing.']);
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

        $modification->fill($final_data);

        if (isset($data['primary_screenshot'])) {
            $ref = strtolower($data['primary_screenshot']);
            /** @var Screenshot $screenshot */
            $screenshot = $modification->screenshots()->where([['ref', $ref]])->first();
            if ($screenshot == null) {
                return $this->not_found_response('Screenshot');
            }
            $modification->assignScreenshot($screenshot, true);
        }

        $this->edit_tags($data, $modification);

        if (isset($files['file'])) {
            if (is_array($files['file'])) {
                return $this->invalid_input_response(['file' => 'Multiple file uploads are not allowed.']);
            }
            if ($modification->file != null) {
                $modification->file->delete();
            }
            $modification->file_id = FileManager::moveUploadedFile($files['file'])->id;
        }

        if (isset($data['state'])) {
            $modification->flags = $this->get_flags($data['state']);
            unset($data['state']);
        }

        $modification->save();
        $payload = (string) json_encode($modification);
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
        $query = Modification::query()->where([['ref', $args['ref']]]);
        /** @var Modification $parent_mod */
        $parent_mod = $query->first();
        if ($parent_mod == null) {
            return $this->not_found_response('Modification');
        }

        if ($parent_mod->isLocked() && $this->can('edit-modifications') !== true) {
            return $this->unauthorized_response(['This modification is locked to editing.']);
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
        $query = Modification::query()->where([['ref', $args['ref']]]);
        /** @var Modification $modification */
        $modification = $query->first();
        if ($modification == null) {
            return $this->not_found_response('Modification');
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['item'] = $modification;
        $request = $request->withParsedBody($requestbody);

        return (new ScreenshotController())->index($request, $response);
    }

    /**
     * Add screenshots to a modification.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function store_screenshots(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Modification::query()->where([['ref', $args['ref']]]);
        /** @var Modification $modification */
        $modification = $query->first();
        if ($modification == null) {
            return $this->not_found_response('Modification');
        }
        if ($modification->isLocked() && $this->can('edit-saves') !== true) {
            return $this->unauthorized_response(['The modification is locked to editing.']);
        }

        $requestbody = (array) $request->getParsedBody();
        $requestbody['item'] = $modification;
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
        $query = Modification::query()->where([['ref', $args['ref']]]);
        /** @var Modification $modification */
        $modification = $query->first();
        if ($modification == null) {
            return $this->not_found_response('Modification');
        }
        $abort = $this->isOrCan($modification->user_id, 'delete-modifications');
        if ($abort !== true) {
            return $abort;
        }
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Modification::query()->where([['parent_id', $modification->id]]);
        $children = $query->get();
        $children->each(function ($child, $key) use ($modification) {
            /* @var \Shipyard\Models\Modification $child */
            /* @var \Shipyard\Models\Modification $modification */
            $child->update(['parent_id' => $modification->parent_id]);
        });
        $modification->delete();

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
     * @param Modification          $model the model to add and remove tags from
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
