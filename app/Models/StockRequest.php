<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRequest extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'request_code', 'requested_by', 'cash_register_id',
        'transfer_id', 'request_type', 'customer_id',
        'advance_amount', 'advance_method', 'advance_reference',
        'estimated_total', 'real_total', 'remaining_amount',
        'status', 'label_color', 'sale_id',
        'delivery_date', 'notes', 'customer_notes',
        'requested_at', 'dispatched_at', 'received_at', 'delivered_at',
        'confirmed_at', 'preparing_at', 'ready_at', 'cancelled_at',
        'confirmed_by', 'cancel_reason',
    ];

    protected $casts = [
        'delivery_date'    => 'date',
        'estimated_total'  => 'decimal:2',
        'real_total'       => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'advance_amount'   => 'decimal:2',
        'requested_at'     => 'datetime',
        'dispatched_at'    => 'datetime',
        'received_at'      => 'datetime',
        'delivered_at'     => 'datetime',
        'confirmed_at'     => 'datetime',
        'preparing_at'     => 'datetime',
        'ready_at'         => 'datetime',
        'cancelled_at'     => 'datetime',
    ];

    // ─── Relaciones ─────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockRequestItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StockRequestPayment::class);
    }

    // ─── Cálculos ─────────────────────────────

    public function recalculateRealTotal(): void
    {
        $realTotal = 0;
        foreach ($this->items as $item) {
            $item->recalculateFromPackages();
            $realTotal += (float) $item->real_total;
        }
        $this->real_total = $realTotal;
        $this->remaining_amount = max(0, $realTotal - $this->total_paid);
        $this->save();
    }

    public function recalculateTotals(): void
    {
        $totalPaid = $this->payments()
            ->whereIn('payment_type', ['advance', 'final'])
            ->sum('amount');

        $this->advance_amount = $this->payments()
            ->where('payment_type', 'advance')
            ->sum('amount');

        $baseTotal = (float) $this->real_total > 0 ? (float) $this->real_total : (float) $this->estimated_total;
        $this->remaining_amount = max(0, $baseTotal - (float) $totalPaid);
        $this->save();
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()
            ->whereIn('payment_type', ['advance', 'final'])
            ->sum('amount');
    }

    public function getActiveTotal(): float
    {
        return (float) $this->real_total > 0 ? (float) $this->real_total : (float) $this->estimated_total;
    }

    public function getItemsReceivedAttribute(): int
    {
        return $this->items->filter(fn($i) => $i->quantity_sent > 0)->count();
    }

    public function getItemsPendingAttribute(): int
    {
        return $this->items->filter(fn($i) => $i->quantity_sent <= 0)->count();
    }

    // ─── Alertas y verificaciones ─────────────────

    /**
     * ¿El pedido está atrasado? (pasó la fecha de entrega)
     */
    public function getIsLateAttribute(): bool
    {
        if (in_array($this->status, ['delivered', 'cancelled'])) return false;
        return $this->delivery_date && $this->delivery_date->isPast();
    }

    /**
     * ¿El cargamento fue despachado pero no recibido en tienda?
     */
    public function getIsDispatchedNotReceivedAttribute(): bool
    {
        if (!$this->transfer) return false;
        return $this->transfer->status === 'in_transit' && !$this->received_at;
    }

    /**
     * ¿Cuántas horas lleva en tránsito?
     */
    public function getHoursInTransitAttribute(): ?float
    {
        if (!$this->dispatched_at) return null;
        if ($this->received_at) return null;
        return round($this->dispatched_at->diffInMinutes(now()) / 60, 1);
    }

    /**
     * ¿Lleva mucho tiempo sin avanzar? (más de 24h en el mismo estado)
     */
    public function getIsStuckAttribute(): bool
    {
        if (in_array($this->status, ['delivered', 'cancelled', 'ready'])) return false;

        $lastChange = match ($this->status) {
            'pending'    => $this->requested_at,
            'confirmed'  => $this->confirmed_at,
            'preparing'  => $this->preparing_at,
            'dispatched' => $this->dispatched_at,
            'received'   => $this->received_at,
            default      => $this->updated_at,
        };

        return $lastChange && $lastChange->diffInHours(now()) > 24;
    }

    /**
     * ¿Los bultos del pedido fueron recibidos en tienda?
     */
    public function getOrderPackagesInStoreAttribute(): int
    {
        if (!$this->transfer) return 0;

        return Package::where('for_order', true)
            ->where('location', 'tienda')
            ->whereHas('transferLine', fn($q) => $q->where('transfer_id', $this->transfer_id))
            ->count();
    }

    /**
     * ¿Hay bultos del pedido que se vendieron? (error o confusión)
     */
    public function getOrderPackagesSoldAttribute(): int
    {
        if (!$this->transfer) return 0;

        return Package::where('for_order', true)
            ->whereIn('status', ['sold', 'exhausted'])
            ->whereHas('transferLine', fn($q) => $q->where('transfer_id', $this->transfer_id))
            ->count();
    }

    /**
     * Total de bultos marcados for_order en el cargamento
     */
    public function getOrderPackagesTotalAttribute(): int
    {
        if (!$this->transfer) return 0;

        return Package::where('for_order', true)
            ->whereHas('transferLine', fn($q) => $q->where('transfer_id', $this->transfer_id))
            ->count();
    }

    /**
     * Obtener todas las alertas activas
     */
    public function getAlertsAttribute(): array
    {
        $alerts = [];

        if ($this->is_late) {
            $days = $this->delivery_date->diffInDays(now());
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'fa-exclamation-triangle',
                'message' => "Pedido atrasado {$days} día(s). Fecha de entrega: {$this->delivery_date->format('d/m/Y')}.",
            ];
        }

        if ($this->is_dispatched_not_received) {
            $hours = $this->hours_in_transit;
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'fa-truck',
                'message' => "Cargamento en tránsito hace {$hours}h. Aún no recibido en tienda.",
            ];
        }

        if ($this->order_packages_sold > 0) {
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'fa-exclamation-circle',
                'message' => "{$this->order_packages_sold} bulto(s) del pedido fueron vendidos. Se necesita reponer con producto de tienda.",
            ];
        }

        if ($this->is_stuck) {
            $alerts[] = [
                'type'    => 'info',
                'icon'    => 'fa-clock',
                'message' => "El pedido lleva más de 24h en estado '{$this->status_label}' sin avanzar.",
            ];
        }

        if ($this->status === 'ready' && (float) $this->remaining_amount > 0) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'fa-money-bill',
                'message' => "Listo para entregar. Saldo pendiente: S/ " . number_format($this->remaining_amount, 2),
            ];
        }

        return $alerts;
    }

    // ─── Labels de estado ─────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'Pendiente',
            'confirmed'  => 'Confirmado',
            'preparing'  => 'En preparación',
            'dispatched' => 'Despachado',
            'received'   => 'Recibido en tienda',
            'ready'      => 'Listo para entregar',
            'delivered'  => 'Entregado',
            'cancelled'  => 'Cancelado',
            default      => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'bg-yellow-100 text-yellow-800',
            'confirmed'  => 'bg-blue-100 text-blue-800',
            'preparing'  => 'bg-purple-100 text-purple-800',
            'dispatched' => 'bg-indigo-100 text-indigo-800',
            'received'   => 'bg-teal-100 text-teal-800',
            'ready'      => 'bg-green-100 text-green-800',
            'delivered'  => 'bg-gray-100 text-gray-800',
            'cancelled'  => 'bg-red-100 text-red-800',
            default      => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'fa-clock',
            'confirmed'  => 'fa-check',
            'preparing'  => 'fa-boxes-stacked',
            'dispatched' => 'fa-truck',
            'received'   => 'fa-clipboard-check',
            'ready'      => 'fa-check-double',
            'delivered'  => 'fa-handshake',
            'cancelled'  => 'fa-times-circle',
            default      => 'fa-question',
        };
    }
}