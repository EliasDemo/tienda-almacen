<?php

namespace App\Exports;

use App\Models\CashRegister;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\Exportable;

class CashRegisterExport implements WithEvents
{
    use Exportable;

    public function __construct(private CashRegister $register) {}

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $r = $this->register;
                $expectedCash = (float) $r->opening_amount + (float) $r->total_cash;

                // ── Anchos de columna ──
                $sheet->getColumnDimension('A')->setWidth(4);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(28);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(10);
                $sheet->getColumnDimension('G')->setWidth(14);
                $sheet->getColumnDimension('H')->setWidth(14);
                $sheet->getColumnDimension('I')->setWidth(22);
                $sheet->getColumnDimension('J')->setWidth(10);

                $font = 'Arial';
                $row = 1;

                // ── ENCABEZADO ──
                $sheet->mergeCells("B{$row}:J{$row}");
                $sheet->setCellValue("B{$row}", 'REPORTE DE CAJA');
                $sheet->getStyle("B{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'bold' => true, 'size' => 16, 'color' => ['rgb' => '1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $row = 2;
                $sheet->mergeCells("B{$row}:J{$row}");
                $sheet->setCellValue("B{$row}", 'Sistema de Ventas');
                $sheet->getStyle("B{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'size' => 10, 'color' => ['rgb' => '6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── INFO CAJERO ──
                $row = 4;
                $infoStyle = ['font' => ['name' => $font, 'size' => 10]];
                $labelStyle = ['font' => ['name' => $font, 'size' => 10, 'color' => ['rgb' => '6B7280']]];
                $valueStyle = ['font' => ['name' => $font, 'size' => 11, 'bold' => true]];

                $infoBg = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]];

