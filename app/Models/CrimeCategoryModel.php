<?php

namespace App\Models;

use CodeIgniter\Model;

class CrimeCategoryModel extends Model
{
    protected $table         = 'crime_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'slug', 'name', 'description', 'primary_stat', 'display_order',
    ];

    public const VALID_STATS = ['force', 'blindage', 'reflexes', 'hack'];

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    public function listAll(): array
    {
        return $this->orderBy('display_order')->orderBy('name')->findAll();
    }
}
