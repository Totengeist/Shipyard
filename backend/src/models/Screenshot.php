<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasFile;
use Shipyard\Traits\HasRef;
use Valitron\Validator;

/**
 * @property string      $description
 * @property Thumbnail[] $thumbnails
 * @property bool        $primary
 */
class Screenshot extends Model {
    use HasRef;
    use HasFile;

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
        'ref', 'file_id', 'description',
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
     * Retrieve screenshots from items of a specific class.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function thumbnails() {
        return $this->hasMany(Thumbnail::class);
    }

    /**
     * Create or add on to a validator.
     *
     * @param mixed          $data
     * @param Validator|null $v
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

    /**
     * Delete the screenshot, it's associated file, and all thumbnails.
     *
     * @return bool
     */
    public function delete() {
        foreach ($this->thumbnails as $thumb) {
            if (!$thumb->delete()) {
                return false;
            }
        }

        return $this->file->delete() && parent::delete();
    }
}
