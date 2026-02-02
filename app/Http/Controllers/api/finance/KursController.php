<?php

namespace App\Http\Controllers\api\finance;

use App\Http\Controllers\Controller;
use App\Models\finance\Kurs;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Response;

class KursController extends Controller
{
    public function index()
    {
        $current_date = date('Y-m-d');

        $kurs = Kurs::where('date', $current_date)->first();

        if (!$kurs) {
            $get_kurs = $this->getKursUSD();

            $kurs = Kurs::create([
                'date'   => $current_date,
                'jual'   => $get_kurs['jual'],
                'beli'   => $get_kurs['beli'],
                'tengah' => $get_kurs['tengah']
            ]);
        }

        return Response::json($kurs, 200);
    }

    private function getKursUSD()
    {
        // $user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, seperti Gecko) Chrome/120.0.0.0 Safari/537.36";
        // $headers = [
        //     "Referer: https://www.google.com/",
        //     "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
        //     "Accept-Language: en-US,en;q=0.5",
        //     "Connection: keep-alive"
        // ];
        $headers = [
            "Referer: https://www.google.com/",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
            "Accept-Language: en-US,en;q=0.5",
            "Connection: keep-alive",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36",
            // "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/134.0.6998.90",
        ];
        $url = 'https://www.bi.go.id/id/statistik/informasi-kurs/transaksi-bi/default.aspx';

        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");

        if (!$html = curl_exec($ch)) {
            return response()->json(['status'  => false, 'message' => 'Failed to fetch!'], 500);
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);
        $rows = $xpath->query("//table[contains(@class, 'table-striped')]/tbody/tr[25]");

        $result = [];

        if ($rows->length >= 1) {
            $row = $rows->item(1);
            $cols = $row->getElementsByTagName('td');

            if ($cols->length >= 1) {
                $jual   = $this->toFloat(trim($cols->item(2)->nodeValue));
                $beli   = $this->toFloat(trim($cols->item(3)->nodeValue));
                $tengah = ($jual + $beli) / 2;

                $result = [
                    'jual'   => $jual,
                    'beli'   => $beli,
                    'tengah' => $tengah,
                    // 'jual'   => number_format($jual, 2),
                    // 'beli'   => number_format($beli, 2),
                    // 'tengah' => number_format($tengah, 2),
                ];
            }
        }

        return $result;
    }

    private function toFloat($num)
    {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        }

        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
                preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num)))
        );
    }
}
