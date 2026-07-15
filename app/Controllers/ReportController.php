<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ReportModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportController extends BaseController
{
    protected ReportModel $reportModel;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
    }

    public function index()
    {
        return view('report/index', [
            'title'     => 'Laporan',
            'startDate' => date('Y-m-01'), // awal bulan berjalan sebagai default
            'endDate'   => date('Y-m-d'),
        ]);
    }

    // ==========================================================
    // AJAX DATA per jenis laporan
    // ==========================================================

    public function data($type)
    {
        [$startDate, $endDate] = $this->getValidatedDateRange();

        if (!$startDate) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Rentang tanggal tidak valid.']);
        }

        $data = match ($type) {
            'revenue'     => [
                'rows'    => $this->reportModel->revenueReport($startDate, $endDate),
                'summary' => $this->reportModel->revenueSummary($startDate, $endDate),
            ],
            'reservation' => [
                'rows'    => $this->reportModel->reservationReport($startDate, $endDate),
                'summary' => $this->reportModel->reservationSummaryByStatus($startDate, $endDate),
            ],
            'checkin'     => ['rows' => $this->reportModel->checkInReport($startDate, $endDate)],
            'checkout'    => ['rows' => $this->reportModel->checkOutReport($startDate, $endDate)],
            'room'        => [
                'rows'    => $this->reportModel->roomOccupancyReport($startDate, $endDate),
                'summary' => $this->reportModel->currentRoomStatusSummary(),
            ],
            'customer'    => ['rows' => $this->reportModel->customerReport($startDate, $endDate)],
            default       => null,
        };

        if ($data === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Jenis laporan tidak dikenali.']);
        }

        return $this->response->setJSON($data);
    }

    // ==========================================================
    // EXPORT EXCEL
    // ==========================================================

    public function exportExcel($type)
    {
        [$startDate, $endDate] = $this->getValidatedDateRange();

        if (!$startDate) {
            return redirect()->back()->with('error', 'Rentang tanggal tidak valid.');
        }

        $config = $this->getReportConfig($type, $startDate, $endDate);
        if (!$config) {
            return redirect()->back()->with('error', 'Jenis laporan tidak dikenali.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($config['title'], 0, 31));

        $lastColumnLetter = Coordinate::stringFromColumnIndex(count($config['headers']));

        // Header judul
        $sheet->setCellValue('A1', $config['title']);
        $sheet->mergeCells('A1:' . $lastColumnLetter . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', "Periode: {$startDate} s/d {$endDate}");
        $sheet->mergeCells('A2:' . $lastColumnLetter . '2');

        // Header kolom (baris ke-4)
        $col = 1;
        foreach ($config['headers'] as $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($columnLetter . '4', $header);
            $col++;
        }
        $sheet->getStyle('A4:' . $lastColumnLetter . '4')
            ->getFont()->setBold(true);

        // Data rows
        $rowNum = 5;
        foreach ($config['rows'] as $row) {
            $col = 1;
            foreach ($config['mapper']($row) as $value) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($columnLetter . $rowNum, $value);
                $col++;
            }
            $rowNum++;
        }

        // Auto-size kolom
        foreach (range(1, count($config['headers'])) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $filename = $config['filename'] . '_' . $startDate . '_to_' . $endDate . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    // ==========================================================
    // EXPORT PDF
    // ==========================================================

    public function exportPdf($type)
    {
        [$startDate, $endDate] = $this->getValidatedDateRange();

        if (!$startDate) {
            return redirect()->back()->with('error', 'Rentang tanggal tidak valid.');
        }

        $config = $this->getReportConfig($type, $startDate, $endDate);
        if (!$config) {
            return redirect()->back()->with('error', 'Jenis laporan tidak dikenali.');
        }

        $html = view('report/pdf_template', [
            'title'     => $config['title'],
            'headers'   => $config['headers'],
            'rows'      => $config['rows'],
            'mapper'    => $config['mapper'],
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // landscape karena tabel laporan biasanya lebar
        $dompdf->render();

        $filename = $config['filename'] . '_' . $startDate . '_to_' . $endDate . '.pdf';

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    // ==========================================================
    // HELPERS
    // ==========================================================

    /**
     * Validasi ketat format tanggal dari input GET sebelum dipakai di query.
     * WAJIB dipanggil sebelum $startDate/$endDate diteruskan ke ReportModel,
     * karena beberapa method model menyisipkan tanggal langsung ke string SQL
     * (untuk kebutuhan JOIN kondisional) — validasi ini adalah lapis pertahanan
     * utama terhadap SQL Injection di titik tersebut.
     */
    private function getValidatedDateRange(): array
    {
        $start = $this->request->getGet('start_date');
        $end   = $this->request->getGet('end_date');

        $isValidDate = fn($d) => $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        if (!$isValidDate($start) || !$isValidDate($end)) {
            return [null, null];
        }

        if (strtotime($start) > strtotime($end)) {
            return [null, null];
        }

        return [$start, $end];
    }

    /**
     * Konfigurasi kolom & data per jenis laporan, dipakai bersama oleh
     * exportExcel() dan exportPdf() agar format kedua output selalu konsisten.
     */
    private function getReportConfig(string $type, string $startDate, string $endDate): ?array
    {
        return match ($type) {
            'revenue' => [
                'title'    => 'Laporan Pendapatan',
                'filename' => 'Laporan_Pendapatan',
                'headers'  => ['No. Pembayaran', 'No. Invoice', 'No. Booking', 'Customer', 'Metode', 'Jumlah', 'Tanggal Bayar'],
                'rows'     => $this->reportModel->revenueReport($startDate, $endDate),
                'mapper'   => fn($r) => [
                    $r['payment_number'], $r['invoice_number'], $r['booking_number'], $r['customer_name'],
                    strtoupper($r['method']), (float) $r['amount'], date('d-m-Y H:i', strtotime($r['paid_at'])),
                ],
            ],
            'reservation' => [
                'title'    => 'Laporan Reservasi',
                'filename' => 'Laporan_Reservasi',
                'headers'  => ['No. Booking', 'Customer', 'Kamar', 'Tipe', 'Check In', 'Check Out', 'Malam', 'Status'],
                'rows'     => $this->reportModel->reservationReport($startDate, $endDate),
                'mapper'   => fn($r) => [
                    $r['booking_number'], $r['customer_name'], $r['room_number'], $r['room_type_name'],
                    date('d-m-Y', strtotime($r['check_in_date'])), date('d-m-Y', strtotime($r['check_out_date'])),
                    $r['nights'], $r['status'],
                ],
            ],
            'checkin' => [
                'title'    => 'Laporan Check In',
                'filename' => 'Laporan_CheckIn',
                'headers'  => ['No. Booking', 'Customer', 'Kamar', 'Waktu Check In', 'Diproses Oleh'],
                'rows'     => $this->reportModel->checkInReport($startDate, $endDate),
                'mapper'   => fn($r) => [
                    $r['booking_number'], $r['customer_name'], $r['room_number'],
                    date('d-m-Y H:i', strtotime($r['checked_in_at'])), $r['processed_by'],
                ],
            ],
            'checkout' => [
                'title'    => 'Laporan Check Out',
                'filename' => 'Laporan_CheckOut',
                'headers'  => ['No. Booking', 'Customer', 'Kamar', 'Waktu Check Out', 'Total Biaya', 'Diproses Oleh'],
                'rows'     => $this->reportModel->checkOutReport($startDate, $endDate),
                'mapper'   => fn($r) => [
                    $r['booking_number'], $r['customer_name'], $r['room_number'],
                    date('d-m-Y H:i', strtotime($r['checked_out_at'])), (float) $r['total_amount'], $r['processed_by'],
                ],
            ],
            'room' => [
                'title'    => 'Laporan Kamar (Okupansi)',
                'filename' => 'Laporan_Kamar',
                'headers'  => ['No. Kamar', 'Tipe Kamar', 'Total Reservasi', 'Total Malam Terisi'],
                'rows'     => $this->reportModel->roomOccupancyReport($startDate, $endDate),
                'mapper'   => fn($r) => [
                    $r['room_number'], $r['room_type_name'], $r['total_reservations'], $r['total_nights_booked'],
                ],
            ],
            'customer' => [
                'title'    => 'Laporan Customer',
                'filename' => 'Laporan_Customer',
                'headers'  => ['Nama', 'No HP', 'Email', 'Total Reservasi', 'Total Pengeluaran'],
                'rows'     => $this->reportModel->customerReport($startDate, $endDate),
                'mapper'   => fn($r) => [
                    $r['name'], $r['phone'], $r['email'] ?? '-', $r['total_reservations'], (float) $r['total_spent'],
                ],
            ],
            default => null,
        };
    }

    private function columnLetter(int $columnNumber): string
    {
        $letter = '';
        while ($columnNumber > 0) {
            $mod = ($columnNumber - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $columnNumber = (int) (($columnNumber - $mod) / 26) - 1;
        }
        return $letter;
    }
}