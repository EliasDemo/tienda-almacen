@extends('layouts.app')

@section('title', 'Usuarios y Roles')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ activeTab: 'users' }">

    {{-- Cabecera con tabs --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-lg font-semibold"><i class="fas fa-user-shield mr-2 text-blue-500"></i>Usuarios y Roles</h3>
                <p class="text-xs text-gray-500 mt-1">Gestiona usuarios, roles y permisos del sistema.</p>
            </div>
            <button onclick="createUser()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                <i class="fas fa-user-plus"></i> Nuevo Usuario
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 border-b">
            <button @click="activeTab = 'users'"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors"
                    :class="activeTab === 'users' ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'">
                <i class="fas fa-users mr-1"></i>Usuarios ({{ $users->count() }})
            </button>
            <button @click="activeTab = 'roles'"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors"
                    :class="activeTab === 'roles' ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'">
                <i class="fas fa-shield-halved mr-1"></i>Roles y Permisos ({{ $roles->count() }})
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- TAB: USUARIOS --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="activeTab === 'users'" x-transition>
        <div class="bg-white rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-5 py-3 text-gray-600 font-medium"><i class="fas fa-user mr-1 text-xs"></i>Usuario</th>
                            <th class="px-5 py-3 text-gray-600 font-medium"><i class="fas fa-envelope mr-1 text-xs"></i>Email</th>
                            <th class="px-5 py-3 text-gray-600 font-medium text-center"><i class="fas fa-shield-halved mr-1 text-xs"></i>Rol</th>
                            <th class="px-5 py-3 text-gray-600 font-medium text-center"><i class="fas fa-key mr-1 text-xs"></i>Permisos</th>
                            <th class="px-5 py-3 text-gray-600 font-medium text-center">Creado</th>
                            <th class="px-5 py-3 text-gray-600 font-medium text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($users as $u)
                        @php
                            $roleColors = [
                                'admin' => ['bg-purple-100 text-purple-800', 'fa-crown', '#7C3AED'],
                                'gerente' => ['bg-blue-100 text-blue-800', 'fa-briefcase', '#2563EB'],
                                'almacen' => ['bg-yellow-100 text-yellow-800', 'fa-warehouse', '#D97706'],
                                'tienda' => ['bg-green-100 text-green-800', 'fa-shop', '#16A34A'],
                                'caja' => ['bg-orange-100 text-orange-800', 'fa-cash-register', '#EA580C'],
                                'deudas' => ['bg-red-100 text-red-800', 'fa-file-invoice-dollar', '#DC2626'],
                            ];
                            $roleName = $u->roles->first()?->name ?? 'sin-rol';
                            $rc = $roleColors[$roleName] ?? ['bg-gray-100 text-gray-800', 'fa-user', '#6B7280'];
                            $permCount = $u->roles->first()?->permissions->count() ?? 0;
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                         style="background-color: {{ $rc[2] }};">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $u->name }}</p>
                                        @if($u->id === auth()->id())
                                        <p class="text-[10px] text-blue-500"><i class="fas fa-circle text-[6px] mr-0.5"></i>Sesión actual</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-600">{{ $u->email }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full {{ $rc[0] }}">
                                    <i class="fas {{ $rc[1] }} text-[10px]"></i>
                                    {{ ucfirst($roleName) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-xs text-gray-500">{{ $permCount }}</span>
                            </td>
                            <td class="px-5 py-3 text-center text-xs text-gray-500">
                                {{ $u->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="editUser({{ $u->id }}, {{ json_encode([
                                        'name' => $u->name,
                                        'email' => $u->email,
                                        'role' => $roleName,
                                    ]) }})"
                                            class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <button onclick="resetPassword({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                            class="text-yellow-600 hover:text-yellow-800 transition-colors" title="Cambiar contraseña">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    @if($u->id !== auth()->id())
                                    <button onclick="deleteUser({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                            class="text-red-500 hover:text-red-700 transition-colors" title="Desactivar">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                    @endif
                                </div>

                                {{-- Forms ocultos --}}
                                <form id="update-user-{{ $u->id }}" method="POST" action="{{ route('admin.users.update', $u) }}" class="hidden">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="name" id="upd-user-name-{{ $u->id }}">
                                    <input type="hidden" name="email" id="upd-user-email-{{ $u->id }}">
                                    <input type="hidden" name="role" id="upd-user-role-{{ $u->id }}">
                                </form>
                                <form id="reset-pw-{{ $u->id }}" method="POST" action="{{ route('admin.users.reset-password', $u) }}" class="hidden">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="password" id="reset-pw-val-{{ $u->id }}">
                                </form>
                                <form id="toggle-user-{{ $u->id }}" method="POST" action="{{ route('admin.users.toggle', $u) }}" class="hidden">
                                    @csrf @method('PATCH')
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- TAB: ROLES Y PERMISOS --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="activeTab === 'roles'" x-transition>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($roles as $role)
            @php
                $rc = $roleColors[$role->name] ?? ['bg-gray-100 text-gray-800', 'fa-user', '#6B7280'];
                $usersInRole = $users->filter(fn($u) => $u->hasRole($role->name))->count();
            @endphp
            <div class="bg-white rounded-lg shadow">
                {{-- Header del rol --}}
                <div class="p-4 border-b flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                             style="background-color: {{ $rc[2] }};">
                            <i class="fas {{ $rc[1] }}"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">{{ ucfirst($role->name) }}</h4>
                            <p class="text-xs text-gray-500">{{ $usersInRole }} {{ $usersInRole === 1 ? 'usuario' : 'usuarios' }}</p>
                        </div>
                    </div>
                    <button onclick="editRolePermissions({{ $role->id }}, '{{ $role->name }}')"
                            class="text-blue-600 hover:text-blue-800 text-sm" title="Editar permisos">
                        <i class="fas fa-pen-to-square"></i>
                    </button>
                </div>

                {{-- Permisos del rol --}}
                <div class="p-4">
                    @if($role->permissions->isEmpty())
                    <p class="text-xs text-gray-400 text-center py-2">
                        <i class="fas fa-info-circle mr-1"></i>Sin permisos asignados
                    </p>
                    @else
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($role->permissions->sortBy('name') as $perm)
                        @php
                            $permColors = [
                                'reports' => 'bg-blue-50 text-blue-700',
                                'almacen' => 'bg-yellow-50 text-yellow-700',
                                'tienda' => 'bg-green-50 text-green-700',
                                'caja' => 'bg-orange-50 text-orange-700',
                                'transit' => 'bg-purple-50 text-purple-700',
                                'inventory' => 'bg-cyan-50 text-cyan-700',
                                'debts' => 'bg-red-50 text-red-700',
                            ];
                            $permColor = 'bg-gray-50 text-gray-600';
                            foreach ($permColors as $key => $color) {
                                if (str_contains($perm->name, $key)) { $permColor = $color; break; }
                            }
                        @endphp
                        <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $permColor }}">
                            {{ $perm->name }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-4 py-2 border-t bg-gray-50 rounded-b-lg">
                    <p class="text-[10px] text-gray-400">
                        <i class="fas fa-key mr-1"></i>{{ $role->permissions->count() }} permisos
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Form oculto crear usuario --}}
<form id="create-user-form" method="POST" action="{{ route('admin.users.store') }}" class="hidden">
    @csrf
    <input type="hidden" name="name" id="crt-user-name">
    <input type="hidden" name="email" id="crt-user-email">
    <input type="hidden" name="password" id="crt-user-pw">
    <input type="hidden" name="role" id="crt-user-role">
