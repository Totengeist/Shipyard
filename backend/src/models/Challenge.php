<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasRef;
use Shipyard\Traits\HasReleases;
use Shipyard\Traits\HasScreenshots;
use Shipyard\Traits\HasTags;

/**
 * @property string $file_path
 * @property string $title
 * @property string $description
 * @property int    $user_id
 * @property int    $save_id
 * @property int    $downloads
 */
class Challenge extends Model {
    use HasTags;
    use HasReleases;
    use HasScreenshots;
    use HasRef;

    /**
     * Label to use for tag table.
     *
     * @var string
     */
    public static $tag_label = 'challenge';
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ref', 'user_id', 'title', 'description',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = ['id', 'file_path', 'user_id', 'save_id'];

    /**
     * A challenge can belong to a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * A challenge has a save.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function saveFile() {
        return $this->hasOne(Save::class);
    }
}
