<?php

namespace App\Models;

use CodeIgniter\Model;

class GameSettingModel extends Model
{
    protected $table         = 'game_settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'setting_key', 'value', 'label', 'description', 'type', 'category',
    ];

    /** Cache memoire des settings deja lus (clef => row), partage entre appels. */
    private static array $cache = [];

    /**
     * Lit un setting cast selon son type. Si manquant, renvoie $default.
     *
     * Types supportes : int, float, bool, string.
     *
     * @param int|float|bool|string|null $default
     * @return int|float|bool|string|null
     */
    public function get(string $key, $default = null)
    {
        if (! isset(self::$cache[$key])) {
            $row = $this->where('setting_key', $key)->first();
            self::$cache[$key] = $row ?: false;
        }
        $row = self::$cache[$key];
        if ($row === false) {
            return $default;
        }
        return $this->castValue((string) ($row['value'] ?? ''), (string) ($row['type'] ?? 'string'));
    }

    /** Met a jour la valeur (string) d'un setting et invalide le cache pour cette clef. */
    public function setValue(string $key, string $value): bool
    {
        $row = $this->where('setting_key', $key)->first();
        if ($row === null) {
            return false;
        }
        $ok = $this->update($row['id'], ['value' => $value]);
        unset(self::$cache[$key]);
        return (bool) $ok;
    }

    /** Liste tous les settings groupes par categorie. */
    public function listGrouped(): array
    {
        $rows = $this->orderBy('category')->orderBy('setting_key')->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $cat = (string) ($r['category'] ?? 'general');
            $out[$cat][] = $r;
        }
        return $out;
    }

    private function castValue(string $raw, string $type)
    {
        return match ($type) {
            'int'   => (int) $raw,
            'float' => (float) $raw,
            'bool'  => in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true),
            default => $raw,
        };
    }
}
