<?php

namespace Fitness\MSCommon\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Subscription
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription query()
 * @mixin \Eloquent
 */
class Subscription extends Model
{
    use HasFactory;

    protected $table = 'subscriptions';
}
