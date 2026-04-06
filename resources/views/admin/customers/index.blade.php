@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold"><i class="fas fa-address-book mr-2 text-blue-500"></i>Clientes Frecuentes</h3>
            <p class="text-xs text-gray-500 mt-1">Gestiona clientes, precios preferenciales, descuentos y créditos.</p>
        </div>
        <button onclick="createCustomer()"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Nuevo Cliente
        </button>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-4 py-3 text-gray-600 font-medium"><i class="fas fa-user mr-1 text-xs"></i>Cliente</th>
                        <th class="px-4 py-3 text-gray-600 font-medium"><i class="fas fa-phone mr-1 text-xs"></i>Teléfono</th>
                        <th class="px-4 py-3 text-gray-600 font-medium"><i class="fas fa-id-card mr-1 text-xs"></i>DNI/RUC</th>
                        <th class="px-4 py-3 text-gray-600 font-medium text-center">Tipo</th>
                        <th class="px-4 py-3 text-gray-600 font-medium text-center">Desc.</th>
                        <th class="px-4 py-3 text-gray-600 font-medium text-center"><i class="fas fa-shopping-bag mr-1 text-xs"></i>Compras</th>
                        <th class="px-4 py-3 text-gray-600 font-medium text-center"><i class="fas fa-hand-holding-dollar mr-1 text-xs"></i>Deuda</th>
                        <th class="px-4 py-3 text-gray-600 font-medium text-center"><i class="fas fa-credit-card mr-1 text-xs"></i>Crédito</th>
                        <th class="px-4 py-3 text-gray-600 font-medium text-center">Estado</th>
                        <th class="px-4 py-3 text-gray-600 font-medium text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($customers as $customer)
                    @php $debt = (float) ($customer->pending_debt ?? 0); @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                     style="background-color: var(--brand-primary);">
                                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $customer->name }}</p>
                                    @if($customer->notes)
                                    <p class="text-[10px] text-gray-400">{{ Str::limit($customer->notes, 30) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ $customer->document ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $customer->price_type === 'mayorista' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                <i class="fas {{ $customer->price_type === 'mayorista' ? 'fa-truck' : 'fa-user' }} mr-1 text-[10px]"></i>
                                {{ ucfirst($customer->price_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if((float)$customer->discount_percent > 0)
                            <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-xs font-medium">
                                {{ $customer->discount_percent }}%
                            </span>
                            @else
                            <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-medium">{{ $customer->sales_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($debt > 0)
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-bold">
                                <i class="fas fa-exclamation-triangle mr-0.5 text-[10px]"></i>S/ {{ number_format($debt, 2) }}
                            </span>
                            @else
                            <span class="text-green-600 text-xs"><i class="fas fa-check-circle"></i></span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($customer->credit_blocked)
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-medium">
                                <i class="fas fa-ban mr-0.5"></i>Bloqueado
                            </span>
                            @elseif((float)$customer->credit_limit > 0)
                            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-medium">
                                Lím: S/ {{ number_format($customer->credit_limit, 2) }}
                            </span>
                            @else
                            <span class="text-green-600 text-xs"><i class="fas fa-check-circle"></i> Libre</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="toggleCustomer({{ $customer->id }}, {{ $customer->is_active ? 'true' : 'false' }})"
                                    class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full cursor-pointer transition-colors
                                    {{ $customer->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                <i class="fas {{ $customer->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $customer->is_active ? 'Activo' : 'Inactivo' }}
                            </button>
                            <form id="toggle-cust-{{ $customer->id }}" method="POST" action="{{ route('admin.customers.toggle', $customer) }}" class="hidden">
                                @csrf @method('PATCH')
                            </form>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.customers.show', $customer) }}"
                                   class="text-green-600 hover:text-green-800 transition-colors" title="Historial">
                                    <i class="fas fa-clock-rotate-left"></i>
                                </a>
                                <button onclick="editCustomer({{ $customer->id }}, {{ json_encode([
                                    'name' => $customer->name,
                                    'phone' => $customer->phone ?? '',
                                    'document' => $customer->document ?? '',
                                    'price_type' => $customer->price_type,
                                    'discount_percent' => $customer->discount_percent,
                                    'credit_limit' => $customer->credit_limit,
                                    'notes' => $customer->notes ?? '',
                                ]) }})"
                                        class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                            </div>
                            <form id="update-cust-{{ $customer->id }}" method="POST" action="{{ route('admin.customers.update', $customer) }}" class="hidden">
                                @csrf @method('PUT')
                                <input type="hidden" name="name" id="upd-cust-name-{{ $customer->id }}">
                                <input type="hidden" name="phone" id="upd-cust-phone-{{ $customer->id }}">
                                <input type="hidden" name="document" id="upd-cust-doc-{{ $customer->id }}">
                                <input type="hidden" name="price_type" id="upd-cust-type-{{ $customer->id }}">
                                <input type="hidden" name="discount_percent" id="upd-cust-disc-{{ $customer->id }}">
                                <input type="hidden" name="credit_limit" id="upd-cust-limit-{{ $customer->id }}">
                                <input type="hidden" name="notes" id="upd-cust-notes-{{ $customer->id }}">
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center">
                            <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-400">No hay clientes registrados.</p>
                            <button onclick="createCustomer()" class="mt-3 text-blue-600 hover:underline text-sm">
                                <i class="fas fa-user-plus mr-1"></i>Registrar el primero
                            </button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $customers->links() }}</div>
    </div>
