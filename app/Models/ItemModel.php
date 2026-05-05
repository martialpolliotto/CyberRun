<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemModel extends Model
{
    protected $table         = 'items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'slug', 'name', 'description', 'slot',
        'bonus_force', 'bonus_blindage', 'bonus_reflexes', 'bonus_hack',
        'starter',
    ];

    /** Liste canonique des slots supportés (ordre = ordre d'affichage). */
    public const SLOTS = [
        'optique'     => 'Optique',
        'combinaison' => 'Combinaison',
        'bottes'      => 'Bottes',
        'arme1'       => 'Arme principale',
        'arme2'       => 'Arme secondaire',
        'cyberdeck'   => 'Cyberdeck',
    ];

    public function findStarters(): array
    {
        return $this->where('starter', 1)->findAll();
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }
}