                $sheet->getStyle("B{$row}:E" . ($row + 3))->applyFromArray($infoBg);
                $sheet->getStyle("B{$row}:E" . ($row + 3))->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                ]);

                $sheet->setCellValue("B{$row}", 'Cajero:');
                $sheet->setCellValue("C{$row}", $r->user->name);
                $sheet->setCellValue("D{$row}", 'Fecha:');
                $sheet->setCellValue("E{$row}", $r->opened_at->format('d/m/Y'));
                $sheet->getStyle("B{$row}")->applyFromArray($labelStyle);
                $sheet->getStyle("D{$row}")->applyFromArray($labelStyle);
                $sheet->getStyle("C{$row}")->applyFromArray($valueStyle);
                $sheet->getStyle("E{$row}")->applyFromArray($valueStyle);

                $row++;
                $sheet->setCellValue("B{$row}", 'Apertura:');
                $sheet->setCellValue("C{$row}", $r->opened_at->format('H:i'));
                $sheet->setCellValue("D{$row}", 'Cierre:');
                $sheet->setCellValue("E{$row}", $r->closed_at?->format('H:i') ?? '—');
                $sheet->getStyle("B{$row}")->applyFromArray($labelStyle);
                $sheet->getStyle("D{$row}")->applyFromArray($labelStyle);
                $sheet->getStyle("C{$row}")->applyFromArray($valueStyle);
                $sheet->getStyle("E{$row}")->applyFromArray($valueStyle);

                // ── RESUMEN FINANCIERO ──
                $row = 8;
                $sheet->mergeCells("B{$row}:E{$row}");
                $sheet->setCellValue("B{$row}", 'RESUMEN FINANCIERO');
                $sheet->getStyle("B{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $summaryData = [
                    ['Fondo inicial', (float) $r->opening_amount],
                    ['Total ventas', (float) $r->total_sales],
                    ['Efectivo neto de ventas', (float) $r->total_cash],
                    ['Otros medios de pago', (float) $r->total_other],
                    ['Efectivo esperado en caja', $expectedCash],
                    ['Efectivo contado', (float) $r->closing_amount],
                    ['DIFERENCIA', (float) $r->difference],
                ];

                $row = 9;
                foreach ($summaryData as $i => $item) {
                    $sheet->setCellValue("B{$row}", $item[0]);
                    $sheet->setCellValue("E{$row}", $item[1]);
                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

                    $sheet->getStyle("B{$row}")->applyFromArray($labelStyle);
                    $sheet->getStyle("E{$row}")->applyFromArray(['font' => ['name' => $font, 'bold' => true, 'size' => 11]]);

                    if ($i % 2 === 0) {
                        $sheet->getStyle("B{$row}:E{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                        ]);
                    }

                    // Última fila (diferencia) con formato especial
                    if ($i === count($summaryData) - 1) {
                        $sheet->getStyle("B{$row}:E{$row}")->applyFromArray([
                            'font' => ['name' => $font, 'bold' => true, 'size' => 12],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '1F2937']]],
                        ]);

                        $color = (float) $r->difference >= 0 ? '16A34A' : 'DC2626';
                        $sheet->getStyle("E{$row}")->applyFromArray([
                            'font' => ['name' => $font, 'bold' => true, 'size' => 13, 'color' => ['rgb' => $color]],
                        ]);

                        $status = (float) $r->difference > 0 ? ' (sobrante)' : ((float) $r->difference < 0 ? ' (faltante)' : ' (cuadra)');
                        $sheet->setCellValue("B{$row}", 'DIFERENCIA' . $status);
                    }

                    $row++;
                }

                // ── BORDE RESUMEN ──
                $sheet->getStyle('B9:E' . ($row - 1))->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                ]);

                // ── DETALLE DE VENTAS ──
                $row += 1;
                $sheet->mergeCells("B{$row}:J{$row}");
                $sheet->setCellValue("B{$row}", 'DETALLE DE VENTAS (' . $r->sales->count() . ')');
                $sheet->getStyle("B{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Cabecera tabla
                $row++;
                $headers = ['#', 'N° Venta', 'Cliente', 'Producto', 'Cantidad', 'Unidad', 'Precio Unit.', 'Subtotal', 'Método Pago', 'Hora'];
                $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

                foreach ($headers as $i => $header) {
                    $sheet->setCellValue($cols[$i] . $row, $header);
                }

                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '9CA3AF']],
                    ],
                ]);

                // Filas de ventas
                $row++;
                $num = 1;
                $startDataRow = $row;

                foreach ($r->sales as $sale) {
                    $isFirstItem = true;
                    $payMethods = $sale->payments->map(fn($p) => ucfirst($p->method) . ': S/ ' . number_format($p->amount, 2))->join(', ');

                    foreach ($sale->items as $item) {
                        $sheet->setCellValue("A{$row}", $num);
                        $sheet->setCellValue("B{$row}", $isFirstItem ? $sale->sale_number : '');
                        $sheet->setCellValue("C{$row}", $isFirstItem ? ($sale->customer?->name ?? '—') : '');
                        $sheet->setCellValue("D{$row}", $item->variant->product->name . ' - ' . $item->variant->name);
                        $sheet->setCellValue("E{$row}", (float) $item->quantity);
                        $sheet->setCellValue("F{$row}", $item->unit);
                        $sheet->setCellValue("G{$row}", (float) $item->unit_price);
                        $sheet->setCellValue("H{$row}", (float) $item->subtotal);
                        $sheet->setCellValue("I{$row}", $isFirstItem ? $payMethods : '');
                        $sheet->setCellValue("J{$row}", $isFirstItem ? $sale->created_at->format('H:i') : '');

                        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.000');
                        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // Alternar colores de fila
                        if ($num % 2 === 0) {
                            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                            ]);
                        }

                        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                            'font' => ['name' => $font, 'size' => 10],
                            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E5E7EB']]],
                        ]);

                        $isFirstItem = false;
                        $num++;
                        $row++;
                    }
                }

                // Fila total
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->setCellValue("A{$row}", 'TOTAL VENTAS');
                $sheet->setCellValue("H{$row}", '=SUM(H' . $startDataRow . ':H' . ($row - 1) . ')');
                $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2563EB']]],
                ]);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Borde exterior de toda la tabla
                $sheet->getStyle('A' . ($startDataRow - 1) . ':J' . $row)->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
                ]);

                // ── PIE ──
                $row += 2;
                $sheet->setCellValue("B{$row}", 'Generado: ' . now()->format('d/m/Y H:i'));
                $sheet->getStyle("B{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'size' => 9, 'italic' => true, 'color' => ['rgb' => '9CA3AF']],
                ]);

                // Configurar impresión
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->setTitle('Caja ' . $r->opened_at->format('d-m-Y'));
            },
        ];
    }
}