<?php

use CodeIgniter\I18n\Time;

if (! function_exists('relative_short')) {
    /**
     * Distance compacte entre une date passee et maintenant : "5s", "12m", "3h", "2d", "1w".
     *
     * Sert dans les listes a forte densite (chat messages, inbox, log d'activite).
     * Bornes : <60s -> Ns, <1h -> Nm, <1d -> Nh, <7d -> Nd, sinon Nw.
     */
    function relative_short(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') return '';
        $now     = Time::now();
        $t       = Time::parse($datetime);
        $seconds = max(1, $now->getTimestamp() - $t->getTimestamp());
        if ($seconds < 60)        return $seconds . 's';
        if ($seconds < 3600)      return intdiv($seconds, 60)    . 'm';
        if ($seconds < 86400)     return intdiv($seconds, 3600)  . 'h';
        if ($seconds < 7 * 86400) return intdiv($seconds, 86400) . 'd';
        return intdiv($seconds, 7 * 86400) . 'w';
    }
}
