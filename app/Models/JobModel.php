<?php

namespace App\Models;

use CodeIgniter\Model;

class JobModel extends Model
{
    protected $table         = 'jobs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'slug', 'name', 'description', 'primary_stat',
        'stat_1', 'stat_2',
        'work_energy_cost', 'display_order',
    ];

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    public function listAll(): array
    {
        return $this->orderBy('display_order')->orderBy('name')->findAll();
    }
}
