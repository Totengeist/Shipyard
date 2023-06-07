<?php

namespace Shipyard\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @method Model|static   create(array $attributes = [])
 * @method Builder|static find(mixed $id, array|string $columns = ['*'])
 * @method Builder|static findOrFail(mixed $id, array|string $columns = ['*'])
 * @method Model|static   firstOrCreate(array $attributes = [], array $values = [])
 * @method Builder|static where(mixed $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method Builder|static whereHas(string $relation, Closure $callback = null, string $operator = '>=', int $count = 1)
 * @method static         Builder|static query()
 */
class Model extends EloquentModel {
}