</div>

<form id="create-cust-form" method="POST" action="{{ route('admin.customers.store') }}" class="hidden">
    @csrf
    <input type="hidden" name="name" id="crt-cust-name">
    <input type="hidden" name="phone" id="crt-cust-phone">
    <input type="hidden" name="document" id="crt-cust-doc">
    <input type="hidden" name="price_type" id="crt-cust-type">
    <input type="hidden" name="discount_percent" id="crt-cust-disc">
    <input type="hidden" name="credit_limit" id="crt-cust-limit">
    <input type="hidden" name="notes" id="crt-cust-notes">
</form>

<script>
function customerFormHtml(data = {}) {
    return `
        <div class="text-left space-y-3 mt-2">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-user mr-1 text-gray-400"></i>Nombre <span class="text-red-500">*</span>
                    </label>
                    <input id="swal-cust-name" type="text" class="swal2-input !ml-0 !mr-0"
                           value="${data.name || ''}" placeholder="Nombre completo" style="margin:0;width:100%;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-phone mr-1 text-gray-400"></i>Teléfono
                    </label>
                    <input id="swal-cust-phone" type="text" class="swal2-input !ml-0 !mr-0"
                           value="${data.phone || ''}" placeholder="987654321" style="margin:0;width:100%;">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-id-card mr-1 text-gray-400"></i>DNI/RUC
                    </label>
                    <input id="swal-cust-doc" type="text" class="swal2-input !ml-0 !mr-0"
                           value="${data.document || ''}" placeholder="12345678" style="margin:0;width:100%;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-tag mr-1 text-gray-400"></i>Tipo Precio <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-cust-type" class="swal2-select !ml-0 !mr-0" style="margin:0;width:100%;">
                        <option value="minorista" ${(data.price_type||'minorista')==='minorista'?'selected':''}>Minorista</option>
                        <option value="mayorista" ${data.price_type==='mayorista'?'selected':''}>Mayorista</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-percent mr-1 text-gray-400"></i>Descuento (%)
                    </label>
                    <input id="swal-cust-disc" type="number" step="0.01" min="0" max="100" class="swal2-input !ml-0 !mr-0"
                           value="${data.discount_percent || 0}" style="margin:0;width:100%;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-credit-card mr-1 text-gray-400"></i>Límite crédito (S/)
                    </label>
                    <input id="swal-cust-limit" type="number" step="0.01" min="0" class="swal2-input !ml-0 !mr-0"
                           value="${data.credit_limit || 0}" placeholder="0 = sin límite" style="margin:0;width:100%;">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note mr-1 text-gray-400"></i>Notas
                </label>
                <input id="swal-cust-notes" type="text" class="swal2-input !ml-0 !mr-0"
                       value="${data.notes || ''}" placeholder="Opcional" style="margin:0;width:100%;">
            </div>
        </div>
    `;
}

