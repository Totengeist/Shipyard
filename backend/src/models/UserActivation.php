<?php

namespace Shipyard\Models;

/**
 * @property string $created_at
 * @property string $token
 * @property string $email
 */
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
     * @var string[]
     */
    protected $fillable = ['email'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = ['created_at', 'token'];

    /**
     * Get the tree the person belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo('Shipyard\User');
    }

    /**
     * Override the save function to verify a name is present and to clean the
     * slug.
     *
     * @param mixed[] $options
     *
     * return bool
     */
    public function save(array $options = []) {
        $this->created_at = $this->freshTimestamp();
        $this->token = bin2hex(random_bytes(20));

        return parent::save($options);
    }
}
