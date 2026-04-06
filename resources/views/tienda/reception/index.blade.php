@extends('layouts.app')

@section('title', 'Recepción de Mercadería')

@section('content')
<div x-data="quickScanner()" class="max-w-5xl mx-auto">

    {{-- Escaneo rápido --}}
    <div class="bg-green-50 border-2 border-green-300 rounded-lg shadow p-4 mb-6">
        <div class="flex justify-between items-center mb-2">
            <h4 class="text-sm font-semibold text-green-800">Escaneo Rápido — Recibir bulto al instante</h4>
            <button @click="toggleCamera()"
                    class="flex items-center gap-1 text-sm px-3 py-1.5 rounded-lg"
                    :class="cameraActive ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'">
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
            <div id="qr-reader-quick" class="mx-auto rounded-lg overflow-hidden" style="max-width: 400px;"></div>
            <p class="text-xs text-center text-gray-500 mt-2">Apunta la cámara al código QR del bulto</p>
        </div>

        {{-- Campo manual / pistola --}}
        <div class="flex gap-3">
            <div class="flex-1">
                <input type="text" x-model="scanInput"
                       @keydown.enter.prevent="quickScan()"
                       x-ref="scanInput"
                       class="w-full border-green-400 rounded-lg shadow-sm text-lg text-center focus:ring-green-500 focus:border-green-500 h-12"
                       placeholder="Escanea con pistola o cámara cualquier bulto..."
                       autofocus>
            </div>
            <button @click="quickScan()"
                    :disabled="!scanInput || scanning"
                    class="bg-green-600 text-white px-8 h-12 rounded-lg hover:bg-green-700 font-bold disabled:opacity-50">
                RECIBIR
            </button>
        </div>

        {{-- Mensaje --}}
        <div x-show="message" x-transition
             :class="messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'"
             class="mt-3 px-4 py-3 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-medium" x-text="message"></p>
                    <template x-if="lastReceived">
                        <div class="mt-1 text-xs opacity-80">
                            <span x-text="lastReceived.product"></span> —
                            <span x-text="lastReceived.quantity + ' ' + lastReceived.unit"></span> —
                            Cargamento: <span x-text="lastReceived.transfer"></span>
                        </div>
                    </template>
                </div>
                <button @click="message = ''" class="text-lg font-bold opacity-50 hover:opacity-100">&times;</button>
            </div>
        </div>

        {{-- Últimos recibidos --}}
        <div x-show="recentScans.length > 0" class="mt-3">
            <p class="text-xs text-green-700 font-medium mb-1">Últimos recibidos (<span x-text="recentScans.length"></span>):</p>
            <div class="space-y-1">
                <template x-for="(scan, idx) in recentScans" :key="idx">
                    <div class="flex justify-between items-center bg-white rounded px-3 py-1.5 text-xs">
                        <div>
                            <span class="font-medium" x-text="scan.product"></span>
                            <span class="text-gray-400 mx-1">—</span>
                            <span class="font-bold" x-text="scan.quantity + ' ' + scan.unit"></span>
                        </div>
                        <div class="text-gray-400">
                            <span x-text="scan.transfer"></span>
                            <span class="mx-1">—</span>
                            <span x-text="scan.time"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Lista de cargamentos pendientes --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">Cargamentos por Recibir</h3>
            <p class="text-sm text-gray-500 mt-1">O selecciona un cargamento para recepción detallada.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-6 py-3 text-gray-600 font-medium">Código</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Productos</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Bultos</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Recibidos</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Despachado por</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Fecha</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($transfers as $transfer)
                    @php
                        $totalPkgs = $transfer->lines->sum('total_packages');
                        $receivedPkgs = $transfer->lines->sum('received_packages');
                        $transitSold = $transfer->lines->sum('transit_sold_packages');
                        $pending = $totalPkgs - $receivedPkgs - $transitSold;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-mono text-xs font-medium">{{ $transfer->transfer_code }}</td>
                        <td class="px-6 py-4">
                            @foreach($transfer->lines as $line)
                                <div class="text-xs">
                                    <span class="font-medium">{{ $line->variant->product->name }}</span>
                                    <span class="text-gray-400">({{ $line->total_packages }})</span>
                                </div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 font-medium">{{ $totalPkgs }}</td>
                        <td class="px-6 py-4">
                            <span class="text-green-600 font-medium">{{ $receivedPkgs }}</span>
                            @if($transitSold > 0)
                                <span class="text-orange-500 text-xs ml-1">({{ $transitSold }} tránsito)</span>
                            @endif
                            @if($pending > 0)
                                <span class="text-red-500 text-xs ml-1">({{ $pending }} pendientes)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs">{{ $transfer->dispatcher->name }}</td>
                        <td class="px-6 py-4 text-xs">{{ $transfer->dispatched_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('tienda.reception.show', $transfer) }}"
                               class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 text-xs">
                                Detalle
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                            No hay cargamentos pendientes de recepción.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<script>
function quickScanner() {
    let html5QrCode = null;

    return {
        scanInput: '',
        scanning: false,
        message: '',
        messageType: 'success',
        lastReceived: null,
        recentScans: [],
        cameraActive: false,

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
                html5QrCode = new Html5Qrcode("qr-reader-quick");

                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        this.scanInput = decodedText;
                        this.stopCamera();
                        this.quickScan();
                    },
                    () => {}
                ).catch(() => {
                    this.message = 'No se pudo acceder a la cámara.';
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

        async quickScan() {
            if (!this.scanInput.trim() || this.scanning) return;

            this.scanning = true;
            this.message = '';
            this.lastReceived = null;

            try {
                const res = await fetch('{{ route("tienda.reception.quick-scan") }}', {
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
                    this.message = data.message;
                    this.messageType = 'success';
                    this.lastReceived = data.package;

                    // Agregar a recientes
                    const now = new Date();
                    this.recentScans.unshift({
                        product: data.package.product,
                        quantity: data.package.quantity,
                        unit: data.package.unit,
                        transfer: data.package.transfer,
                        time: now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0'),
                    });

                    // Máximo 20 recientes
                    if (this.recentScans.length > 20) this.recentScans.pop();

                    // Sonido de éxito (opcional)
                    try { new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH2JkZqTjX51aF1XT09RW2V0hJGdp6igl5CLgXlwa2ZjYGBhZWtze4OLkpaYmJWRjImFgX15d3V0c3N0dnh7f4KFh4mKioqJiIeGhYSDgoGAgH9/f39/f4CAgYKDhIWGh4iIiYmJiYmJiImIiIeHh4aGhYWEhIOCgoGBgIB/f39+fn5+fn5+fn9/f4CAgIGBgoKDg4SEhISFhYWFhYWFhYWFhISDg4ODgoKCgYGBgICAgH9/f39/f39/f39/f4CAgICBgYGBgoKCgoKDg4ODg4ODg4ODg4KCgoKCgoGBgYGBgYCAgICAgIB/f39/f39/f39/f4CAgICAgYGBgYGBgYKCgoKCgoKCgoKCgoKCgoKCgYGBgYGBgYGBgICAgICAgICAgICAf3+AgICAgICBgYGBgQ==').play(); } catch(e) {}
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = 'Error de conexión.';
                this.messageType = 'error';
            }

            this.scanInput = '';
            this.scanning = false;
            this.$refs.scanInput.focus();
        },
    }
}
</script>
@endsection
