<?php

namespace Shipyard;

use Illuminate\Database\Eloquent\Model;
use Valitron\Validator;

class Screenshot extends Model {
    use HasRef;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ref',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];

    /**
     * Retrieve ships with this tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ships() {
        return $this->retrieve_type(Ship::class);
    }

    /**
     * Retrieve saves with this tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function saves() {
        return $this->retrieve_type(Save::class);
    }

    /**
     * Retrieve challenges with this tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function challenges() {
        return $this->retrieve_type(Challenge::class);
    }

    public function retrieve_type($class) {
        return $this->belongsToMany($class, 'item_screenshots', 'screenshot_id', 'item_id')->wherePivot('type', '=', $class::$tag_label);
    }

    /**
     * Get a validator for an incoming request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data, Validator $v = null) {
        if ($v === null) {
            $v = new Validator($data);
        }

        $v->rules([
            'required' => [
                ['file_path']
            ]
        ]);

        return $v;
    }
}
