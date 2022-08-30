<?php

namespace Shipyard;

use Illuminate\Database\Eloquent\Model;

class Challenge extends Model {
    use HasTags;
    use HasRef;

    /**
     * Label to use for tag table.
     *
     * @str
     */
    protected $tag_label = 'challenge';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ref', 'user_id', 'title', 'description',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['id', 'file_path', 'user_id', 'save_id'];

    /**
     * A ship can belong to a user.
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
