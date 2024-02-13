<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasRef;
use Valitron\Validator;

/**
 * @property string $description
 * @property int    $file_id
 * @property File   $file
 * @property bool   $primary
 */
class Screenshot extends Model {
    use HasRef;

    /**
     * Label to use for tag table.
     *
     * @var string
     */
    public static $tag_label = 'screenshot';
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ref', 'file_id', 'description', 'primary',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = [
        'id', 'file_id', 'pivot',
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
     * Retrieve modifications with this tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function modifications() {
        return $this->retrieve_type(Modification::class);
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
     * A ship has a file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file() {
        return $this->belongsTo(File::class);
    }

    /**
     * Create or add on to a validator.
     *
     * @param mixed                    $data
     * @param \Valitron\Validator|null $v
     *
     * @return Validator
     */
    public static function validator($data, $v = null) {
        if ($v === null) {
            $v = new Validator($data);
        }

        $v->rules([
            'required' => [
                ['file_id']
            ]
        ]);

        return $v;
    }

    public function delete() {
        return $this->file->delete() && parent::delete();
    }
}
