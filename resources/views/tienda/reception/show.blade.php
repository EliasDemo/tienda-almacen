@extends('layouts.app')

@section('title', 'Recepción ' . $transfer->transfer_code)

@section('content')
<div x-data="receptionStation()" class="max-w-5xl mx-auto">

    {{-- Cabecera --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold">Recepción: {{ $transfer->transfer_code }}</h3>
            <p class="text-xs text-gray-500">Despachado por {{ $transfer->dispatcher->name }} — {{ $transfer->dispatched_at?->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('tienda.reception.index') }}" class="text-sm text-blue-600 hover:underline">← Volver</a>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500">Total</p>
            <p class="text-2xl font-bold" x-text="stats.total"></p>
        </div>
        <div class="bg-green-50 rounded-lg shadow p-4 text-center">
            <p class="text-xs text-green-600">Recibidos</p>
            <p class="text-2xl font-bold text-green-700" x-text="stats.received"></p>
        </div>
        <div class="bg-orange-50 rounded-lg shadow p-4 text-center">
            <p class="text-xs text-orange-600">Tránsito</p>
            <p class="text-2xl font-bold text-orange-700" x-text="stats.transit"></p>
        </div>
        <div class="bg-red-50 rounded-lg shadow p-4 text-center">
            <p class="text-xs text-red-600">Pendientes</p>
            <p class="text-2xl font-bold text-red-700" x-text="stats.pending"></p>
        </div>
    </div>

    {{-- Escáner --}}
    <div class="bg-blue-50 border-2 border-blue-300 rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-center mb-2">
            <h4 class="text-sm font-semibold text-blue-800">Escanear Bulto</h4>
            <button @click="toggleCamera()"
                    class="flex items-center gap-1 text-sm px-3 py-1.5 rounded-lg"
                    :class="cameraActive ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-blue-100 text-blue-700 hover:bg-blue-200'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span x-text="cameraActive ? 'Cerrar Cámara' : 'Usar Cámara'"></span>
            </button>
        </div>

        {{-- Cámara --}}
        <div x-show="cameraActive" x-transition class="mb-3">
            <div id="qr-reader" class="mx-auto rounded-lg overflow-hidden" style="max-width: 400px;"></div>
            <p class="text-xs text-center text-gray-500 mt-2">Apunta la cámara al código QR del bulto</p>
        </div>

        {{-- Campo manual / pistola --}}
        <div class="flex gap-3">
            <div class="flex-1">
                <input type="text" x-model="scanInput"
                       @keydown.enter.prevent="scanPackage()"
                       x-ref="scanInput"
                       class="w-full border-blue-400 rounded-lg shadow-sm text-lg text-center focus:ring-blue-500 focus:border-blue-500 h-12"
                       placeholder="Escanea con pistola o ingresa UUID..."
                       autofocus>
            </div>
            <button @click="scanPackage()"
                    :disabled="!scanInput || scanning"
                    class="bg-blue-600 text-white px-6 h-12 rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50">
                Buscar
            </button>
        </div>

        {{-- Resultado del escaneo --}}
        <div x-show="scannedPackage" x-transition class="mt-4 p-4 bg-white rounded-lg border">
            <template x-if="scannedPackage">
                <div>
                    <div x-show="scannedPackage.already_processed" class="text-center py-2">
                        <p class="text-orange-600 font-medium">Este bulto ya fue procesado.</p>
                        <p class="text-xs text-gray-500" x-text="'Estado: ' + scannedPackage.status + ' | Ubicación: ' + scannedPackage.location"></p>
                    </div>

                    <div x-show="!scannedPackage.already_processed">
                        <div class="grid grid-cols-5 gap-4 mb-3">
                            <div>
                                <p class="text-xs text-gray-500">Producto</p>
                                <p class="font-medium text-sm" x-text="scannedPackage.product"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Lote</p>
                                <p class="font-mono text-xs" x-text="scannedPackage.lot_code"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Tipo</p>
                                <p class="text-sm capitalize" x-text="scannedPackage.package_type"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Peso / Cant.</p>
                                <p class="font-bold text-lg" x-text="scannedPackage.quantity + ' ' + scannedPackage.unit"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">UUID</p>
                                <p class="font-mono text-xs" x-text="scannedPackage.uuid.substring(0, 12) + '...'"></p>
                            </div>
                        </div>

                        <div class="flex gap-3 justify-center">
                            <button @click="confirmReceive()"
                                    :disabled="processing"
                                    class="bg-green-600 text-white px-8 py-2 rounded-lg hover:bg-green-700 font-medium">
                                Recibido en Tienda
                            </button>
                            <button @click="confirmTransitSale()"
                                    :disabled="processing"
                                    class="bg-orange-500 text-white px-8 py-2 rounded-lg hover:bg-orange-600 font-medium">
                                Vendido en Camino
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Mensaje --}}
        <div x-show="message" x-transition
             :class="messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
             class="mt-3 px-3 py-2 rounded text-sm">
            <span x-text="message"></span>
        </div>
    </div>

    {{-- Lista de bultos del cargamento --}}
    @foreach($transfer->lines as $line)
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="p-4 border-b">
            <h4 class="font-semibold">
                {{ $line->variant->product->name }} — {{ $line->variant->name }}
            </h4>
            <p class="text-xs text-gray-500">
                {{ $line->total_packages }} bultos — Merma: {{ number_format($line->merma_kg, 3) }} kg
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">#</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">UUID</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Lote</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Tipo</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Peso / Cant.</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($line->packages as $i => $pkg)
                    <tr class="hover:bg-gray-50 {{ $pkg->location === 'tienda' ? 'bg-green-50' : '' }} {{ $pkg->status === 'sold_in_transit' ? 'bg-orange-50' : '' }}"
                        id="pkg-row-{{ $pkg->id }}">
                        <td class="px-4 py-2 text-xs">{{ $i + 1 }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ Str::limit($pkg->uuid, 10) }}</td>
                        <td class="px-4 py-2 text-xs">{{ Str::limit($pkg->lot->lot_code, 15) }}</td>
                        <td class="px-4 py-2 text-xs capitalize">{{ $pkg->package_type }}</td>
                        <td class="px-4 py-2 text-xs font-medium">
                            @if($pkg->gross_weight)
                                {{ number_format($pkg->gross_weight, 3) }} kg
                            @else
                                {{ $pkg->unit_count }} und
                            @endif
                        </td>
                        <td class="px-4 py-2" id="pkg-status-{{ $pkg->id }}">
                            @if($pkg->location === 'tienda')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Recibido</span>
                            @elseif($pkg->status === 'sold_in_transit')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">Vendido en tránsito</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    {{-- Botón finalizar --}}
    <div class="bg-white rounded-lg shadow p-4 flex justify-between items-center" x-show="stats.pending === 0 && stats.total > 0">
        <p class="text-sm text-green-700 font-medium">Todos los bultos fueron procesados.</p>
        <form method="POST" action="{{ route('tienda.reception.finish', $transfer) }}">
            @csrf
            <button type="submit"
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700"
                    onclick="return confirm('¿Finalizar recepción de este cargamento?')">
                Finalizar Recepción
            </button>
        </form>
    </div>
