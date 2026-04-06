<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.manage',
            'almacen.entry',
            'almacen.dispatch',
            'almacen.label',
            'almacen.merma',
            'tienda.receive',
            'tienda.sell',
            'caja.open_close',
            'caja.sell',
            'caja.open_package',
            'caja.request_stock',
            'reports.view',
            'reports.profit',
            'transit.validate',
            'inventory.adjust',
            'debts.manage',
            'promotions.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $gerente = Role::firstOrCreate(['name' => 'gerente']);
        $gerente->syncPermissions([
            'reports.view', 'reports.profit', 'transit.validate', 'inventory.adjust',
        ]);

        $almacen = Role::firstOrCreate(['name' => 'almacen']);
        $almacen->syncPermissions([
            'almacen.entry', 'almacen.dispatch', 'almacen.label', 'almacen.merma',
        ]);

        $tienda = Role::firstOrCreate(['name' => 'tienda']);
        $tienda->syncPermissions([
            'tienda.receive', 'tienda.sell',
        ]);

        $caja = Role::firstOrCreate(['name' => 'caja']);
        $caja->syncPermissions([
            'caja.open_close', 'caja.sell', 'caja.open_package', 'caja.request_stock',
        ]);

        $deudas = Role::firstOrCreate(['name' => 'deudas']);
        $deudas->syncPermissions([
            'debts.manage',
        ]);
    }
}