<?php

namespace App\Classes;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfClass
{
    // untuk export pdf
    public static function print($file_name, $view, $size = 'A4', $measure = 'potrait', $data = [])
    {
        $options = new Options();
        $options->setChroot(public_path());
        if (env('APP_ENV') === 'production') {
            $options->setIsRemoteEnabled(true);
        }
        $dompdf = new Dompdf();
        $html   = view($view, $data);
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($size, $measure);
        $dompdf->render();
        return $dompdf->output();
    }

    // untuk view pdf
    public static function view($file_name, $view, $size = 'A4', $measure = 'potrait', $data = [])
    {
        $options = new Options();
        $options->setChroot(public_path());
        if (env('APP_ENV') === 'production') {
            $options->setIsRemoteEnabled(true);
        }
        $dompdf = new Dompdf();
        $html = view($view, $data)->render();
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($size, $measure);
        $dompdf->render();
        $dompdf->stream($file_name . '.pdf', ['Attachment' => false]);
        exit(0);
    }
}
