<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

// use function PHPSTORM_META\map;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Style\Color;

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '259200');

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class Laporan extends RestController
{
    function __construct()
    {
        parent::__construct();
        $this->load->library('Authorization_Token');
        $is_valid_token = $this->authorization_token->validateToken();

        if (!$is_valid_token['status']) {
            $this->response([
                'status' => FALSE,
                'message' => $is_valid_token['message']
            ], RESTController::HTTP_UNAUTHORIZED);
            return;
        }
        $this->load->model('LaporanModel', 'lap');
        $this->load->model('PegawaiModel', 'peg');
        $this->load->model('MasterIzinModel', 'mizin');
        $this->load->helper('security');
        $this->load->library('Pdf_lib');
    }

    public function index_post()
    {
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules('upt', 'upt', 'required|max_length[4]|xss_clean');
        $this->form_validation->set_rules('bagian', 'bagian', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('pegawai', 'pegawai', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('bulan', 'bulan', 'required|max_length[2]|xss_clean');
        $this->form_validation->set_rules('tahun', 'tahun', 'required|max_length[4]|xss_clean');
        $this->form_validation->set_rules('return', 'return', 'required|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $cari = array(
            'MONTH(tanggal)=' => $this->post('bulan'),
            'YEAR(tanggal)=' => $this->post('tahun'),
        );
        $cariizin = array(
            'MONTH(tgl_mulai)=' => $this->post('bulan'),
            'YEAR(tgl_mulai)=' => $this->post('tahun'),
        );
        if ($this->post('upt') != 'all') {
            $cari['user.upt_id'] = $this->post('upt');
            $cariizin['user.upt_id'] = $this->post('upt');
        }
        if ($this->post('bagian') != 'all') {
            if ($this->post('role') == 'adm-tu') {
                $cari['LEFT(user.bagian_id,1)='] = substr($this->post('bagian'), 0, 1);
                $cariizin['LEFT(user.bagian_id,1)='] = substr($this->post('bagian'), 0, 1);
            } else {
                $cari['user.bagian_id'] = $this->post('bagian');
                $cariizin['user.bagian_id'] = $this->post('bagian');
            }
        }
        if ($this->post('pegawai') != 'all') {
            $cari['user_presensi.id_user'] = $this->post('pegawai');
            $cariizin['pp.id_user'] = $this->post('pegawai');
        }
        $result = $this->lap->getDataLaporan($cari);
        $izin = $this->lap->getDataPerizinan($cariizin);
        foreach ($izin as $iz) {
            $begin = new \DateTime($iz['tgl_mulai']);
            $end   = new \DateTime($iz['tgl_selesai']);
            for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
                $result[] = array(
                    "unit_kerja" => $iz['unit_kerja'],
                    "nama" => $iz['nama'],
                    "nip" => $iz['nip'],
                    "tanggal" => $i->format("Y-m-d"),
                    "tanggal_real" => "",
                    "waktu_presensi_masuk" => "",
                    "batas_waktu_presensi_masuk" => "",
                    "waktu_presensi_pulang" => "",
                    "batas_waktu_presensi_pulang" => "",
                    "terlambat" => "",
                    "plg_sebelum" => "",
                    "jumlah_jam" => "",
                    "jenis_absen_masuk" => "",
                    "jenis_absen_pulang" => "",
                    "status" => $iz['status'],
                );
            }
        }
        foreach ($result as $key => $row) {
            $unit_kerja[$key] = $row['unit_kerja'];
            $nip[$key] = $row['nip'];
            $tanggal[$key] = $row['tanggal'];
        }
        array_multisort($unit_kerja, SORT_ASC, $nip, SORT_ASC, $tanggal, SORT_ASC, $result);
        if ($result) {
            if ($this->post('return') == 'view') {
                $this->response([
                    'status' => TRUE,
                    'message' => 'Rekap laporan absen ditemukan',
                    'data' => $result
                ], RESTController::HTTP_OK);
            } else if ($this->post('return') == 'excel') {
                $this->laporanDetail($result, $this->post());
            } else if ($this->post('return') == 'pdf') {
                $this->laporanDetailPdf($result, $this->post());
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Rekap laporan absen tidak ditemukan',
            ], RESTController::HTTP_NOT_FOUND);
        }
    }

    public function rekap_post()
    {
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules('upt', 'upt', 'required|max_length[4]|xss_clean');
        $this->form_validation->set_rules('bagian', 'bagian', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('pegawai', 'pegawai', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('bulan', 'bulan', 'required|max_length[2]|xss_clean');
        $this->form_validation->set_rules('tahun', 'tahun', 'required|max_length[4]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }

        $cari = array(
            'MONTH(tanggal)=' => $this->post('bulan'),
            'YEAR(tanggal)=' => $this->post('tahun'),
        );
        $cariizin = array(
            'MONTH(tgl_mulai)=' => $this->post('bulan'),
            'YEAR(tgl_mulai)=' => $this->post('tahun'),
        );
        if ($this->post('upt') != 'all') {
            $cari['user.upt_id'] = $this->post('upt');
            $cariizin['user.upt_id'] = $this->post('upt');
        }
        if ($this->post('bagian') != 'all') {
            if ($this->post('role') == 'adm-tu') {
                $cari['LEFT(user.bagian_id,1)='] = substr($this->post('bagian'), 0, 1);
                $cariizin['LEFT(user.bagian_id,1)='] = substr($this->post('bagian'), 0, 1);
            } else {
                $cari['user.bagian_id'] = $this->post('bagian');
                $cariizin['user.bagian_id'] = $this->post('bagian');
            }
        }
        if ($this->post('pegawai') != 'all') {
            $cari['user_presensi.id_user'] = $this->post('pegawai');
            $cariizin['pp.id_user'] = $this->post('pegawai');
        }
        $result = $this->lap->getDataRekap($cari);
        $izin = $this->lap->getDataPerizinan($cariizin);
        foreach ($result as $val) {
            $presensi[$val['nip']][$val['day']] = $val['status'];
        }
        foreach ($izin as $iz) {
            $begin = new DateTime($iz['tgl_mulai']);
            $end   = new DateTime($iz['tgl_selesai']);
            for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
                $presensi[$iz['nip']][intval($i->format("d"))] = $iz['kode'];
                // $iz['day'] = $i->format("m");
                // $result['id_user'] = $iz['id_user'];
                // $result['day'] = $i->format("m");
                // $result['nip'] = $iz['nip'];
                // $result['bagian_id'] = $iz['bagian_id'];
                // $result['status'] = $iz['kode'];
                // $restIzin[] = $iz;
            }
        }
        if ($presensi) {
            ksort($presensi);
            if ($this->post('return') == 'view') {
                $this->response([
                    'status' => TRUE,
                    'message' => 'Rekap data absen ditemukan',
                    'data' => $presensi
                ], RESTController::HTTP_OK);
            } else if ($this->post('return') == 'excel') {
                $cari = array('verified' => '1');
                if ($this->post('bagian') != 'all') {
                    $cari['bagian_id'] = $this->post('bagian');
                }
                if ($this->post('upt') != 'all') {
                    $cari['upt_id'] = $this->post('upt');
                }
                if ($this->post('pegawai') != 'all') {
                    $cari['id_user'] = $this->post('pegawai');
                }
                $pegawailist = $this->peg->getDataPegawai($cari);
                foreach ($pegawailist as $val) {
                    $user[$val['nip']] = $val['nama'];
                }
                $this->laporanSummary($presensi, $this->post(), $user);
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Rekap data absen tidak ditemukan',
            ], RESTController::HTTP_NOT_FOUND);
        }
    }

    function laporanDetail($data, $inputan)
    {
        $namaBulan = [
            "01" => "Januari",
            "02" => "Februari",
            "03" => "Maret",
            "04" => "April",
            "05" => "Mei",
            "06" => "Juni",
            "07" => "Juli",
            "08" => "Agustus",
            "09" => "September",
            "10" => "Oktober",
            "11" => "November",
            "12" => "Desember"
        ];
        $periode = $namaBulan[$inputan['bulan']] . " " . $inputan['tahun'];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("ePresensi Barantin")
            ->setLastModifiedBy("ePresensi Barantin")
            ->setTitle("Rekap Laporan")
            ->setSubject("Laporan Periode : " . $periode)
            ->setDescription(
                "Program created by IT Barantin"
            )
            ->setKeywords("ePresensi")
            ->setCategory("unduh");

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("ePresensi " . $inputan['bulan'] . $inputan['tahun']);

        $x = 0;
        $abjad = array(
            "A",
            "B",
            "C",
            "D",
            "E",
            "F",
            "G",
            "H",
            "I",
            "J",
            "K",
            "L",
            "M",
            "N",
            "O",
            "P",
            "Q",
            "R",
            "S",
            "T",
            "U",
            "V",
            "W",
            "X",
            "Y",
            "Z",
            "AA",
            "AB",
            "AC",
            "AD",
            "AE",
            "AF",
            "AG",
            "AH",
            "AI",
            "AJ",
            "AK",
            "AL",
            "AM",
            "AN",
            "AO",
            "AP",
            "AQ",
            "AR",
            "AS",
            "AT",
            "AU",
            "AV",
            "AW",
            "AX",
            "AY",
            "AZ"
        );
        $sheet->setShowGridlines(false);
        $sheet->setCellValue('A1', 'LAPORAN PRESENSI');
        $sheet->setCellValue('A2', 'Periode : ' . $periode);
        $sheet->setCellValue('A3', 'Diunduh tanggal : ' . date('Y-m-d H:i:s') . ' WIB');
        $sheet->setCellValue('A4', 'Oleh : ' . $inputan['profiluser']['nama'] . " - " . $inputan['profiluser']['nip']);
        // $sheet->setCellValue('A5', '');
        $cellnum = '6';
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'NO');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Unit Kerja');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Nama Pegawai');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'NIP Pegawai');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Tgl Presensi');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Tgl Aktual');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Presensi Masuk');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Batas Presensi Masuk');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Presensi Pulang');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Batas Presensi Pulang');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Terlambat (Menit)');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Pulang Sebelum Waktu (Menit)');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Jumlah (Menit)');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'O/A');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Status');
        $i = 7;
        $no = 1;
        $x = 0;

        //Retrieve Highest Column (e.g AE)
        $highestColumn = $sheet->getHighestColumn();
        // $highestRow = $sheet->getHighestRow();

        $dataCount = count($data);
        $sheet->getStyle('A1:' . $highestColumn . $cellnum)->getFont()->setBold(true);
        $sheet->getStyle('D7:D' . ($dataCount + $i - 1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        //set first row bold
        // $spreadsheet->getActiveSheet()->getStyle('B2')->getBorders()->applyFromArray(['allBorders' => ['borderStyle' => Border::BORDER_DASHDOT, 'color' => ['rgb' => '000000']]]);
        // $styleArray = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_DASHDOT, 'color' => ['rgb' => '000000']]]];

        $styleArray = [
            'borders' => [
                'bottom' => ['borderStyle' => 'thin', 'color' => ['argb' => '000000']],
                'top' => ['borderStyle' => 'thin', 'color' => ['argb' => '000000']],
                'right' => ['borderStyle' => 'thin', 'color' => ['argb' => '000000']],
                'left' => ['borderStyle' => 'thin', 'color' => ['argb' => '000000']],
            ],
        ];
        $sheet->getStyle('A' . ($i - 1) . ':' . $highestColumn . ($dataCount + $i - 1))->applyFromArray($styleArray, false);

        foreach ($data as $row) {
            $sheet->setCellValue($abjad[$x++] . $i, $no++);
            $sheet->setCellValue($abjad[$x++] . $i, ($row['unit_kerja']));
            $sheet->setCellValue($abjad[$x++] . $i, ($row['nama']));
            $colNip = $abjad[$x] . $i;
            $sheet->setCellValue($colNip . $i, ($row['nip']));
            $sheet->setCellValueExplicit($colNip, $row['nip'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $x++;
            $sheet->setCellValue($abjad[$x++] . $i, ($row['tanggal']));
            $sheet->setCellValue($abjad[$x++] . $i, ($row['tanggal_real'] ? $row['tanggal_real'] : $row['tanggal']));
            // $sheet->setCellValue($abjad[$x++] . $i, ($row['waktu_presensi_masuk']));
            $colWaktuMasuk = $abjad[$x] . $i;
            $sheet->setCellValue($colWaktuMasuk, $row['waktu_presensi_masuk']);
            if ($row['waktu_presensi_masuk'] < $row['batas_waktu_presensi_masuk']) {
                $sheet->getStyle($colWaktuMasuk)->getFont()->getColor()->setRGB('00B050'); // Warna hijau
            } else {
                $sheet->getStyle($colWaktuMasuk)->getFont()->getColor()->setRGB('e50d0d'); // merah
            }
            $x++;
            $sheet->setCellValue($abjad[$x++] . $i, ($row['batas_waktu_presensi_masuk']));
            // $sheet->setCellValue($abjad[$x++] . $i, ($row['waktu_presensi_pulang']));
            $colWaktuPulang = $abjad[$x] . $i;
            $sheet->setCellValue($colWaktuPulang, $row['waktu_presensi_pulang']);
            if ($row['waktu_presensi_pulang'] > $row['batas_waktu_presensi_pulang']) {
                $sheet->getStyle($colWaktuPulang)->getFont()->getColor()->setRGB('00B050'); // Warna hijau
            } else {
                $sheet->getStyle($colWaktuPulang)->getFont()->getColor()->setRGB('e50d0d'); // merah
            }
            $x++;
            $sheet->setCellValue($abjad[$x++] . $i, ($row['batas_waktu_presensi_pulang']));
            $sheet->setCellValue($abjad[$x++] . $i, ($row['terlambat']));
            $sheet->setCellValue($abjad[$x++] . $i, ($row['plg_sebelum']));
            $sheet->setCellValue($abjad[$x++] . $i, ($row['jumlah_jam']));
            $sheet->setCellValue($abjad[$x++] . $i, ($row['jenis_absen_masuk'] ? $row['jenis_absen_masuk'] : $row['jenis_absen_pulang']));
            // $sheet->setCellValue($abjad[$x++] . $i, ($row['status']));
            $colStatus = $abjad[$x] . $i;
            $status = $row['status'];

            if (strpos($status, '(FWA)') !== false) {
                $text = new RichText();

                // Ambil teks sebelum (FWA)
                $parts = explode('(FWA)', $status);
                $text->createTextRun($parts[0]);
                $run2 = $text->createTextRun('(FWA)');
                $run2->getFont()->getColor()->setRGB('00B050'); // ijo

                $sheet->setCellValue($colStatus, $text);
            } else {
                $sheet->setCellValue($colStatus, $status);
                if ($status != "Tepat waktu") {
                    if ($row['jenis_absen_masuk'] || $row['jenis_absen_pulang']) {
                        $sheet->getStyle($colStatus)->getFont()->getColor()->setRGB('e50d0d'); // merah
                    } else {
                        $sheet->getStyle($colStatus)->getFont()->getColor()->setRGB('0651e5'); // biru
                    }
                }
            }
            $i++;
            $x = 0;
        }

        $writer = new Xlsx($spreadsheet);
        ob_end_clean();
        // header('Content-Type: application/vnd.ms-excel');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-type: application/csv');
        header('Content-Disposition: attachment;filename="Data presensi.xlsx"');
        header('Cache-Control: max-age=0');
        // $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    function laporanSummary($data, $inputan, $user)
    {
        $namaBulan = [
            "01" => "Januari",
            "02" => "Februari",
            "03" => "Maret",
            "04" => "April",
            "05" => "Mei",
            "06" => "Juni",
            "07" => "Juli",
            "08" => "Agustus",
            "09" => "September",
            "10" => "Oktober",
            "11" => "November",
            "12" => "Desember"
        ];
        $periode = $namaBulan[$inputan['bulan']] . " " . $inputan['tahun'];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("ePresensi Barantin")
            ->setLastModifiedBy("ePresensi Barantin")
            ->setTitle("Absen summary")
            ->setSubject("Absen summary Periode : " . $periode)
            ->setDescription(
                "Program created by IT Barantin"
            )
            ->setKeywords("ePresensi")
            ->setCategory("unduh");

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Summary " . $inputan['bulan'] . $inputan['tahun']);

        $x = 0;
        $abjad = array(
            "A",
            "B",
            "C",
            "D",
            "E",
            "F",
            "G",
            "H",
            "I",
            "J",
            "K",
            "L",
            "M",
            "N",
            "O",
            "P",
            "Q",
            "R",
            "S",
            "T",
            "U",
            "V",
            "W",
            "X",
            "Y",
            "Z",
            "AA",
            "AB",
            "AC",
            "AD",
            "AE",
            "AF",
            "AG",
            "AH",
            "AI",
            "AJ",
            "AK",
            "AL",
            "AM",
            "AN",
            "AO",
            "AP",
            "AQ",
            "AR",
            "AS",
            "AT",
            "AU",
            "AV",
            "AW",
            "AX",
            "AY",
            "AZ"
        );
        $sheet->setShowGridlines(false);
        $sheet->setCellValue('A1', 'SUMMARY PRESENSI' . ($inputan['jenis'] == 'uangmakan' ? " (Uang makan)" : ""));
        $sheet->setCellValue('A2', 'Periode : ' . $periode);
        $sheet->setCellValue('A3', 'Diunduh tanggal : ' . date('Y-m-d H:i:s') . ' WIB');
        $sheet->setCellValue('A4', 'Oleh : ' . $inputan['profiluser']['nama'] . " - " . $inputan['profiluser']['nip']);
        // $sheet->setCellValue('A5', '');
        $sheet->mergeCells('A6:A7');
        $sheet->mergeCells('B6:B7');
        $sheet->mergeCells('C6:C7');

        $num_day = date('t', strtotime(date($inputan['bulan'] . '-' . $inputan['tahun'] . '-' . '01')));

        $last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + $num_day);
        $sheet->mergeCells('D6:' . $last_col . '6');

        $cellnum = '6';
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'NO');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'Nama');
        $sheet->setCellValue($abjad[$x++] . $cellnum, 'NIP');
        $sheet->setCellValue($abjad[$x++] . $cellnum, $periode);
        $hari_kerja = ["1", "2", "3", "4", "5"];
        for ($i = 1; $i <= $num_day; $i++) {
            $col_name = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + $i);
            $sheet->getColumnDimension($col_name)->setWidth(4.7);
            $day = date('w', strtotime($inputan['bulan'] . '-' . $inputan['tahun'] . '-' . substr('0' . $i, -2)));
            if (!in_array($day, $hari_kerja)) {
                $sheet
                    ->getStyle($col_name . '2')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('e2e3e5');
            }
            $sheet->setCellValue($col_name . '7', $i);
        }
        if ($inputan['jenis'] == 'uangmakan') {
            $last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + $num_day);
            $sheet->setCellValue($last_col . '6', 'TOTAL');
            $sheet->mergeCells($last_col . '6:' . $last_col . '7');
        }
        $sheet->getStyle('A1:' . $last_col . 7)->getFont()->setBold(true);
        $no = 1;
        $row = 8;
        foreach ($data as $id_user => $absen_user) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $user[$id_user]);
            $sheet->setCellValue('C' . $row, $id_user);
            $sheet->setCellValueExplicit('C' . $row, $id_user, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            for ($i = 1; $i <= $num_day; $i++) {
                $col_name = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + $i);
                $day = date('w', strtotime($inputan['tahun'] . '-' . $inputan['bulan'] . '-' . substr('0' . $i, -2)));
                $text = '';
                if (in_array($day, $hari_kerja)) {
                    if (key_exists($i, $absen_user)) {
                        switch ($absen_user[$i]) {
                            case 'tam':
                                $bgcolor = 'f8d7da';
                                $text = 'TAM';
                                break;
                            case 'tam_psw':
                                $bgcolor = 'f8d7da';
                                $text = 'TAM,PSW';
                                break;
                            case 'tap':
                                $bgcolor = 'f8d7da';
                                $text = 'TAP';
                                break;
                            case 'tl_tap':
                                $bgcolor = 'f8d7da';
                                $text = 'TL,TAP';
                                break;
                            case 'tam_tap':
                                $bgcolor = 'f8d7da';
                                $text = 'TAM,TAP';
                                break;
                            case 'tl_psw':
                                $bgcolor = 'fff3cd';
                                $text = 'TL,PSW';
                                break;
                            case 'tl_f':
                                $bgcolor = 'd1e7dd';
                                $text = 'TLF';
                                break;
                            case 'tl':
                                $bgcolor = 'fff3cd';
                                $text = 'TL';
                                break;
                            case 'psw_f':
                                $bgcolor = 'd1e7dd';
                                $text = 'PSWF';
                                break;
                            case 'psw':
                                $bgcolor = 'fff3cd';
                                $text = 'PSW';
                                break;
                            case 'tw':
                                $bgcolor = 'd1e7dd';
                                $text = 'v';
                                break;
                            default:
                                $bgcolor = 'C6CDE7';
                                $text = $absen_user[$i];
                        }
                    } else {
                        $bgcolor = 'f8d7da';
                        $text = 'TA';
                    }
                } else {
                    $bgcolor = 'e2e3e5';
                }

                if (strpos($text, ',')) {
                    $sheet->getColumnDimension($col_name)->setWidth(8.4);
                }
                $sheet
                    ->getStyle($col_name . $row)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($bgcolor);

                $sheet->setCellValue($col_name . $row, ($inputan['jenis'] == 'summary' ? $text : (key_exists($i, $absen_user) ? (strtolower($absen_user[$i]) == $absen_user[$i] ? 1 : 0) : 0)));
            }
            if ($inputan['jenis'] == 'uangmakan') {
                $filtered = [];
                foreach ($absen_user as $key => $val) {
                    if (strtolower($val) == $val) {
                        $filtered[] = 1;
                    }
                }
                $sheet->setCellValue($last_col . $row, array_sum($filtered));
            }
            $no++;
            $row++;
        }

        $sheet->getStyle('A6:' . $last_col . $row)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B8:' . 'B' . $row)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle('A6:' . $last_col . $row)
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        $row--;
        $sheet->getStyle('A6:' . $last_col . $row)->applyFromArray($styleArray);
        if ($inputan['jenis'] == 'summary') {
            $strizin = "";
            $ketizin = $this->mizin->getMasterIzin();
            $xx = 1;
            foreach ($ketizin as $ket) {
                $strizin .= $ket['kode'] . ": " . $ket['deskripsi'] . ($xx == count($ketizin) ? "" : ", ");
                $xx++;
            }

            $sheet->setCellValue('A' . ++$row, '*)Keterangan: V: Tepat waktu, TL: Terlambat masuk, TLF: Terlambat masuk (FWA), PSW: Pulang sebelum waktunya, PSWF: Pulang sebelum waktunya (FWA), TAM: Tidak absen masuk, TAP: Tidak absen pulang, ' . $strizin);
            $sheet->getStyle('A' . $row)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        }
        $writer = new Xlsx($spreadsheet);
        ob_end_clean();
        // header('Content-Type: application/vnd.ms-excel');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-type: application/csv');
        header('Content-Disposition: attachment;filename="Data presensi.xlsx"');
        header('Cache-Control: max-age=0');
        // $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    function laporanDetailPdf($data, $inputan)
    {
        $namaBulan = [
            "01" => "Januari",
            "02" => "Februari",
            "03" => "Maret",
            "04" => "April",
            "05" => "Mei",
            "06" => "Juni",
            "07" => "Juli",
            "08" => "Agustus",
            "09" => "September",
            "10" => "Oktober",
            "11" => "November",
            "12" => "Desember"
        ];
        $periode = $namaBulan[$inputan['bulan']] . " " . $inputan['tahun'];
        $pdf = new Pdf_lib('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator("ePresensi Barantin");
        $pdf->SetAuthor('IT Barantin');
        $pdf->SetTitle('Laporan ePresensi Barantin');
        $pdf->SetSubject('Absen summary Periode : ' . $periode);
        $pdf->SetKeywords('ePresensi, PDF, Laporan, Summary');
        $pdf->SetProtection(array('modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'), '', null, 2, null);

        // set default header data
        $pdf->setPrintHeader(false);
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 001', PDF_HEADER_STRING, array(0, 64, 255), array(0, 64, 128));
        $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

        // set header and footer fonts
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        // $pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
        $font_size = 10;

        // Set font
        // $pdf->SetMargins($margin_left, $margin_top, $margin_right, false);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        // set default font subsetting mode
        $pdf->setFontSubsetting(true);

        // Set font

        // Add a page
        // This method has several options, check the source code documentation for more information.
        $pdf->AddPage('L');

        $pdf->SetFont('helvetica', '', $font_size, '', 'default', true);
        $tglnow = date('Y-m-d H:i:s');
        $oleh = $inputan['profiluser']['nama'] . " - " . $inputan['profiluser']['nip'];
        $html = <<<EOD
                <h4>LAPORAN PRESENSI <br>
                Periode : $periode <br>
                Diunduh tanggal : $tglnow WIB <br>
                Oleh : $oleh <br>
                </h4>
                EOD;
        $border_color = '#CECECE';
        $background_color = '#efeff0';
        $html .= <<<EOD
		<table border="0" cellspacing="0" cellpadding="6">
			<thead>
				<tr border="1" style="background-color:$background_color">
					<th style="width:5%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">No</th>
					<th style="width:22%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">Unit Kerja</th>
					<th style="width:18%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">Nama Pegawai</th>
					<th style="width:15%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">NIP Pegawai</th>
					<th style="width:8%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Tanggal</th>
					<th style="width:7%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Masuk</th>
					<th style="width:7%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Pulang</th>
					<th style="width:5%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">O/A</th>
					<th style="width:15%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Keterangan</th>
				</tr>
			</thead>
			<tbody>
		EOD;
        $no = 1;
        foreach ($data as $val) {
            // var_dump($val);
            $jenisabsen = '';
            if ($val['jenis_absen_masuk']) {
                $jenisabsen = strtoupper($val['jenis_absen_masuk']);
            }
            if ($val['jenis_absen_pulang']) {
                $jenisabsen = strtoupper($val['jenis_absen_pulang']);
            }
            // if (array_key_exists('jenis_absen_masuk', $val)) {
            // } else if(array_key_exists('jenis_absen_pulang', $val)) {
            // }
            $exp = explode('-', $val['tanggal']);
            $tanggal = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
            $warnamasuk = $val['waktu_presensi_masuk'] > $val['batas_waktu_presensi_masuk'] ? 'color:red;' : '';
            $warnapulang = $val['waktu_presensi_pulang'] < $val['batas_waktu_presensi_pulang'] ? 'color:red;' : '';
            $status = $val['status'];
            if(strpos($status, " (FWA)")) {
                $fwa = '<b style="color:green;"> (FWA)</b>'; 
                $status = '<span>' . str_replace(" (FWA)", "", $val['status']) . $fwa . '</span>';
            } else {
                if(!$val['jenis_absen_masuk'] && !$val['jenis_absen_pulang']) {
                    $status = '<span style="color:blue;">' . $val['status'] . '</span>';
                } else if($status != 'Tewap waktu') {
                    $status = '<span style="color:red;">' . $val['status'] . '</span>';
                }
            }
            $html .= <<<EOD
					<tr>
						<td style="width:5%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">$no</td>
						<td style="width:22%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color">$val[unit_kerja]</td>
						<td style="width:18%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color">$val[nama]</td>
						<td style="width:15%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color">$val[nip]</td>
						<td style="width:8%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="right">$tanggal</td>
						<td style="width:7%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;$warnamasuk" align="right">$val[waktu_presensi_masuk]</td>
						<td style="width:7%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;$warnapulang">$val[waktu_presensi_pulang]</td>
						<td style="width:5%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$jenisabsen</td>
						<td style="width:15%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$status</td>
					</tr>
					EOD;
            $no++;
        }
        $html .= '</tbody></table>';

        // Print text using writeHTMLCell()
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

        ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="laporan.pdf"');
        header('Cache-Control: max-age=0');
        $pdf->Output('example_001.pdf', 'D');
        exit;
    }
}