</form>

{{-- Forms ocultos para roles --}}
@foreach($roles as $role)
<form id="update-role-{{ $role->id }}" method="POST" action="{{ route('admin.roles.update-permissions', $role) }}" class="hidden">
    @csrf @method('PUT')
    <div id="role-perms-container-{{ $role->id }}"></div>
</form>
@endforeach

<script>
const allRoles = @json($roles->pluck('name'));
const allPermissions = @json($permissions->pluck('name'));
const rolePermissions = @json($roles->mapWithKeys(fn($r) => [$r->id => $r->permissions->pluck('name')]));

function createUser() {
    let roleOptions = '';
    allRoles.forEach(r => {
        roleOptions += `<option value="${r}">${r.charAt(0).toUpperCase() + r.slice(1)}</option>`;
    });

    Swal.fire({
        title: '<i class="fas fa-user-plus text-blue-500 mr-2"></i>Nuevo Usuario',
        html: `
            <div class="text-left space-y-3 mt-2">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user mr-1 text-gray-400"></i>Nombre <span class="text-red-500">*</span>
                        </label>
                        <input id="swal-user-name" type="text" class="swal2-input !ml-0 !mr-0"
                               placeholder="Nombre completo" style="margin:0;width:100%;">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-envelope mr-1 text-gray-400"></i>Email <span class="text-red-500">*</span>
                        </label>
                        <input id="swal-user-email" type="email" class="swal2-input !ml-0 !mr-0"
                               placeholder="correo@ejemplo.com" style="margin:0;width:100%;">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-lock mr-1 text-gray-400"></i>Contraseña <span class="text-red-500">*</span>
                        </label>
                        <input id="swal-user-pw" type="password" class="swal2-input !ml-0 !mr-0"
                               placeholder="Mínimo 6 caracteres" style="margin:0;width:100%;">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-shield-halved mr-1 text-gray-400"></i>Rol <span class="text-red-500">*</span>
                        </label>
                        <select id="swal-user-role" class="swal2-select !ml-0 !mr-0" style="margin:0;width:100%;">
                            ${roleOptions}
                        </select>
                    </div>
                </div>
            </div>
        `,
        width: 550,
        showCancelButton: true, confirmButtonColor: '#2563EB', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Crear Usuario',
        cancelButtonText: 'Cancelar', reverseButtons: true, focusConfirm: false,
        didOpen: () => document.getElementById('swal-user-name').focus(),
        preConfirm: () => {
            const name = document.getElementById('swal-user-name').value.trim();
            const email = document.getElementById('swal-user-email').value.trim();
            const pw = document.getElementById('swal-user-pw').value;
            if (!name) { Swal.showValidationMessage('El nombre es obligatorio'); return false; }
            if (!email) { Swal.showValidationMessage('El email es obligatorio'); return false; }
            if (pw.length < 6) { Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres'); return false; }
            return { name, email, password: pw, role: document.getElementById('swal-user-role').value };
        }
    }).then((r) => {
        if (r.isConfirmed) {
            const d = r.value;
            document.getElementById('crt-user-name').value = d.name;
            document.getElementById('crt-user-email').value = d.email;
            document.getElementById('crt-user-pw').value = d.password;
            document.getElementById('crt-user-role').value = d.role;
            document.getElementById('create-user-form').submit();
        }
    });
}

function editUser(id, data) {
    let roleOptions = '';
    allRoles.forEach(r => {
        roleOptions += `<option value="${r}" ${r === data.role ? 'selected' : ''}>${r.charAt(0).toUpperCase() + r.slice(1)}</option>`;
    });

    Swal.fire({
        title: '<i class="fas fa-pen-to-square text-blue-500 mr-2"></i>Editar Usuario',
        html: `
            <div class="text-left space-y-3 mt-2">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user mr-1 text-gray-400"></i>Nombre <span class="text-red-500">*</span>
                        </label>
                        <input id="swal-edit-name" type="text" class="swal2-input !ml-0 !mr-0"
                               value="${data.name}" style="margin:0;width:100%;">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-envelope mr-1 text-gray-400"></i>Email <span class="text-red-500">*</span>
                        </label>
                        <input id="swal-edit-email" type="email" class="swal2-input !ml-0 !mr-0"
                               value="${data.email}" style="margin:0;width:100%;">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-shield-halved mr-1 text-gray-400"></i>Rol <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-edit-role" class="swal2-select !ml-0 !mr-0" style="margin:0;width:100%;">
                        ${roleOptions}
                    </select>
                </div>
                <p class="text-xs text-gray-400"><i class="fas fa-info-circle mr-1"></i>Para cambiar contraseña usa el botón <i class="fas fa-key"></i> en la tabla.</p>
            </div>
        `,
        width: 550,
        showCancelButton: true, confirmButtonColor: '#2563EB', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Actualizar',
        cancelButtonText: 'Cancelar', reverseButtons: true, focusConfirm: false,
        didOpen: () => document.getElementById('swal-edit-name').focus(),
        preConfirm: () => {
            const name = document.getElementById('swal-edit-name').value.trim();
            const email = document.getElementById('swal-edit-email').value.trim();
            if (!name) { Swal.showValidationMessage('El nombre es obligatorio'); return false; }
            if (!email) { Swal.showValidationMessage('El email es obligatorio'); return false; }
            return { name, email, role: document.getElementById('swal-edit-role').value };
        }
    }).then((r) => {
        if (r.isConfirmed) {
            document.getElementById('upd-user-name-' + id).value = r.value.name;
            document.getElementById('upd-user-email-' + id).value = r.value.email;
            document.getElementById('upd-user-role-' + id).value = r.value.role;
            document.getElementById('update-user-' + id).submit();
        }
    });
}

function resetPassword(id, name) {
    Swal.fire({
        title: '<i class="fas fa-key text-yellow-500 mr-2"></i>Cambiar Contraseña',
        html: `
            <div class="text-left space-y-3 mt-2">
                <p class="text-sm text-gray-600">Usuario: <strong>${name}</strong></p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-lock mr-1 text-gray-400"></i>Nueva Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input id="swal-new-pw" type="password" class="swal2-input !ml-0 !mr-0"
                           placeholder="Mínimo 6 caracteres" style="margin:0;width:100%;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-lock mr-1 text-gray-400"></i>Confirmar Contraseña
                    </label>
                    <input id="swal-confirm-pw" type="password" class="swal2-input !ml-0 !mr-0"
                           placeholder="Repite la contraseña" style="margin:0;width:100%;">
                </div>
            </div>
        `,
        showCancelButton: true, confirmButtonColor: '#D97706', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-key mr-1"></i> Cambiar',
        cancelButtonText: 'Cancelar', reverseButtons: true, focusConfirm: false,
        didOpen: () => document.getElementById('swal-new-pw').focus(),
        preConfirm: () => {
            const pw = document.getElementById('swal-new-pw').value;
            const confirm = document.getElementById('swal-confirm-pw').value;
            if (pw.length < 6) { Swal.showValidationMessage('Mínimo 6 caracteres'); return false; }
            if (pw !== confirm) { Swal.showValidationMessage('Las contraseñas no coinciden'); return false; }
            return pw;
        }
    }).then((r) => {
        if (r.isConfirmed) {
            document.getElementById('reset-pw-val-' + id).value = r.value;
            document.getElementById('reset-pw-' + id).submit();
        }
    });
}

function deleteUser(id, name) {
    Swal.fire({
        title: '<i class="fas fa-user-slash text-red-500 mr-2"></i>¿Desactivar usuario?',
        html: `<p class="text-sm text-gray-600"><strong>${name}</strong> no podrá acceder al sistema.</p>`,
        icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#DC2626', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-user-slash mr-1"></i> Desactivar',
        cancelButtonText: 'Cancelar', reverseButtons: true,
    }).then((r) => {
        if (r.isConfirmed) document.getElementById('toggle-user-' + id).submit();
    });
}

function editRolePermissions(roleId, roleName) {
    const currentPerms = rolePermissions[roleId] || [];
    let checkboxes = '';

    // Agrupar permisos por prefijo
    const groups = {};
    allPermissions.forEach(p => {
        const parts = p.split('.');
        const group = parts[0];
        if (!groups[group]) groups[group] = [];
        groups[group].push(p);
    });

    const groupIcons = {
        'reports': 'fa-chart-bar', 'almacen': 'fa-warehouse', 'tienda': 'fa-shop',
        'caja': 'fa-cash-register', 'transit': 'fa-route', 'inventory': 'fa-boxes-stacked',
        'debts': 'fa-file-invoice-dollar',
    };

    for (const [group, perms] of Object.entries(groups)) {
        const icon = groupIcons[group] || 'fa-key';
        checkboxes += `<div class="mb-3">
            <p class="text-xs font-semibold text-gray-600 mb-1.5 uppercase">
                <i class="fas ${icon} mr-1"></i>${group}
            </p>
            <div class="flex flex-wrap gap-2">`;

        perms.forEach(p => {
            const checked = currentPerms.includes(p) ? 'checked' : '';
            const shortName = p.split('.').slice(1).join('.');
            checkboxes += `
                <label class="flex items-center gap-1.5 bg-gray-50 rounded px-2 py-1 cursor-pointer hover:bg-blue-50 transition-colors">
                    <input type="checkbox" value="${p}" class="role-perm-cb rounded text-blue-600" ${checked}>
                    <span class="text-xs text-gray-700">${shortName || p}</span>
                </label>`;
        });

        checkboxes += `</div></div>`;
    }

    Swal.fire({
        title: `<i class="fas fa-shield-halved text-blue-500 mr-2"></i>Permisos: ${roleName.charAt(0).toUpperCase() + roleName.slice(1)}`,
        html: `
            <div class="text-left mt-2 max-h-[400px] overflow-y-auto">
                ${checkboxes}
            </div>
            <div class="mt-3 flex gap-2 justify-end">
                <button type="button" onclick="document.querySelectorAll('.role-perm-cb').forEach(c=>c.checked=true)" class="text-xs text-blue-600 hover:underline">
                    <i class="fas fa-check-double mr-1"></i>Todos
                </button>
                <button type="button" onclick="document.querySelectorAll('.role-perm-cb').forEach(c=>c.checked=false)" class="text-xs text-red-600 hover:underline">
                    <i class="fas fa-times mr-1"></i>Ninguno
                </button>
            </div>
        `,
        width: 600,
        showCancelButton: true, confirmButtonColor: '#2563EB', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Guardar Permisos',
        cancelButtonText: 'Cancelar', reverseButtons: true,
        preConfirm: () => {
            const checked = [];
            document.querySelectorAll('.role-perm-cb:checked').forEach(cb => checked.push(cb.value));
            return checked;
        }
    }).then((r) => {
        if (r.isConfirmed) {
            const container = document.getElementById('role-perms-container-' + roleId);
            container.innerHTML = '';
            r.value.forEach(p => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'permissions[]'; input.value = p;
                container.appendChild(input);
            });
            if (r.value.length === 0) {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'permissions'; input.value = '';
                container.appendChild(input);
            }
            document.getElementById('update-role-' + roleId).submit();
        }
    });
}
</script>
@endsection