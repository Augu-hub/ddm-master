<?php

namespace App\Enums;

enum NomenclatureType: string
{
    case RC = 'RC';
    case RF = 'RF';
    case RS = 'RS';
    case RO = 'RO';

    public function label(): string
    {
        return match($this) {
            self::RC => 'Risque de Conformite',
            self::RF => 'Risque Financier',
            self::RS => 'Risque Strategique',
            self::RO => 'Risque Operationnel',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::RC => '#4361ee',
            self::RF => '#e63946',
            self::RS => '#7209b7',
            self::RO => '#f77f00',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::RC => 'primary',
            self::RF => 'danger',
            self::RS => 'purple',
            self::RO => 'warning',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::RC => 'ti ti-shield-check',
            self::RF => 'ti ti-coin',
            self::RS => 'ti ti-chess',
            self::RO => 'ti ti-settings-cog',
        };
    }
}
