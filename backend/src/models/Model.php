<?php

namespace Shipyard\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @property int $id
 *
 * @method \Shipyard\Models\Model|static                                       create(array $attributes = [])
 * @method \Shipyard\Models\Model|static                                       find(mixed $id, array|string $columns = ['*'])
 * @method \Illuminate\Database\Eloquent\Builder|static                        where(mixed $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method \Illuminate\Database\Eloquent\Builder|static                        whereHas(string $relation, Closure $callback = null, string $operator = '>=', int $count = 1)
 * @method \Shipyard\Models\Model|\Illuminate\Database\Eloquent\Builder|static whereSlug(string $slug)
 * @method static                                                              \Illuminate\Database\Eloquent\Builder|static query()
 */
class Model extends EloquentModel {
}
