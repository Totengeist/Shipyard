<?php

namespace Shipyard\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivation extends Model {
    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_activations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['created_at', 'token'];

    /**
     * Get the tree the person belongs to.
     */
    public function user() {
        return $this->belongsTo('Shipyard\User');
    }

    public function save(array $options = []) {
        $this->created_at = $this->freshTimestamp();
        $this->token = bin2hex(random_bytes(20));
        parent::save($options);
    }
}
