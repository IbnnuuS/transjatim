<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanHarianExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $rows;
    protected array $filters;

    public function __construct($rows, array $filters = [])
    {
        $this->rows = $rows instanceof Collection ? $rows : collect($rows);
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Jam',
            'Judul',
            'Nama',
            'Divisi',
            'PIC',
            'Progress',
            'Status',
        ];
    }

    public function map($t): array
    {
        $tgl  = optional($t->schedule_date)->format('d/m/Y') ?? '—';
        $time = (($t->start_time ?? null) && ($t->end_time ?? null))
            ? ($t->start_time.' - '.$t->end_time)
            : ($t->start_time ?? '—');

        $judul = $t->judul ?? '-';
        $nama  = $t->jobdesk?->user?->name ?? '—';
        $div   = $t->jobdesk?->division ?? ($t->jobdesk?->user?->division ?? '—');
        $pic   = $t->pic ?? '—';
        $prog  = (int)($t->progress ?? 0) . '%';
        $st    = $t->status ?? '-';

        return [
            $tgl,
            $time,
            $judul,
            $nama,
            $div,
            $pic,
            $prog,
            ucwords(str_replace('_', ' ', $st)),
        ];
    }

    public function title(): string
    {
        $from = $this->filters['date_from'] ?? null;
        $to   = $this->filters['date_to'] ?? null;

        if ($from && $to) {
            return "Harian {$from} s.d {$to}";
        }
        if ($from) {
            return "Harian sejak {$from}";
        }
        if ($to) {
            return "Harian s.d {$to}";
        }
        return 'Laporan Harian';
    }
}
