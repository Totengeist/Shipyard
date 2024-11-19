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
 * @property int    $flags
 * @property int    $user_id
 * @property int    $parent_id
 * @property int    $downloads
 */
class Modification extends Model {
    use HasTags;
    use HasReleases;
    use HasScreenshots;
    use HasRef;

    /**
     * Label to use for tag table.
     *
     * @var string
     */
    public static $tag_label = 'modification';
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
     * A modification can belong to a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * A modification can have a parent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent() {
        return $this->hasOne(Modification::class, 'id', 'parent_id');
    }

    /**
     * A modification can have a child.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function child() {
        return $this->hasOne(Modification::class, 'parent_id', 'id');
    }

    /**
     * A mod has a file.
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

    /**
     * Delete the resource and the associated file.
     *
     * @return bool
     */
    public function delete() {
        return $this->file->delete() && parent::delete();
    }

    /**
     * Whether the file is unlisted.
     *
     * @return bool
     */
    public function isUnlisted() {
        return ($this->flags & 2) == 2;
    }

    /**
     * Whether the file is listed.
     *
     * @return bool
     */
    public function isListed() {
        return !$this->isUnlisted();
    }

    /**
     * Whether the file is private.
     *
     * @return bool
     */
    public function isPrivate() {
        return ($this->flags & 1) == 1;
    }

    /**
     * Whether the file is public.
     *
     * @return bool
     */
    public function isPublic() {
        return !$this->isPrivate();
    }

    /**
     * Whether the file has editing locked.
     *
     * @return bool
     */
    public function isLocked() {
        return ($this->flags & 4) == 4;
    }
}
