<?php

namespace App\Models;

use CodeIgniter\Model;

class VendorModel extends Model
{
    protected $table         = 'vendors';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'slug', 'name', 'tagline', 'description',
        'image_path', 'banner_path',
    ];

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /** Liste tous les vendors triés par nom (pour grille shops + admin). */
    public function listAll(): array
    {
        return $this->orderBy('name')->findAll();
    }

    /** Retourne les items en vente chez un vendor (price > 0, non discontinued). */
    public function getCatalog(int $vendorId): array
    {
        return $this->db->table('items')
            ->where('vendor_id', $vendorId)
            ->where('discontinued', 0)
            ->where('price >', 0)
            ->orderBy('slot')
            ->orderBy('price')
            ->get()
            ->getResultArray();
    }
}
