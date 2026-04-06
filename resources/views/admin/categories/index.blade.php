@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Cabecera --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold"><i class="fas fa-tags mr-2 text-blue-500"></i>Categorías de Productos</h3>
            <p class="text-xs text-gray-500 mt-1">Administra las categorías para organizar tus productos.</p>
        </div>
        <button onclick="createCategory()"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> Nueva Categoría
        </button>
    </div>

    {{-- Lista --}}
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-6 py-3 text-gray-600 font-medium"><i class="fas fa-tag mr-1 text-xs"></i>Nombre</th>
                        <th class="px-6 py-3 text-gray-600 font-medium"><i class="fas fa-align-left mr-1 text-xs"></i>Descripción</th>
                        <th class="px-6 py-3 text-gray-600 font-medium text-center"><i class="fas fa-boxes-stacked mr-1 text-xs"></i>Productos</th>
                        <th class="px-6 py-3 text-gray-600 font-medium text-center">Estado</th>
                        <th class="px-6 py-3 text-gray-600 font-medium text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($categories as $cat)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-medium text-gray-800">{{ $cat->name }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">
                            {{ $cat->description ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full text-xs font-medium">
                                {{ $cat->products_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="toggleCategory({{ $cat->id }}, {{ $cat->is_active ? 'true' : 'false' }})"
                                    class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full cursor-pointer transition-colors
                                    {{ $cat->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                <i class="fas {{ $cat->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $cat->is_active ? 'Activa' : 'Inactiva' }}
                            </button>
                            <form id="toggle-cat-{{ $cat->id }}" method="POST" action="{{ route('admin.categories.toggle', $cat) }}" class="hidden">
                                @csrf @method('PATCH')
                            </form>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="editCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ addslashes($cat->description ?? '') }}')"
                                        class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                            </div>
                            <form id="update-cat-{{ $cat->id }}" method="POST" action="{{ route('admin.categories.update', $cat) }}" class="hidden">
                                @csrf @method('PUT')
                                <input type="hidden" name="name" id="update-cat-name-{{ $cat->id }}">
                                <input type="hidden" name="description" id="update-cat-desc-{{ $cat->id }}">
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-400">No hay categorías registradas.</p>
                            <button onclick="createCategory()" class="mt-3 text-blue-600 hover:underline text-sm">
                                <i class="fas fa-plus mr-1"></i>Crear la primera
                            </button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Formulario oculto para crear --}}
<form id="create-cat-form" method="POST" action="{{ route('admin.categories.store') }}" class="hidden">
    @csrf
    <input type="hidden" name="name" id="create-cat-name">
    <input type="hidden" name="description" id="create-cat-desc">
</form>

<script>
function createCategory() {
    Swal.fire({
        title: '<i class="fas fa-plus-circle text-blue-500 mr-2"></i>Nueva Categoría',
        html: `
            <div class="text-left space-y-4 mt-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-tag mr-1 text-gray-400"></i>Nombre <span class="text-red-500">*</span>
                    </label>
                    <input id="swal-name" type="text" class="swal2-input w-full !ml-0 !mr-0"
                           placeholder="Ej: Aves, Embutidos, Lácteos" style="margin:0; width:100%;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-align-left mr-1 text-gray-400"></i>Descripción
                    </label>
                    <input id="swal-desc" type="text" class="swal2-input w-full !ml-0 !mr-0"
                           placeholder="Descripción opcional" style="margin:0; width:100%;">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#2563EB',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Guardar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusConfirm: false,
        didOpen: () => {
            document.getElementById('swal-name').focus();
        },
        preConfirm: () => {
            const name = document.getElementById('swal-name').value.trim();
            if (!name) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle mr-1"></i> El nombre es obligatorio');
                return false;
            }
            return { name: name, description: document.getElementById('swal-desc').value.trim() };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('create-cat-name').value = result.value.name;
            document.getElementById('create-cat-desc').value = result.value.description;
            document.getElementById('create-cat-form').submit();
        }
    });
}

function editCategory(id, name, description) {
    Swal.fire({
        title: '<i class="fas fa-pen-to-square text-blue-500 mr-2"></i>Editar Categoría',
        html: `
            <div class="text-left space-y-4 mt-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-tag mr-1 text-gray-400"></i>Nombre <span class="text-red-500">*</span>
                    </label>
                    <input id="swal-edit-name" type="text" class="swal2-input w-full !ml-0 !mr-0"
                           value="${name}" style="margin:0; width:100%;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-align-left mr-1 text-gray-400"></i>Descripción
                    </label>
                    <input id="swal-edit-desc" type="text" class="swal2-input w-full !ml-0 !mr-0"
                           value="${description}" style="margin:0; width:100%;">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#2563EB',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Actualizar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusConfirm: false,
        didOpen: () => {
            document.getElementById('swal-edit-name').focus();
        },
        preConfirm: () => {
            const name = document.getElementById('swal-edit-name').value.trim();
            if (!name) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle mr-1"></i> El nombre es obligatorio');
                return false;
            }
            return { name: name, description: document.getElementById('swal-edit-desc').value.trim() };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('update-cat-name-' + id).value = result.value.name;
            document.getElementById('update-cat-desc-' + id).value = result.value.description;
            document.getElementById('update-cat-' + id).submit();
        }
    });
}

function toggleCategory(id, isActive) {
    const action = isActive ? 'desactivar' : 'activar';
    const icon = isActive ? 'warning' : 'question';
    const color = isActive ? '#DC2626' : '#16A34A';

    Swal.fire({
        title: `¿${isActive ? 'Desactivar' : 'Activar'} categoría?`,
        text: isActive
            ? 'Los productos de esta categoría no aparecerán en el POS.'
            : 'La categoría y sus productos volverán a estar disponibles.',
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: color,
        cancelButtonColor: '#6B7280',
        confirmButtonText: `<i class="fas ${isActive ? 'fa-times-circle' : 'fa-check-circle'} mr-1"></i> Sí, ${action}`,
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('toggle-cat-' + id).submit();
        }
    });
}
</script>
@endsection
