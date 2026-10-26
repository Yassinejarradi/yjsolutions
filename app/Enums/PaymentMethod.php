<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Transfer = 'virement';
    case Cheque = 'cheque';
    case Cash = 'especes';
    case Bill = 'effet';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Virement',
            self::Cheque => 'Chèque',
            self::Cash => 'Espèces',
            self::Bill => 'Effet',
        };
    }
}
