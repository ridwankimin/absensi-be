<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once('./application/third_party/dompdf/autoload.inc.php');

use Dompdf\Dompdf;

class Mypdf
{
    protected $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function generate($view, $data = array(), $filename = 'Laporan', $paper = 'A4', $orientation = 'portrait')
    {
        $dompdf = new Dompdf();
        $html = $this->ci->load->view($view, $data, TRUE);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        // Render the HTML as PDF
        $dompdf->render();
        $this->injectPageCount($dompdf);
        $dompdf->stream($filename . ".pdf", array("Attachment" => FALSE));
    }

    public function getPdf($view, $data = array(), $filename = 'Laporan', $paper = 'A4', $orientation = 'portrait')
    {
        $dompdf = new Dompdf();
        $html = $this->ci->load->view($view, $data, TRUE);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        // Render the HTML as PDF
        $dompdf->render();
        $this->injectPageCount($dompdf);
        //    $dompdf->stream();
        // echo hex2bin($dompdf->render());
        // $dompdf->stream($filename . ".pdf", array("Attachment" => FALSE));
        $output = $dompdf->output();
        return $output;
        // var_dump($output);
        // file_put_contents('Brochure.pdf', $output);
    }

    function injectPageCount(Dompdf $dompdf): void
    {
        /** @var CPDF $canvas */
        $canvas = $dompdf->getCanvas();
        $pdf = $canvas->get_cpdf();

        foreach ($pdf->objects as &$o) {
            if ($o['t'] === 'contents') {
                $o['c'] = str_replace('DOMPDF_PAGE_COUNT_PLACEHOLDER', $canvas->get_page_count(), $o['c']);
            }
        }
    }
}

/* End of file Mypdf.php */
/* Location: ./application/libraries/Mypdf.php */
