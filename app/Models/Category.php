<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
