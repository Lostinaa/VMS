<?php

namespace App\Exports;

use App\Models\VisitRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitReportExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
    ) {}

    public function query()
    {
        $query = VisitRequest::query()
            ->with(['visitor', 'host', 'site', 'zone']);

        if ($this->dateFrom) {
            $query->whereDate('scheduled_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('scheduled_at', '<=', $this->dateTo);
        }

        return $query->orderByDesc('scheduled_at');
    }

    public function headings(): array
    {
        return [
            'ID', 'Reference', 'Visitor Name', 'Organization', 'Email', 'Phone',
            'Car Plate', 'Host', 'Site', 'Zone', 'Department', 'Meeting Location',
            'Purpose', 'Category', 'Visitor Type', 'Status',
            'Scheduled At', 'Expected Duration (hrs)', 'Parking',
        ];
    }

    public function map($visit): array
    {
        return [
            $visit->id,
            'VMS-' . str_pad($visit->id, 5, '0', STR_PAD_LEFT),
            $visit->visitor->full_name ?? '',
            $visit->visitor->organization ?? '',
            $visit->visitor->email ?? '',
            $visit->visitor->phone ?? '',
            $visit->visitor->car_plate_number ?? '',
            $visit->host->name ?? '',
            $visit->site->name ?? '',
            $visit->zone->name ?? '',
            $visit->host->department?->name ?? '',
            $visit->meeting_location ?? '',
            $visit->purpose,
            ucfirst($visit->category),
            ucfirst($visit->visitor_type),
            ucfirst(str_replace('_', ' ', $visit->status)),
            $visit->scheduled_at?->format('Y-m-d H:i'),
            $visit->expected_duration_hours,
            $visit->parking_number ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
