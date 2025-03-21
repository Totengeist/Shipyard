<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Models\Tag;
use Shipyard\Traits\ChecksPermissions;
use Shipyard\Traits\ProcessesSlugs;

class TagController extends Controller {
    use ChecksPermissions;
    use ProcessesSlugs;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Response $response) {
        /** @var \Illuminate\Database\Eloquent\Builder $builder */
        $builder = Tag::query();
        $payload = (string) json_encode($this->paginate($builder));
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
        if (($perm_check = $this->can('create-tags')) !== true) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();
        if (!array_key_exists('slug', $data) || $data['slug'] === null || $data['slug'] === '') {
            $data['slug'] = self::slugify($data['label']);
        }
        $validator = $this->slug_validator($data);
        $validator->validate();
        /** @var string[] $errors */
        $errors = $validator->errors();

        if (count($errors)) {
            return $this->invalid_input_response($errors);
        }
        $payload = (string) json_encode(Tag::query()->create($data));

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
        $query = Tag::query()->where([['slug', $args['slug']]])->with(['ships', 'ships.user', 'saves', 'saves.user', 'modifications', 'modifications.user']);
        $tag = $query->first();
        if ($tag == null) {
            return $this->not_found_response('Tag');
        }
        $payload = (string) json_encode($tag);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function update(Request $request, Response $response, $args) {
        if (($perm_check = $this->can('edit-tags')) !== true) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Tag::query()->where([['slug', $args['slug']]]);
        /** @var Tag $tag */
        $tag = $query->first();
        if ($tag == null) {
            return $this->not_found_response('Tag');
        }
        $tag->slug = $data['slug'];
        $tag->label = $data['label'];
        $tag->save();

        $payload = (string) json_encode($tag);

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
        if (($perm_check = $this->can('delete-tags')) !== true) {
            return $perm_check;
        }
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Tag::query()->where([['slug', $args['slug']]]);
        /** @var Tag $tag */
        $tag = $query->first();
        if ($tag == null) {
            return $this->not_found_response('Tag');
        }
        $tag->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Return the top 30 matching tags.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function search(Request $request, Response $response, $args) {
        $query_str = str_replace(';', '', $args['query']);
        if ($query_str === '') {
            $response->getBody()->write('[]');

            return $response
              ->withHeader('Content-Type', 'application/json');
        }
        if ($this->can('assign-tags') === true) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Tag::query();
            $query = $query->where('label', 'like', '%' . $query_str . '%')->orWhere('slug', 'like', '%' . self::slugify($query_str) . '%');
        } else {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Tag::query()->where('locked', false)->where(function ($query) use ($query_str) {
                $query->where('label', 'like', '%' . $query_str . '%')->orWhere('slug', 'like', '%' . self::slugify($query_str) . '%');
            });
        }
        $tags = $query->paginate(30);

        $payload = (string) json_encode($tags);
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
