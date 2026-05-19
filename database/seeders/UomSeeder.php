<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Uom;

class UomSeeder extends Seeder
{
    public function run()
    {
        $uoms = [
            'Kilogram', 'Gram', 'Ton', 'Pound', 'Ounce',
            'Liter', 'Milliliter', 'Gallon', 'Pint', 'Quart',
            'Meter', 'Centimeter', 'Kilometer', 'Inch', 'Foot',
            'Yard', 'Mile', 'Piece', 'Dozen', 'Box',
            'Carton', 'Pallet', 'Container', 'Bag', 'Pack',
            'Bundle', 'Roll', 'Sheet', 'Set', 'Unit',
            'Case', 'Crate', 'Drum', 'Barrel', 'Can',
            'Bottle', 'Jar', 'Tube', 'Packet', 'Sachet',
        ];

        foreach ($uoms as $uom) {
            Uom::create([
                'name' => $uom,
                'status' => 1,
            ]);
        }
    }
}
