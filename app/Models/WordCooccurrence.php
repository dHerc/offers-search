<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WordCooccurrence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WordCooccurrence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WordCooccurrence query()
 * @mixin \Eloquent
 */
class WordCooccurrence extends Model
{
    protected $table = 'word_cooccurrence';
    protected $fillable = [];
}
