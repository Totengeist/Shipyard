<?php

namespace Shipyard\Models;

use Illuminate\Database\Eloquent\Model;
use Shipyard\Traits\HasRef;
use Shipyard\Traits\HasReleases;
use Shipyard\Traits\HasScreenshots;
use Shipyard\Traits\HasTags;
use Valitron\Validator;

class Save extends Model {
    use HasTags;
    use HasReleases;
    use HasScreenshots;
    use HasRef;

    /**
     * Label to use for tag table.
     *
     * @str
     */
    public static $tag_label = 'save';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ref', 'user_id', 'file_path', 'title', 'description', 'downloads',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['id', 'file_path', 'user_id'];

    /**
     * A save can belong to a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    public static function validator(array $data, Validator $v = null) {
        if ($v === null) {
            $v = new Validator($data);
        }

        $v->rules([
            'required' => [
                ['user_id'],
                ['title'],
                ['description'],
                ['file_path']
            ]
        ]);

        return $v;
    }
}
