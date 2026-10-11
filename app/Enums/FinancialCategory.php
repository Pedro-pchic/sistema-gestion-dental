<?php

namespace App\Enums;

enum FinancialCategory: string
{
    case Sales = 'sales';

    case OtherIncome = 'other_income';

    case Purchases = 'purchases';

    case Operating = 'operating';

    case OtherExpense = 'other_expense';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Ventas',
            self::OtherIncome => 'Otros ingresos',
            self::Purchases => 'Compras',
            self::Operating => 'Operativos',
            self::OtherExpense => 'Otros gastos',
        };
    }

    /** @return array<int, self> */
    public static function manualExpenseCases(): array
    {
        return [self::Operating, self::OtherExpense];
    }
}