function getCustomerFormData() {
    const name = document.getElementById('swal-cust-name').value.trim();
    if (!name) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle mr-1"></i> El nombre es obligatorio');
        return false;
    }
    return {
        name, phone: document.getElementById('swal-cust-phone').value.trim(),
        document: document.getElementById('swal-cust-doc').value.trim(),
        price_type: document.getElementById('swal-cust-type').value,
        discount_percent: document.getElementById('swal-cust-disc').value || 0,
        credit_limit: document.getElementById('swal-cust-limit').value || 0,
        notes: document.getElementById('swal-cust-notes').value.trim(),
    };
}

function createCustomer() {
    Swal.fire({
        title: '<i class="fas fa-user-plus text-blue-500 mr-2"></i>Nuevo Cliente',
        html: customerFormHtml(), width: 550,
        showCancelButton: true, confirmButtonColor: '#2563EB', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Guardar', cancelButtonText: 'Cancelar',
        reverseButtons: true, focusConfirm: false,
        didOpen: () => document.getElementById('swal-cust-name').focus(),
        preConfirm: getCustomerFormData,
    }).then((r) => {
        if (r.isConfirmed) {
            const d = r.value;
            document.getElementById('crt-cust-name').value = d.name;
            document.getElementById('crt-cust-phone').value = d.phone;
            document.getElementById('crt-cust-doc').value = d.document;
            document.getElementById('crt-cust-type').value = d.price_type;
            document.getElementById('crt-cust-disc').value = d.discount_percent;
            document.getElementById('crt-cust-limit').value = d.credit_limit;
            document.getElementById('crt-cust-notes').value = d.notes;
            document.getElementById('create-cust-form').submit();
        }
    });
}

function editCustomer(id, data) {
    Swal.fire({
        title: '<i class="fas fa-pen-to-square text-blue-500 mr-2"></i>Editar Cliente',
        html: customerFormHtml(data), width: 550,
        showCancelButton: true, confirmButtonColor: '#2563EB', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Actualizar', cancelButtonText: 'Cancelar',
        reverseButtons: true, focusConfirm: false,
        didOpen: () => document.getElementById('swal-cust-name').focus(),
        preConfirm: getCustomerFormData,
    }).then((r) => {
        if (r.isConfirmed) {
            const d = r.value;
            document.getElementById('upd-cust-name-' + id).value = d.name;
            document.getElementById('upd-cust-phone-' + id).value = d.phone;
            document.getElementById('upd-cust-doc-' + id).value = d.document;
            document.getElementById('upd-cust-type-' + id).value = d.price_type;
            document.getElementById('upd-cust-disc-' + id).value = d.discount_percent;
            document.getElementById('upd-cust-limit-' + id).value = d.credit_limit;
            document.getElementById('upd-cust-notes-' + id).value = d.notes;
            document.getElementById('update-cust-' + id).submit();
        }
    });
}

function toggleCustomer(id, isActive) {
    Swal.fire({
        title: `¿${isActive ? 'Desactivar' : 'Activar'} cliente?`,
        text: isActive ? 'No aparecerá en el POS.' : 'Volverá a estar disponible.',
        icon: isActive ? 'warning' : 'question',
        showCancelButton: true, confirmButtonColor: isActive ? '#DC2626' : '#16A34A', cancelButtonColor: '#6B7280',
        confirmButtonText: `<i class="fas ${isActive ? 'fa-times-circle' : 'fa-check-circle'} mr-1"></i> Sí, ${isActive ? 'desactivar' : 'activar'}`,
        cancelButtonText: 'Cancelar', reverseButtons: true,
    }).then((r) => { if (r.isConfirmed) document.getElementById('toggle-cust-' + id).submit(); });
}
</script>
@endsection