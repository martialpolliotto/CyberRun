<?php

if (! function_exists('resolve_username')) {
    /**
     * Resout le pseudo (users.username) d'un player_id donne. Fallback 'inconnu'.
     *
     * Centralise un join qui etait duplique dans 5+ endroits (controllers + services).
     * Disponible globalement via helper('player'), donc utilisable aussi bien depuis
     * un BaseController que depuis un Service ou une vue.
     */
    function resolve_username(int $playerId): string
    {
        $row = db_connect()->table('players p')
            ->select('users.username')
            ->join('users', 'users.id = p.user_id', 'inner')
            ->where('p.id', $playerId)
            ->get()->getRowArray();
        return (string) ($row['username'] ?? 'inconnu');
    }
}
