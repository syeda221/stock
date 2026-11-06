<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseRow extends Model
{
    protected $fillable = [
        'warehouse_id',
        'row_name',
        'pallet_capacity',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Build row-letter mapping:
     * - If row_name starts with letters and digits (e.g. A001 TO A013, B001 TO B013, AA01 TO AA13),
     *   use the explicit prefix (A, B, AA, etc.) directly.
     * - Otherwise, fall back to sequential letters (A, B, C...) based on natural sort.
     */
    public static function getRowLetterMap(): array
    {
        $rowLetterMap = [];
        $allRows = self::orderBy('warehouse_id')->orderBy('row_name')->get()->groupBy('warehouse_id');
        foreach ($allRows as $whId => $rows) {
            $rows = $rows->sortBy('row_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            foreach ($rows as $i => $row) {
                if (preg_match('/^([A-Za-z]+)\d+(\s+TO\s+.*)?$/i', $row->row_name, $m)) {
                    $letter = strtoupper($m[1]);
                } else {
                    $n = $i + 1;
                    $letter = '';
                    while ($n > 0) {
                        $n--;
                        $letter = chr(65 + $n % 26) . $letter;
                        $n = (int)($n / 26);
                    }
                }
                $rowLetterMap[$whId . '-' . $row->row_name] = $letter;
            }
        }
        return $rowLetterMap;
    }

    /**
     * Build row-digits mapping for padding:
     * - e.g. A001 TO A013 -> 3 digits
     * - e.g. AA01 TO AA13 -> 2 digits
     */
    public static function getRowDigitsMap(): array
    {
        $rowDigitsMap = [];
        $allRows = self::orderBy('warehouse_id')->orderBy('row_name')->get()->groupBy('warehouse_id');
        foreach ($allRows as $whId => $rows) {
            foreach ($rows as $row) {
                if (preg_match('/^[A-Za-z]+(\d+)/i', $row->row_name, $m)) {
                    $rowDigitsMap[$whId . '-' . $row->row_name] = strlen($m[1]);
                } else {
                    $rowDigitsMap[$whId . '-' . $row->row_name] = 3;
                }
            }
        }
        return $rowDigitsMap;
    }
}
