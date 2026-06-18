<?php
// database/seeders/ProductosSeeder.php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductosSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            // ── Sacos ────────────────────────────────────────────────────────
            [
                'codigo'          => 'ARR-01',
                'nombre'          => 'Arroz Blanco Precocido',
                'tipo_embalaje'   => 'Sacos',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 22.680, // saco de 50 lb
                'activo'          => true,
            ],
            [
                'codigo'          => 'FRI-01',
                'nombre'          => 'Frijol Rojo de Seda',
                'tipo_embalaje'   => 'Sacos',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 22.680,
                'activo'          => true,
            ],
            [
                'codigo'          => 'MAI-01',
                'nombre'          => 'Maíz Blanco Seco',
                'tipo_embalaje'   => 'Sacos',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 45.360, // saco de 100 lb
                'activo'          => true,
            ],
            [
                'codigo'          => 'AZU-01',
                'nombre'          => 'Azúcar Blanca Refinada',
                'tipo_embalaje'   => 'Sacos',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 22.680,
                'activo'          => true,
            ],
            [
                'codigo'          => 'SAL-01',
                'nombre'          => 'Sal Yodada',
                'tipo_embalaje'   => 'Sacos',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 4.536, // saco de 10 lb
                'activo'          => true,
            ],
            [
                'codigo'          => 'INC-01',
                'nombre'          => 'Incaparina (Harina Fortalecida)',
                'tipo_embalaje'   => 'Sacos',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 22.680,
                'activo'          => true,
            ],

            // ── Cajas ────────────────────────────────────────────────────────
            [
                'codigo'          => 'ACE-01',
                'nombre'          => 'Aceite Vegetal (Botella 900 ml)',
                'tipo_embalaje'   => 'Cajas',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.900,
                'activo'          => true,
            ],
            [
                'codigo'          => 'PAS-01',
                'nombre'          => 'Pasta Alimenticia (Espagueti)',
                'tipo_embalaje'   => 'Cajas',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.400,
                'activo'          => true,
            ],
            [
                'codigo'          => 'AVE-01',
                'nombre'          => 'Avena en Hojuelas',
                'tipo_embalaje'   => 'Cajas',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.800,
                'activo'          => true,
            ],
            [
                'codigo'          => 'LEC-01',
                'nombre'          => 'Leche Entera en Polvo (Bolsa 400 g)',
                'tipo_embalaje'   => 'Cajas',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.400,
                'activo'          => true,
            ],
            [
                'codigo'          => 'CRE-01',
                'nombre'          => 'Crema de Maíz Instantánea',
                'tipo_embalaje'   => 'Cajas',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.500,
                'activo'          => true,
            ],

            // ── Latas ────────────────────────────────────────────────────────
            [
                'codigo'          => 'SAR-01',
                'nombre'          => 'Sardina en Salsa de Tomate',
                'tipo_embalaje'   => 'Latas',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.425,
                'activo'          => true,
            ],
            [
                'codigo'          => 'ATU-01',
                'nombre'          => 'Atún en Agua',
                'tipo_embalaje'   => 'Latas',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.140,
                'activo'          => true,
            ],

            // ── Otro ─────────────────────────────────────────────────────────
            [
                'codigo'          => 'TOR-01',
                'nombre'          => 'Tortillas de Maíz (Paquete)',
                'tipo_embalaje'   => 'Otro',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.500,
                'activo'          => true,
            ],
            [
                'codigo'          => 'PAN-01',
                'nombre'          => 'Pan Francés (Bolsa)',
                'tipo_embalaje'   => 'Otro',
                'unidad_peso'     => 'kg',
                'peso_por_unidad' => 0.250,
                'activo'          => true,
            ],
        ];

        foreach ($productos as $datos) {
            Producto::firstOrCreate(
                ['codigo' => $datos['codigo']],
                $datos
            );
        }

        $this->command->info('✅ ' . count($productos) . ' productos creados correctamente.');
    }
}
