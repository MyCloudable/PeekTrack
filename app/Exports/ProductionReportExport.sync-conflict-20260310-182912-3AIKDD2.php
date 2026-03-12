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

class ProductionReportExport implements FromArray, WithEvents
{
    protected $jobNumber;

    public function __construct($jobNumber)
    {
        $this->jobNumber = $jobNumber;
    }

    public function array(): array
    {

        $rows = DB::table('production')
    ->join('jobentries','production.link','=','jobentries.link')

    ->leftJoin('job_notes', function($join){
        $join->on('production.link','=','job_notes.link')
             ->where('job_notes.note_type','JobCardNote');
    })

    ->select(
        'production.phase',
        'production.description',
        'production.unit_of_measure',
        'production.road_name',
        'jobentries.workdate',
        DB::raw('SUM(production.qty) as qty'),
        DB::raw('GROUP_CONCAT(DISTINCT job_notes.note SEPARATOR "\n") as note')
    )

    ->where('jobentries.job_number',$this->jobNumber)

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
                ['No production data found for job '.$this->jobNumber]
            ];
        }

        /*
        |----------------------------------------
        | Build dynamic columns
        |----------------------------------------
        */

        $phases = $rows->pluck('phase')->filter()->unique()->values();

        $phaseDescriptions = [];
        $phaseUnits = [];

        foreach ($rows as $row) {
            $phaseDescriptions[$row->phase] = $row->description;
            $phaseUnits[$row->phase] = $row->unit_of_measure;
        }

        /*
        |----------------------------------------
        | Pivot rows by date + road
        |----------------------------------------
        */

        $data = [];

        foreach ($rows as $row) {

    $key = $row->workdate.'_'.$row->road_name;

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

/*
Combine quantities if same phase appears again
*/
$data[$key][$row->phase] += (float)$row->qty;

/*
Combine notes without duplicates
*/
if(!empty($row->note)){
    $notes = explode("\n",$data[$key]['notes']);

    if(!in_array($row->note,$notes)){
        $data[$key]['notes'] .= ($data[$key]['notes'] ? "\n" : '') . $row->note;
    }
}

    if(!empty($row->note)){
        $data[$key]['notes'] .= ($data[$key]['notes'] ? "\n" : '') . $row->note;
    }
}

        /*
        |----------------------------------------
        | Build Sheet
        |----------------------------------------
        */

        $sheet = [];

        $sheet[] = ['PEEK PAVEMENT MARKING, LLC'];
        $sheet[] = [];

        $sheet[] = ['Contractor:', 'REEVES CONSTRUCTION'];
        $sheet[] = ['Peek No:', $this->jobNumber];
        $sheet[] = ['Project', 'IFB 25-26-2025 LMIG'];
        $sheet[] = ['County:', 'GLYNN'];
        $sheet[] = [];

        /*
        |----------------------------------------
        | Phase row
        |----------------------------------------
        */

        $phaseRow = ['',''];

        foreach ($phases as $phase) {
            $phaseRow[] = $phase;
        }

        $sheet[] = $phaseRow;

        /*
        |----------------------------------------
        | Description row
        |----------------------------------------
        */

        $descRow = ['',''];

        foreach ($phases as $phase) {
            $descRow[] = $phaseDescriptions[$phase];
        }

        $sheet[] = $descRow;

        /*
        |----------------------------------------
        | Unit row
        |----------------------------------------
        */

        $unitRow = ['',''];

        foreach ($phases as $phase) {
            $unitRow[] = $phaseUnits[$phase];
        }

        $sheet[] = $unitRow;

        /*
        |----------------------------------------
        | Date header row
        |----------------------------------------
        */

        $dateHeader = ['DATE','ROAD NAME'];

foreach ($phases as $phase) {
    $dateHeader[] = '';
}

$dateHeader[] = 'NOTES';

$sheet[] = $dateHeader;

        /*
        |----------------------------------------
        | Data rows
        |----------------------------------------
        */

        foreach ($data as $row) {

    $line = [
        $row['date'],
        $row['road']
    ];

    foreach ($phases as $phase) {

        $qty = $row[$phase];

        $line[] = $qty == 0 ? '' : number_format($qty,3);
    }

    $line[] = $row['notes'] ?? '';

    $sheet[] = $line;
}

        /*
        |----------------------------------------
        | Total quantities
        |----------------------------------------
        */

        $totals = ['','TOTAL QTY'];

        foreach ($phases as $phase) {

            $total = $rows
                ->where('phase',$phase)
                ->sum('qty');

            $totals[] = number_format($total,3);
        }

        $sheet[] = [];
        $sheet[] = $totals;

        return $sheet;
    }

    /*
    |----------------------------------------
    | Styling
    |----------------------------------------
    */

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function(AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();
				
                /*
                Column widths
                */
				$sheet->getColumnDimension($highestColumn)->setWidth(60);
                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(35);
				$sheet->getStyle('A8:'.$highestColumn.$highestRow)
				->getAlignment()
				->setWrapText(true);
                foreach(range('C',$highestColumn) as $col){
                    $sheet->getColumnDimension($col)->setWidth(16);
                }

                /*
                Title formatting
                */

                $sheet->mergeCells('A1:'.$highestColumn.'1');

                $sheet->getStyle('A1')->applyFromArray([
                    'font'=>[
                        'bold'=>true,
                        'size'=>16
                    ],
                    'alignment'=>[
                        'horizontal'=>Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                /*
                Header styling
                */

                $sheet->getStyle('A8:'.$highestColumn.'9')->applyFromArray([

                    'font'=>[
                        'bold'=>true
                    ],

                    'alignment'=>[
                        'horizontal'=>Alignment::HORIZONTAL_CENTER,
                        'vertical'=>Alignment::VERTICAL_CENTER,
                        'wrapText'=>true
                    ],

                    'fill'=>[
                        'fillType'=>Fill::FILL_SOLID,
                        'startColor'=>[
                            'rgb'=>'D7E8EE'
                        ]
                    ],

                    'borders'=>[
                        'allBorders'=>[
                            'borderStyle'=>Border::BORDER_THIN
                        ]
                    ]

                ]);

                /*
                Table borders
                */

                $sheet->getStyle('A8:'.$highestColumn.$highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                /*
                Total row bold
                */

                $sheet->getStyle('A'.$highestRow.':'.$highestColumn.$highestRow)
                    ->getFont()->setBold(true);

            }

        ];
    }
}