</div>

{{-- Librería QR Scanner --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<script>
function receptionStation() {
    const initialPackages = @json($initialPackages);
    let html5QrCode = null;

    return {
        scanInput: '',
        scanning: false,
        processing: false,
        scannedPackage: null,
        message: '',
        messageType: 'success',
        packages: initialPackages,
        cameraActive: false,

        get stats() {
            const total = this.packages.length;
            const received = this.packages.filter(p => p.location === 'tienda').length;
            const transit = this.packages.filter(p => p.status === 'sold_in_transit').length;
            const pending = total - received - transit;
            return { total, received, transit, pending };
        },

        toggleCamera() {
            if (this.cameraActive) {
                this.stopCamera();
            } else {
                this.startCamera();
            }
        },

        startCamera() {
            this.cameraActive = true;

            this.$nextTick(() => {
                html5QrCode = new Html5Qrcode("qr-reader");

                html5QrCode.start(
                    { facingMode: "environment" },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                    },
                    (decodedText) => {
                        // QR leído exitosamente
                        this.scanInput = decodedText;
                        this.stopCamera();
                        this.scanPackage();
                    },
                    (errorMessage) => {
                        // Ignorar errores de lectura continua
                    }
                ).catch((err) => {
                    this.message = 'No se pudo acceder a la cámara. Verifica los permisos.';
                    this.messageType = 'error';
                    this.cameraActive = false;
                });
            });
        },

        stopCamera() {
            this.cameraActive = false;
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().catch(() => {});
            }
        },

        async scanPackage() {
            if (!this.scanInput.trim() || this.scanning) return;

            this.scanning = true;
            this.scannedPackage = null;
            this.message = '';

            try {
                const res = await fetch('{{ route("tienda.reception.scan", $transfer) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ uuid: this.scanInput.trim() }),
                });

                const data = await res.json();

                if (data.success) {
                    this.scannedPackage = data.package;
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = 'Error de conexión.';
                this.messageType = 'error';
            }

            this.scanning = false;
        },

        async confirmReceive() {
            if (!this.scannedPackage || this.processing) return;
            this.processing = true;

            try {
                const res = await fetch('/tienda/reception/{{ $transfer->id }}/receive/' + this.scannedPackage.id, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    const pkg = this.packages.find(p => p.id === this.scannedPackage.id);
                    if (pkg) {
                        pkg.location = 'tienda';
                        pkg.status = 'closed';
                    }

                    const statusEl = document.getElementById('pkg-status-' + this.scannedPackage.id);
                    if (statusEl) {
                        statusEl.innerHTML = '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Recibido</span>';
                    }
                    const rowEl = document.getElementById('pkg-row-' + this.scannedPackage.id);
                    if (rowEl) {
                        rowEl.classList.remove('bg-orange-50');
                        rowEl.classList.add('bg-green-50');
                    }

                    this.message = 'Bulto recibido correctamente.';
                    this.messageType = 'success';
                    this.scannedPackage = null;
                    this.scanInput = '';
                    this.$refs.scanInput.focus();

                    setTimeout(() => { this.message = ''; }, 3000);
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = 'Error al recibir.';
                this.messageType = 'error';
            }

            this.processing = false;
        },

        async confirmTransitSale() {
            if (!this.scannedPackage || this.processing) return;
            if (!confirm('¿Marcar como vendido en camino? El gerente deberá validar esto.')) return;
            this.processing = true;

            try {
                const res = await fetch('/tienda/reception/{{ $transfer->id }}/transit-sale/' + this.scannedPackage.id, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    const pkg = this.packages.find(p => p.id === this.scannedPackage.id);
                    if (pkg) {
                        pkg.status = 'sold_in_transit';
                    }

                    const statusEl = document.getElementById('pkg-status-' + this.scannedPackage.id);
                    if (statusEl) {
                        statusEl.innerHTML = '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">Vendido en tránsito</span>';
                    }
                    const rowEl = document.getElementById('pkg-row-' + this.scannedPackage.id);
                    if (rowEl) {
                        rowEl.classList.remove('bg-green-50');
                        rowEl.classList.add('bg-orange-50');
                    }

                    this.message = 'Bulto marcado como vendido en tránsito.';
                    this.messageType = 'success';
                    this.scannedPackage = null;
                    this.scanInput = '';
                    this.$refs.scanInput.focus();

                    setTimeout(() => { this.message = ''; }, 3000);
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = 'Error al procesar.';
                this.messageType = 'error';
            }

            this.processing = false;
        },
    }
}
</script>
@endsection
