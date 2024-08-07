<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasRef;
use Shipyard\Traits\HasReleases;
use Shipyard\Traits\HasScreenshots;
use Shipyard\Traits\HasTags;
use Valitron\Validator;

/**
 * @property int    $file_id
 * @property File   $file
 * @property string $title
 * @property string $description
 * @property int    $user_id
 * @property int    $parent_id
 * @property int    $downloads
 */
class Ship extends Model {
    use HasTags;
    use HasReleases;
    use HasScreenshots;
    use HasRef;

    /**
     * Label to use for tag table.
     *
     * @var string
     */
    public static $tag_label = 'ship';
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ref', 'user_id', 'parent_id', 'file_id', 'title', 'description', 'downloads',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = ['id', 'user_id', 'parent_id', 'file_id'];

    /**
     * A ship can belong to a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * A ship can have a parent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent() {
        return $this->hasOne(Ship::class, 'id', 'parent_id');
    }

    /**
     * A ship can have children.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children() {
        return $this->hasMany(Ship::class, 'parent_id', 'id');
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
                ['user_id'],
                ['title'],
                ['description'],
                ['file_id']
            ]
        ]);

        return $v;
    }

    public function delete() {
        return $this->file->delete() && parent::delete();
    }
}
