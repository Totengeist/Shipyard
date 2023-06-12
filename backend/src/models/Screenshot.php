<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasRef;
use Valitron\Validator;

/**
 * @property string $description
 * @property string $file_path
 * @property bool   $primary
 */
class Screenshot extends Model {
    use HasRef;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ref', 'file_path', 'description', 'primary',
    ];

    /**
     * The attributes that are casted.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'primary' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = [
        'id', 'file_path',
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

    /**
     * Retrieve screenshots from items of a specific class.
     *
     * @param class-string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function retrieve_type($class) {
        return $this->belongsToMany($class, 'item_screenshots', 'screenshot_id', 'item_id')->wherePivot('type', '=', $class::$tag_label);
    }

    /**
     * Create or add on to a validator.
     *
     * @param mixed                    $data
     * @param \Valitron\Validator|null $v
     *
     * @return \Valitron\Validator
     */
    public static function validator($data, $v = null) {
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
