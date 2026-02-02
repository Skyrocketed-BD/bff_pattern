<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\PdfClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\main\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

class MapPrintController extends Controller
{
    public function all(Request $request)
    {
        $title          = 'PETA WILAYAH IUP';
        $current        = Carbon::now()->format('YmdHis');

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();
        $company        = get_arrangement('company_name');
        $username       = User::find(auth()->id())->name ?? 'invalid user';

        $file_name = str_replace(' ', '', $title) . '-' . $current . '.xlsx';
        $image = $request->file('mapImage');
        $imageData = base64_encode($image->getContent());
        $mimeType = $image->getMimeType();

        $data = [
            'title'         => $title,
            'company'       => $company,
            'printed_date'  => $printed_date,
            'printed_by'    => $username,
            'imageData'     => $imageData,
            'mimeType'      => $mimeType,
        ];

        $pdfOutput = PdfClass::print($data['title'], 'operation.map', 'A4', 'potrait', $data);

        $fileName = str_replace(' ', '', $data['title']) . '-' . now()->format('YmdHis') . '.pdf';

        ActivityLogHelper::log('operation:map_print', 1, [
            'title'         => $data['title'],
            'company'       => $data['company'],
            'printed_date'  => $data['printed_date'],
            'printed_by'    => $data['printed_by'],
        ]);

        return response($pdfOutput, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename=' . $fileName);
    }

}
