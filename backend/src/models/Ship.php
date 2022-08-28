<?php

namespace Shipyard;

use Illuminate\Database\Eloquent\Model;
use Valitron\Validator;

class Ship extends Model {
    use CreatesUniqueIDs;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ref', 'user_id', 'file_path', 'title', 'description', 'downloads',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['id', 'file_path', 'user_id'];

    public static function create(array $attributes = []) {
        if (!isset($attributes['ref'])) {
            $attributes['ref'] = self::get_guid();
        }

        return static::query()->create($attributes);
    }

    public function save(array $options = []) {
        if (!isset($this->attributes['ref'])) {
            $this->setAttribute('ref', self::get_guid());
        }

        return parent::save($options);
    }

    /**
     * A ship can belong to a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    public static function validator(array $data, Validator $v = null) {
        if ($v === null) {
            $v = new Validator($data);
        }

        $v->rules([
            'required' => [
                ['user_id'],
                ['title'],
                ['description'],
                ['file_path']
            ]
        ]);

        return $v;
    }
}
