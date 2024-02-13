<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasRef;
use Shipyard\Traits\HasReleases;
use Shipyard\Traits\HasScreenshots;
use Shipyard\Traits\HasTags;

/**
 * @property string $file_id
 * @property string $title
 * @property string $description
 * @property int    $user_id
 * @property int    $save_id
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
        'ref', 'user_id', 'parent_id', 'file_id', 'title', 'description',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = ['id', 'user_id', 'parent_id', 'file_id', 'save_id'];

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
     * A save has a file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file() {
        return $this->belongsTo(File::class);
    }
}
