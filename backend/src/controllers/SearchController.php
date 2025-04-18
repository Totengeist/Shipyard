<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\Models\Modification;
use Shipyard\Models\Save;
use Shipyard\Models\Ship;
use Shipyard\Models\User;
use Shipyard\Traits\ChecksPermissions;

class SearchController extends Controller {
    use ChecksPermissions;

    /**
     * Remove the specified resource from storage.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function search(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder[] $search_types */
        $search_types = [
            'ships' => Ship::with('user', 'primary_screenshot', 'tags')->selectRaw('*, "ship" as item_type'),
            'saves' => Save::with('user', 'primary_screenshot', 'tags')->selectRaw('*, "save" as item_type'),
            'modifications' => Modification::with('user', 'primary_screenshot', 'tags')->selectRaw('*, "modification" as item_type'),
        ];

        // TODO: The order of the types should always remain the same. This will change it depending on how they are in the type search field.
        if (isset($_GET['type'])) {
            $selected_types = [];
            foreach (explode(',', $_GET['type']) as $type) {
                $type = trim($type);
                if (in_array($type, array_keys($search_types))) {
                    $selected_types[$type] = $search_types[$type];
                }
            }
            $search_types = $selected_types;
        }

        $union = null;
        foreach ($search_types as $key => $search) {
            $search = $this->process_query($key, $search, $_GET);
            if ($search === false) {
                return $this->not_found_response('User');
            }
            if ($union == null) {
                $union = $search;
            } else {
                /** @var \Illuminate\Database\Query\Builder $search */
                $union = $union->union($search);
            }
        }

        if ($union == null) {
            $response->getBody()->write('[]');

            return $response
              ->withHeader('Content-Type', 'application/json');
        }

        $union = $union->orderBy('updated_at', 'DESC')->paginate(15);

        $response->getBody()->write($union->toJson()); // @phpstan-ignore method.notFound

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string                                $type   the search type
     * @param \Illuminate\Database\Eloquent\Builder $search the search being built
     * @param array<string,string>                  $query  the search query
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|false
     */
    public function process_query($type, $search, $query) {
        $authuser = Auth::user();
        $seeall = (int) ($authuser !== null && ($authuser->can('edit-' . $type) === true));
        $seeusr = (int) ($authuser !== null && isset($query['user']) && $query['user'] == $authuser->ref);
        if (!$seeall && !$seeusr) {
            $search = $search->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)');
        } elseif (!$seeall && $seeusr) {
            $search = $search->where(function ($q) use ($authuser) {
                $q->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orWhere('user_id', $authuser->id);
            });
        }

        if (isset($query['user'])) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['ref', $query['user']]]);
            /** @var User $user */
            $user = $query->first();
            if ($user == null) {
                return false;
            }

            return $search->where('user_id', $user->id);
        }

        return $search;
    }
}
