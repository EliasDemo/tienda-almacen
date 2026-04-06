<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sistema Ventas') }} - @yield('title', 'Inicio')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --brand-primary: #2563EB;
            --brand-primary-dark: #1D4ED8;
            --brand-secondary: #0F172A;
            --brand-success: #16A34A;
            --brand-success-light: #DCFCE7;
            --brand-danger: #DC2626;
            --brand-danger-light: #FEE2E2;
            --brand-warning: #F59E0B;
            --brand-warning-light: #FEF3C7;
            --brand-info: #0EA5E9;
            --brand-info-light: #E0F2FE;
            --brand-purple: #7C3AED;
            --brand-purple-light: #EDE9FE;
            --brand-orange: #EA580C;
            --brand-orange-light: #FFF7ED;
            --sidebar-bg: #0F172A;
            --sidebar-active: #2563EB;
            --sidebar-hover: #1E293B;
            --sidebar-text: #94A3B8;
            --sidebar-text-active: #FFFFFF;
        }
        .swal2-popup { border-radius: 12px !important; font-family: inherit !important; }
        .swal2-title { font-size: 1.25rem !important; }
        .swal2-confirm { border-radius: 8px !important; font-weight: 600 !important; }
        .swal2-cancel { border-radius: 8px !important; }
        .swal2-input, .swal2-select, .swal2-textarea { border-radius: 8px !important; }
        .swal2-toast { font-size: 0.875rem !important; }
        nav::-webkit-scrollbar { width: 4px; }
        nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="{ sidebarOpen: true }">

    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside x-show="sidebarOpen" x-transition:enter="transition-transform duration-200"
               x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition-transform duration-200"
               x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
               class="w-64 flex flex-col flex-shrink-0"
               style="background-color: var(--sidebar-bg);">

            {{-- Logo --}}
            <div class="h-16 flex items-center justify-center border-b border-gray-700/50 gap-2">
                <i class="fas fa-store text-blue-400 text-lg"></i>
                <h1 class="text-lg font-bold text-white tracking-tight">Sistema Ventas</h1>
            </div>

            {{-- Usuario --}}
            <div class="px-4 py-3 border-b border-gray-700/50 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white"
                     style="background-color: var(--brand-primary);">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div>
                    <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                    <p class="text-xs" style="color: var(--sidebar-text);">{{ Auth::user()->getRoleNames()->first() }}</p>
                </div>
            </div>

            {{-- Navegación --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

                @php
                    $navActive = 'background-color: var(--sidebar-active); color: var(--sidebar-text-active);';
                    $navDefault = 'color: var(--sidebar-text);';
                @endphp

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-gray-700/50"
                   style="{{ request()->routeIs('dashboard') ? $navActive : $navDefault }}">
                    <i class="fas fa-tachometer-alt w-5 text-center text-xs"></i>
                    <span>Dashboard</span>
                </a>

                {{-- ALMACÉN --}}
                @if(auth()->user()->hasAnyRole(['admin', 'almacen']))
                <div class="pt-5">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest" style="color: #475569;">
                        <i class="fas fa-warehouse mr-1.5"></i>Almacén
                    </p>
                    <a href="{{ route('almacen.entries.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('almacen.entries.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-truck-loading w-5 text-center text-xs"></i><span>Entradas</span>
                    </a>
                    <a href="{{ route('almacen.packages.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('almacen.packages.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-boxes-stacked w-5 text-center text-xs"></i><span>Bultos / Etiquetas</span>
                    </a>
                    <a href="{{ route('almacen.transfers.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('almacen.transfers.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-truck w-5 text-center text-xs"></i><span>Despachos</span>
                    </a>
                    <a href="{{ route('almacen.orders.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('almacen.orders.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-bell w-5 text-center text-xs"></i><span>Pedidos Recibidos</span>
                    </a>
                </div>
                @endif

                {{-- TIENDA --}}
                @if(auth()->user()->hasAnyRole(['admin', 'tienda']))
                <div class="pt-5">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest" style="color: #475569;">
                        <i class="fas fa-shop mr-1.5"></i>Tienda
                    </p>
                    <a href="{{ route('tienda.reception.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('tienda.reception.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-clipboard-check w-5 text-center text-xs"></i><span>Recepción</span>
                    </a>
                </div>
                @endif

                {{-- CAJA --}}
                @if(auth()->user()->hasAnyRole(['admin', 'caja']))
                <div class="pt-5">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest" style="color: #475569;">
                        <i class="fas fa-cash-register mr-1.5"></i>Caja
                    </p>
                    <a href="{{ route('pos.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('pos.index') ? $navActive : $navDefault }}">
                        <i class="fas fa-calculator w-5 text-center text-xs"></i><span>Punto de Venta</span>
                    </a>
                    <a href="{{ route('pos.credits.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('pos.credits.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-hand-holding-dollar w-5 text-center text-xs"></i><span>Fiados</span>
                    </a>
                    <a href="{{ route('pos.orders.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('pos.orders.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-clipboard-list w-5 text-center text-xs"></i><span>Pedidos</span>
                    </a>
                    <a href="{{ route('pos.close-register') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('pos.close-register') ? $navActive : $navDefault }}">
                        <i class="fas fa-lock w-5 text-center text-xs"></i><span>Cerrar Caja</span>
                    </a>
                </div>
                @endif

                {{-- REPORTES --}}
                @if(auth()->user()->hasAnyRole(['admin', 'gerente', 'caja']))
                <div class="pt-5">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest" style="color: #475569;">
                        <i class="fas fa-chart-bar mr-1.5"></i>Reportes
                    </p>
                    <a href="{{ route('reports.cash.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('reports.cash.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-folder-open w-5 text-center text-xs"></i><span>Historial de Cajas</span>
                    </a>
                    @if(auth()->user()->hasAnyRole(['admin', 'gerente']))
                    <a href="#"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ $navDefault }}">
                        <i class="fas fa-route w-5 text-center text-xs"></i><span>Validar Tránsito</span>
                    </a>
                    <a href="#"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ $navDefault }}">
                        <i class="fas fa-chart-line w-5 text-center text-xs"></i><span>Ganancia / Pérdida</span>
                    </a>
                    <a href="{{ route('stock.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                        style="{{ request()->routeIs('stock.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-warehouse w-5 text-center text-xs"></i><span>Stock Tienda</span>
                    </a>
                    @endif
                </div>
                @endif

                {{-- DEUDAS --}}
                @if(auth()->user()->hasAnyRole(['admin', 'deudas']))
                <div class="pt-5">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest" style="color: #475569;">
                        <i class="fas fa-file-invoice-dollar mr-1.5"></i>Deudas
                    </p>
                    <a href="#"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ $navDefault }}">
                        <i class="fas fa-users w-5 text-center text-xs"></i><span>Clientes / Deudas</span>
                    </a>
                </div>
                @endif

                {{-- ADMIN --}}
                @if(auth()->user()->hasRole('admin'))
                <div class="pt-5">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest" style="color: #475569;">
                        <i class="fas fa-cog mr-1.5"></i>Administración
                    </p>
                    <a href="{{ route('admin.categories.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('admin.categories.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-tags w-5 text-center text-xs"></i><span>Categorías</span>
                    </a>
                    <a href="{{ route('admin.products.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('admin.products.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-drumstick-bite w-5 text-center text-xs"></i><span>Productos</span>
                    </a>
                    <a href="{{ route('admin.customers.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('admin.customers.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-address-book w-5 text-center text-xs"></i><span>Clientes</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ request()->routeIs('admin.users.*') ? $navActive : $navDefault }}">
                        <i class="fas fa-user-shield w-5 text-center text-xs"></i><span>Usuarios</span>
                    </a>
                    <a href="#"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-700/50"
                       style="{{ $navDefault }}">
                        <i class="fas fa-gift w-5 text-center text-xs"></i><span>Promociones</span>
                    </a>
                </div>
                @endif

            </nav>

            {{-- Cerrar Sesión --}}
            <div class="px-3 py-4 border-t border-gray-700/50">
                <button onclick="confirmLogout()"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors hover:bg-red-600/20"
                        style="color: #EF4444;">
                    <i class="fas fa-sign-out-alt w-5 text-center text-xs"></i>
                    <span>Cerrar Sesión</span>
                </button>
                <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
            </div>
        </aside>

        {{-- Contenido principal --}}
        <div class="flex-1 flex flex-col">

            {{-- Top bar --}}
            <header class="h-14 bg-white shadow-sm flex items-center justify-between px-6 border-b border-gray-200">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-gray-700 transition-colors">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="text-base font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                </div>
                <div class="flex items-center gap-5">
                    <span class="text-xs text-gray-400 hidden md:inline">
                        <i class="far fa-calendar mr-1"></i>{{ now()->translatedFormat('d M Y') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white"
                             style="background-color: var(--brand-primary);">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <span class="text-sm text-gray-600 hidden md:inline">{{ Auth::user()->name }}</span>
                    </div>
                </div>
            </header>

            {{-- Contenido --}}
            <main class="flex-1 p-6">
                @yield('content')
            </main>

        </div>
    </div>

    {{-- SISTEMA GLOBAL DE ALERTAS --}}
    <script>
    const Toast = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false,
        timer: 3000, timerProgressBar: true,
        didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; }
    });

    function showToast(type, message) { Toast.fire({ icon: type, title: message }); }
    function showSuccess(message) { showToast('success', message); }
    function showError(message) { showToast('error', message); }
    function showWarning(message) { showToast('warning', message); }
    function showInfo(message) { showToast('info', message); }

    function confirmAction(options = {}) {
        return Swal.fire({
            title: options.title || '¿Estás seguro?', text: options.text || 'Esta acción no se puede deshacer.',
            icon: options.icon || 'warning', showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#2563EB', cancelButtonColor: '#6B7280',
            confirmButtonText: options.confirmText || 'Sí, continuar', cancelButtonText: options.cancelText || 'Cancelar',
            reverseButtons: true,
        });
    }

    function confirmDelete(options = {}) {
        return Swal.fire({
            title: options.title || '¿Eliminar?', text: options.text || 'No podrás recuperar este registro.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#DC2626', cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-trash mr-1"></i> Sí, eliminar', cancelButtonText: 'Cancelar', reverseButtons: true,
        });
    }

    function confirmDanger(options = {}) {
        return Swal.fire({
            title: options.title || '¿Confirmar?', text: options.text || '', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#DC2626', cancelButtonColor: '#6B7280',
            confirmButtonText: options.confirmText || 'Sí, confirmar', cancelButtonText: 'Cancelar', reverseButtons: true,
        });
    }

    function promptInput(options = {}) {
        return Swal.fire({
            title: options.title || 'Ingrese valor', input: options.inputType || 'text',
            inputLabel: options.label || '', inputValue: options.value || '',
            inputPlaceholder: options.placeholder || '', inputAttributes: options.attributes || {},
            showCancelButton: true, confirmButtonColor: '#2563EB', cancelButtonColor: '#6B7280',
            confirmButtonText: options.confirmText || 'Guardar', cancelButtonText: 'Cancelar',
            reverseButtons: true, inputValidator: options.validator || null,
        });
    }

    function promptSelect(options = {}) {
        return Swal.fire({
            title: options.title || 'Seleccione', input: 'select',
            inputOptions: options.options || {}, inputPlaceholder: options.placeholder || '-- Seleccionar --',
            showCancelButton: true, confirmButtonColor: '#2563EB', cancelButtonColor: '#6B7280',
            confirmButtonText: options.confirmText || 'Aceptar', cancelButtonText: 'Cancelar', reverseButtons: true,
        });
    }

    function showSaleSuccess(data) {
        let html = `<p class="text-gray-500 mb-1">N° ${data.saleNumber}</p><p class="text-3xl font-bold mb-3">S/ ${data.total}</p>`;
        if (data.creditAmount > 0) {
            html += `<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">
                <p class="text-orange-700"><i class="fas fa-hand-holding-dollar mr-1"></i>Fiado: S/ ${data.creditAmount.toFixed(2)}</p></div>`;
        }
        if (data.change > 0 && data.creditAmount <= 0) {
            html += `<div class="bg-yellow-50 rounded-lg p-3 mb-3">
                <p class="text-yellow-700"><i class="fas fa-exchange-alt mr-1"></i>Vuelto: S/ ${data.change.toFixed(2)}</p></div>`;
        }
        return Swal.fire({
            icon: 'success', title: 'Venta Exitosa', html: html,
            confirmButtonColor: '#2563EB', confirmButtonText: '<i class="fas fa-plus-circle mr-1"></i> Nueva Venta',
            allowOutsideClick: false,
        });
    }

    function confirmLogout() {
        Swal.fire({
            title: '¿Cerrar sesión?', text: 'Se cerrará tu sesión actual.', icon: 'question',
            showCancelButton: true, confirmButtonColor: '#DC2626', cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-sign-out-alt mr-1"></i> Cerrar sesión',
            cancelButtonText: 'Cancelar', reverseButtons: true,
        }).then((result) => { if (result.isConfirmed) document.getElementById('logout-form').submit(); });
    }

    function confirmSubmit(formId, options = {}) {
        confirmAction(options).then((result) => { if (result.isConfirmed) document.getElementById(formId).submit(); });
    }

    @if(session('success')) showSuccess(@json(session('success'))); @endif
    @if(session('error')) showError(@json(session('error'))); @endif
    @if($errors->any())
        Swal.fire({
            icon: 'error', title: 'Se encontraron errores',
            html: `<ul class="text-left text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li class="text-red-600"><i class="fas fa-times-circle mr-1"></i>{{ $error }}</li>
                @endforeach
            </ul>`, confirmButtonColor: '#2563EB',
        });
    @endif
    </script>

</body>
</html>
