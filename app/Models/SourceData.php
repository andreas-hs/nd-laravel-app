<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceData extends Model
{
    protected $table = 'source_data';

    protected $fillable = ['name', 'description', 'created_at'];

    public $incrementing = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public $primaryKey = 'id';
}
