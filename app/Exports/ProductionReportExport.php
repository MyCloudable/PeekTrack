<?php

namespace App\Exports;

use DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProductionReportExport implements FromArray, WithEvents
{
    protected $jobNumber;
    protected $startDate;
    protected $endDate;

    public function __construct($jobNumber, $startDate = null, $endDate = null)
    {
        $this->jobNumber = $jobNumber;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function array(): array
    {
        $rows = DB::table('production')
            ->join('jobentries', 'production.link', '=', 'jobentries.link')
            ->leftJoin('job_notes', function ($join) {
                $join->on('production.link', '=', 'job_notes.link')
                    ->where('job_notes.note_type', 'JobCardNote')
					->where('job_notes.note','!=', 'Jobcard approved.')
					->where('job_notes.note','!=', 'Jobcard submitted.')
					->where('job_notes.note','!=', 'Jobcard reopened.');
            })
            ->select(
                'production.phase',
                DB::raw('MAX(jobentries.name) as name'),
                'production.description',
                'production.unit_of_measure',
                'production.road_name',
                'jobentries.workdate',
                DB::raw('SUM(production.qty) as qty'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT("[", jobentries.name, "] - ", job_notes.note) SEPARATOR " | ") as note')
            )
            ->where('jobentries.job_number', $this->jobNumber)
            ->when($this->startDate, function ($query) {
                $query->whereDate('jobentries.workdate', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($query) {
                $query->whereDate('jobentries.workdate', '<=', $this->endDate);
            })
            ->groupBy(
                'production.phase',
                'production.description',
                'production.unit_of_measure',
                'production.road_name',
                'jobentries.workdate'
            )
            ->orderBy('jobentries.workdate')
            ->get();

        if ($rows->count() == 0) {
            return [
                ['PEEK PAVEMENT MARKING, LLC'],
                ['No production data found for job ' . $this->jobNumber]
            ];
        }

        $phases = $rows->pluck('phase')->filter()->unique()->sort()->values();

        $phaseDescriptions = [];
        $phaseUnits = [];

        foreach ($rows as $row) {
            $phaseDescriptions[$row->phase] = $row->description;
            $phaseUnits[$row->phase] = $row->unit_of_measure;
        }

        $data = [];

        foreach ($rows as $row) {
            $key = $row->workdate . '_' . $row->road_name;

            if (!isset($data[$key])) {
                $data[$key] = [
                    'date' => Carbon::parse($row->workdate)->format('n/j/Y'),
                    'road' => $row->road_name,
                    'notes' => ''
                ];

                foreach ($phases as $phase) {
                    $data[$key][$phase] = 0;
                }
            }

            if (!empty($row->phase)) {
                $data[$key][$row->phase] += (float) $row->qty;
            }

            if (!empty($row->note)) {
                $existingNotes = array_filter(array_map('trim', explode('|', $data[$key]['notes'])));
                $newNote = trim($row->note);

                if (!in_array($newNote, $existingNotes)) {
                    if ($data[$key]['notes'] !== '') {
                        $data[$key]['notes'] .= ' | ';
                    }
                    $data[$key]['notes'] .= $newNote;
                }
            }
        }

        $jobInfo = DB::table('jobs')
            ->select(
                'contractor',
                'description as project',
                'county'
            )
            ->where('job_number', $this->jobNumber)
            ->first();

        $sheet = [];

        $sheet[] = ['PEEK PAVEMENT MARKING, LLC'];
        $sheet[] = [];
        $sheet[] = ['Contractor:', $jobInfo->contractor ?? ''];
        $sheet[] = ['Peek No:', $this->jobNumber];
        $sheet[] = ['Project', $jobInfo->project ?? ''];
        $sheet[] = ['County:', $jobInfo->county ?? ''];
        $sheet[] = [];

        $phaseRow = ['', ''];
        foreach ($phases as $phase) {
            $phaseRow[] = $phase;
        }
        $sheet[] = $phaseRow;

        $descRow = ['', ''];
        foreach ($phases as $phase) {
            $descRow[] = $phaseDescriptions[$phase];
        }
        $sheet[] = $descRow;

        $unitRow = ['', ''];
        foreach ($phases as $phase) {
            $unitRow[] = $phaseUnits[$phase];
        }
        $sheet[] = $unitRow;

        $dateHeader = ['DATE', 'ROAD NAME'];
        foreach ($phases as $phase) {
            $dateHeader[] = '';
        }
        $dateHeader[] = 'NOTES';
        $sheet[] = $dateHeader;

        foreach ($data as $row) {
            $line = [
                $row['date'],
                $row['road']
            ];

            foreach ($phases as $phase) {
                $qty = $row[$phase];
                $line[] = $qty == 0 ? '' : number_format($qty, 3, '.', ',');
            }

            $line[] = $row['notes'] ?? '';
            $sheet[] = $line;
        }

        $totals = ['', 'TOTAL QTY'];

        foreach ($phases as $phase) {
            $total = $rows->where('phase', $phase)->sum('qty');
            $totals[] = number_format($total, 3, '.', ',');
        }

        $totals[] = '';

        $sheet[] = [];
        $sheet[] = $totals;

        return $sheet;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A10');

                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $notesColumnIndex = Coordinate::columnIndexFromString($highestColumn);
                $lastPhaseColumnIndex = $notesColumnIndex - 1;
                $lastPhaseColumnLetter = Coordinate::stringFromColumnIndex($lastPhaseColumnIndex);
				/*
Right align numeric data columns (quantities)
*/
$sheet->getStyle('C10:' . $lastPhaseColumnLetter . $highestRow)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                for ($col = 3; $col <= $lastPhaseColumnIndex; $col++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }

                $sheet->getColumnDimension($highestColumn)->setWidth(60);
                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(35);

                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getAlignment()
                    ->setWrapText(true);

                $sheet->getStyle('C7:' . $lastPhaseColumnLetter . '7')
                    ->getAlignment()
                    ->setWrapText(false);

                $sheet->mergeCells('A1:' . $highestColumn . '1');

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                $sheet->getStyle('A6:' . $highestColumn . '9')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'D7E8EE'
                        ]
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN
                        ]
                    ]
                ]);

                $sheet->getStyle('C7:' . $lastPhaseColumnLetter . '9')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A8:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle('C10:' . $lastPhaseColumnLetter . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.000');

                $sheet->getStyle('A' . $highestRow . ':' . $highestColumn . $highestRow)
                    ->getFont()
                    ->setBold(true);
            }
        ];
    }
}