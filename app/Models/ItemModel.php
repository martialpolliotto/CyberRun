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
        'vendor_id', 'price',
        'bonus_force', 'bonus_blindage', 'bonus_reflexes', 'bonus_hack',
        'starter',
        'discontinued', 'discontinued_at',
        'image_path', 'model_path',
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
        return $this->where('starter', 1)->where('discontinued', 0)->findAll();
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /**
     * Compte combien de joueurs ont cet item dans leur inventaire (équipé ou non).
     * Utile pour montrer l'impact d'une suppression définitive.
     */
    public function countOwners(int $itemId): int
    {
        return $this->db->table('player_items')
            ->where('item_id', $itemId)
            ->countAllResults();
    }

    /**
     * Marque un item comme "hors-circuit" et déséquipe automatiquement tous les joueurs.
     * Idempotent.
     */
    public function discontinue(int $itemId): void
    {
        $this->update($itemId, [
            'discontinued'    => 1,
            'discontinued_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('player_items')
            ->where('item_id', $itemId)
            ->where('equipped', 1)
            ->update(['equipped' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Réactive un item hors-circuit (le ré-introduit au catalogue, ne re-équipe pas).
     */
    public function restore(int $itemId): void
    {
        $this->update($itemId, [
            'discontinued'    => 0,
            'discontinued_at' => null,
        ]);
    }
}
