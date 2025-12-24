<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LaporanBulananExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Collection $rows;
    protected string $bulan; // YYYY-MM

    public function __construct(Collection $rows, string $bulan)
    {
        $this->rows  = $rows;
        $this->bulan = $bulan;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Judul',
            'Nama',
            'Divisi',
            'Progress (%)',
            'Status',
        ];
    }

    public function map($t): array
    {
        $tgl     = optional($t->schedule_date)->format('d/m/Y') ?? '';
        $judul   = $t->judul ?? '';
        $nama    = $t->jobdesk->user->name ?? '';
        $div     = $t->jobdesk->division ?? '';
        $prog    = (int)($t->progress ?? 0);
        $status  = $t->status ?? '';

        return [$tgl, $judul, $nama, $div, $prog, $status];
    }
}
