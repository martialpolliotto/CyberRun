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
        // ---- Consommables ----
        'consumable_type', 'cooldown_seconds',
        'effect_hp', 'effect_nrg', 'effect_nrv',
        'effect_force', 'effect_blindage', 'effect_reflexes', 'effect_hack',
        'effect_hp_max', 'effect_nrg_max', 'effect_nrv_max',
        'effect_duration_seconds',
        'addiction_threshold_increase',
        'overdose_chance_pct', 'overdose_hospital_min', 'overdose_hospital_max',
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

    /** Types de consommables (kind d'effet actif que ca peut produire). */
    public const CONSUMABLE_TYPES = ['booster', 'drug'];

    /** Categories haut niveau utilisees par le filtre inventory (derivees de slot + consumable_type). */
    public const CATEGORIES = [
        'all'        => 'Tous',
        'equipped'   => 'Équipés',
        'available'  => 'Disponibles',
        'weapon'     => 'Armes',
        'protection' => 'Protection',
        'cyberware'  => 'Cyberware',
        'booster'    => 'Boosters',
        'drug'       => 'Drogues',
    ];

    /** Slot -> categorie haut niveau (uniquement pour les items d'equipement, pas pour les consommables). */
    public const SLOT_TO_CATEGORY = [
        'arme1'       => 'weapon',
        'arme2'       => 'weapon',
        'optique'     => 'protection',
        'combinaison' => 'protection',
        'bottes'      => 'protection',
        'cyberdeck'   => 'cyberware',
    ];

    /**
     * Resout la categorie haut niveau d'un item (utilise pour les filtres / regroupement UI).
     *
     * @param array<string,mixed> $item
     */
    public static function resolveCategory(array $item): string
    {
        $type = $item['consumable_type'] ?? ($item['item_consumable_type'] ?? null);
        if ($type === 'booster') return 'booster';
        if ($type === 'drug')    return 'drug';
        $slot = $item['slot'] ?? ($item['item_slot'] ?? '');
        return self::SLOT_TO_CATEGORY[$slot] ?? 'misc';
    }

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
