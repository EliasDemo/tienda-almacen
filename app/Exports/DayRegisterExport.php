<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\Exportable;

class DayRegisterExport implements WithEvents
{
    use Exportable;

    public function __construct(private Collection $registers, private string $date) {}

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $font = 'Arial';

                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(22);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(26);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(10);
                $sheet->getColumnDimension('G')->setWidth(14);
                $sheet->getColumnDimension('H')->setWidth(14);
                $sheet->getColumnDimension('I')->setWidth(10);

                $row = 1;

                // ── TÍTULO ──
                $sheet->mergeCells("A{$row}:I{$row}");
                $sheet->setCellValue("A{$row}", 'REPORTE DEL DÍA: ' . date('d/m/Y', strtotime($this->date)));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'bold' => true, 'size' => 16, 'color' => ['rgb' => '1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $row = 2;
                $sheet->mergeCells("A{$row}:I{$row}");
                $sheet->setCellValue("A{$row}", 'Sistema de Ventas');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'size' => 10, 'color' => ['rgb' => '6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── RESUMEN GENERAL ──
                $row = 4;
                $totalSales = $this->registers->sum('total_sales');
                $totalCash = $this->registers->sum('total_cash');
                $totalOther = $this->registers->sum('total_other');
                $salesCount = $this->registers->sum(fn($r) => $r->sales->count());

                $sheet->mergeCells("A{$row}:I{$row}");
                $sheet->setCellValue("A{$row}", 'RESUMEN GENERAL DEL DÍA');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $row++;
                $summaryItems = [
                    ['Total de cajas', $this->registers->count(), false],
                    ['Total de ventas', $salesCount, false],
                    ['Monto total vendido', $totalSales, true],
                    ['Total en efectivo', $totalCash, true],
                    ['Total otros medios', $totalOther, true],
                ];

                foreach ($summaryItems as $i => $item) {
                    $sheet->setCellValue("B{$row}", $item[0]);
                    $sheet->setCellValue("D{$row}", $item[1]);
                    $sheet->getStyle("B{$row}")->applyFromArray([
                        'font' => ['name' => $font, 'size' => 10, 'color' => ['rgb' => '6B7280']],
                    ]);
                    $sheet->getStyle("D{$row}")->applyFromArray([
                        'font' => ['name' => $font, 'bold' => true, 'size' => 11],
                    ]);
                    if ($item[2]) {
                        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                    if ($i % 2 === 0) {
                        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FF']],
                        ]);
                    }
                    $row++;
                }

                $sheet->getStyle('A5:I' . ($row - 1))->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                ]);

                $row++;

                // ── CADA CAJA ──
                foreach ($this->registers as $r) {
                    $expCash = (float) $r->opening_amount + (float) $r->total_cash;

                    // Header de caja
                    $sheet->mergeCells("A{$row}:I{$row}");
                    $sheet->setCellValue("A{$row}", $r->user->name . '  |  ' . $r->opened_at->format('H:i') . ' — ' . ($r->closed_at?->format('H:i') ?? '—') . '  |  Ventas: S/ ' . number_format($r->total_sales, 2) . '  |  Diferencia: S/ ' . number_format($r->difference, 2));
                    $diffColor = (float) $r->difference >= 0 ? '16A34A' : 'DC2626';
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['name' => $font, 'bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
                    ]);

                    $row++;

                    // Header tabla
                    $headers = ['#', 'N° Venta', 'Cliente', 'Producto', 'Cantidad', 'Unidad', 'Precio', 'Subtotal', 'Hora'];
                    $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

                    foreach ($headers as $i => $h) {
                        $sheet->setCellValue($cols[$i] . $row, $h);
                    }
                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'font' => ['name' => $font, 'bold' => true, 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);

                    $row++;
                    $num = 1;

                    foreach ($r->sales as $sale) {
                        $isFirst = true;
                        foreach ($sale->items as $item) {
                            $sheet->setCellValue("A{$row}", $num);
                            $sheet->setCellValue("B{$row}", $isFirst ? $sale->sale_number : '');
                            $sheet->setCellValue("C{$row}", $isFirst ? ($sale->customer?->name ?? '—') : '');
                            $sheet->setCellValue("D{$row}", $item->variant->product->name);
                            $sheet->setCellValue("E{$row}", (float) $item->quantity);
                            $sheet->setCellValue("F{$row}", $item->unit);
                            $sheet->setCellValue("G{$row}", (float) $item->unit_price);
                            $sheet->setCellValue("H{$row}", (float) $item->subtotal);
                            $sheet->setCellValue("I{$row}", $isFirst ? $sale->created_at->format('H:i') : '');

                            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.000');
                            $sheet->getStyle("G{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                                'font' => ['name' => $font, 'size' => 9],
                                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E5E7EB']]],
                            ]);

                            if ($num % 2 === 0) {
                                $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                                ]);
                            }

                            $isFirst = false;
                            $num++;
                            $row++;
                        }
                    }

                    $row++;
                }

                // ── PIE ──
                $sheet->setCellValue("A{$row}", 'Generado: ' . now()->format('d/m/Y H:i'));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['name' => $font, 'size' => 9, 'italic' => true, 'color' => ['rgb' => '9CA3AF']],
                ]);

                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->setTitle('Día ' . date('d-m-Y', strtotime($this->date)));
            },
        ];
    }
}
