<?php

namespace Shipyard\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @property int $id
 *
 * @method        \Shipyard\Models\Model|static                create(array<string, mixed> $attributes = [])
 * @method        \Shipyard\Models\Model|static                find(mixed $id, string[]|string $columns = ['*'])
 * @method        \Shipyard\Models\Model|static                firstOrCreate(array<string, mixed> $attributes = [], array<string, mixed> $values = [])
 * @method        \Illuminate\Database\Eloquent\Builder|static where(mixed $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method        \Illuminate\Database\Eloquent\Builder|static whereHas(string $relation, \Closure $callback = null, string $operator = '>=', int $count = 1)
 * @method        \Illuminate\Database\Eloquent\Builder|static whereSlug(string $slug)
 * @method        \Illuminate\Database\Eloquent\Builder|static whereRef(string $ref)
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 * @method static \Illuminate\Database\Eloquent\Builder|static selectRaw(string $expression)
 */
class Model extends EloquentModel {
}
