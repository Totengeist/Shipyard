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
class Save extends Model {
    use HasTags;
    use HasReleases;
    use HasScreenshots;
    use HasRef;

    /**
     * Label to use for tag table.
     *
     * @var string
     */
    public static $tag_label = 'save';
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ref', 'user_id', 'parent_id', 'file_id', 'title', 'description', 'downloads', 'flags',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = ['id', 'user_id', 'parent_id', 'file_id'];

    /**
     * A save can belong to a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * A save can have a parent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent() {
        return $this->hasOne(Save::class, 'id', 'parent_id');
    }

    /**
     * A save can have a child.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function child() {
        return $this->hasOne(Save::class, 'parent_id', 'id');
    }

    /**
     * A save has a file.
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

    public function isUnlisted() {
        return (bool) ($this->flags & 2 == 2);
    }

    public function isListed() {
        return !$this->isUnlisted();
    }

    public function isPrivate() {
        return (bool) ($this->flags & 1 == 1);
    }

    public function isPublic() {
        return !$this->isPrivate();
    }

    public function isLocked() {
        return (bool) ($this->flags & 4 == 4);
    }
}
