<?php

use App\Models\operation\StokEfo;
use App\Models\operation\StokEto;
use Carbon\Carbon;

if (!function_exists('sumProductArray')) {
    function sumProductArray(array $array1, array $array2)
    {
        return array_sum(array_map(fn($a, $b) => $a * $b, $array1, $array2));
    }
}

if (!function_exists('_getStokEtoDetail')) {
    function _getStokEtoDetail($id_stok_eto)
    {
        $stok_eto = StokEto::with(['toStokEtoDetail'])->whereIdStokEto($id_stok_eto)->first();

        $ni     = [];
        $fe     = [];
        $co     = [];
        $sio2   = [];
        $mgo2   = [];
        $tonage = [];
        $ritasi = [];
        foreach ($stok_eto->toStokEtoDetail as $key => $value) {
            $ni[]     = $value->ni;
            $fe[]     = $value->fe;
            $co[]     = $value->co;
            $sio2[]   = $value->sio2;
            $mgo2[]   = $value->mgo2;
            $tonage[] = $value->tonage;
            $ritasi[] = $value->ritasi;
        }

        $count_ni   = (sumProductArray($ni, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($ni, $tonage) / array_sum($tonage)), 2);
        $count_fe   = (sumProductArray($fe, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($fe, $tonage) / array_sum($tonage)), 2);
        $count_co   = (sumProductArray($co, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($co, $tonage) / array_sum($tonage)), 2);
        $count_sio2 = (sumProductArray($sio2, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($sio2, $tonage) / array_sum($tonage)), 2);
        $count_mgo2 = (sumProductArray($mgo2, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($mgo2, $tonage) / array_sum($tonage)), 2);

        return [
            'id_dom_eto' => $stok_eto->id_dom_eto,
            'ni'         => $count_ni,
            'fe'         => $count_fe,
            'co'         => $count_co,
            'sio2'       => $count_sio2,
            'mgo2'       => $count_mgo2,
            'tonage'     => array_sum($tonage),
            'ritasi'     => array_sum($ritasi)
        ];
    }
}

if (!function_exists('_getStokEfoDetail')) {
    function _getStokEfoDetail($id_stok_efo)
    {
        $stok_efo = StokEfo::with(['toStokEfoDetail'])->whereIdStokEfo($id_stok_efo)->first();

        $ni     = [];
        $fe     = [];
        $co     = [];
        $sio2   = [];
        $mgo2   = [];
        $tonage = [];
        $ritasi = [];
        foreach ($stok_efo->toStokEfoDetail as $key => $value) {
            $ni[]     = $value->ni;
            $fe[]     = $value->fe;
            $co[]     = $value->co;
            $sio2[]   = $value->sio2;
            $mgo2[]   = $value->mgo2;
            $tonage[] = $value->tonage;
            $ritasi[] = $value->ritasi;
        }

        $count_ni   = (sumProductArray($ni, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($ni, $tonage) / array_sum($tonage)), 2);
        $count_fe   = (sumProductArray($fe, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($fe, $tonage) / array_sum($tonage)), 2);
        $count_co   = (sumProductArray($co, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($co, $tonage) / array_sum($tonage)), 2);
        $count_sio2 = (sumProductArray($sio2, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($sio2, $tonage) / array_sum($tonage)), 2);
        $count_mgo2 = (sumProductArray($mgo2, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($mgo2, $tonage) / array_sum($tonage)), 2);

        return [
            'ni'     => $count_ni,
            'fe'     => $count_fe,
            'co'     => $count_co,
            'sio2'   => $count_sio2,
            'mgo2'   => $count_mgo2,
            'tonage' => array_sum($tonage),
            'ritasi' => array_sum($ritasi)
        ];
    }
}

if (!function_exists('generateUniqueInitials')) {
    function generateUniqueInitials(string $database, string $name, string $table, string $column): string
    {
        $words = preg_split('/[\s-]+/', strtoupper(trim($name)));

        if (in_array($words[0], ['PT', 'CV', 'PT.', 'CV.'])) {
            array_shift($words);
        }

        $alternatives = [];

        if (count($words) >= 3) {
            $alternatives[] = $words[0][0] . $words[1][0] . $words[2][0];
            $alternatives[] = $words[0][0] . $words[1][0] . $words[1][1];
            $alternatives[] = $words[0][0] . $words[2][0] . $words[1][0];
        }

        if (count($words) === 2) {
            $alternatives[] = substr($words[0], 0, 2) . $words[1][0];
            $alternatives[] = $words[0][0] . substr($words[1], 0, 2);
        }

        if (count($words) === 1) {
            $alternatives[] = substr($words[0], 0, 3);
            $alternatives[] = $words[0][0] . $words[0][1] . $words[0][2];
        }

        foreach ($alternatives as $initials) {
            if (!DB::connection($database)->table($table)->where($column, $initials)->exists()) {
                return $initials;
            }
        }

        for ($i = 1; $i < strlen($name) - 2; $i++) {
            $newInitials = strtoupper($name[$i] . $name[$i + 1] . $name[$i + 2]);
            if (!DB::connection($database)->table($table)->where($column, $newInitials)->exists()) {
                return $newInitials;
            }
        }

        return strtoupper(substr(md5($name), 0, 3));
    }

    if (!function_exists('generateDomeNumber')) {
        function generateDomeNumber($database, $table, $id_kontraktor, $key, $kd)
        {
            $year   = Carbon::now()->format('Y');
            $ymin   = Carbon::now()->format('y');
            $full_prefix = "{$kd}{$ymin}-";

            $result = DB::connection($database)->table($table)
                ->where('id_kontraktor', $id_kontraktor)
                // ->select(DB::raw("MAX(CAST(SUBSTRING($key, -4) AS UNSIGNED)) AS max_kd")) //error ini kalau bukan 4 digit dari kanan
                ->select(DB::raw("MAX(CAST(SUBSTRING($key, LENGTH('$full_prefix') + 1) AS UNSIGNED)) AS max_kd"))
                ->whereRaw("LEFT($key, LENGTH('$full_prefix')) = '$full_prefix'")
                ->whereYear('created_at', $year)
                ->first();
            $nextNumber = ($result->max_kd ?? 0) + 1;

            $sequentialPart = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            return "{$kd}{$ymin}-{$sequentialPart}";
        }
    }

}

if (!function_exists('generateSINumber')) {
    function generateSINumber(string $id_kontraktor, string $contractor_initial): string
    {
        $year   = Carbon::now()->format('Y');
        $month  = angka_romawi(Carbon::now()->format('m'));
        $company_initial = get_arrangement('company_initial');
        $key = 'number_si';

        // 001/044-STU/SI/SSP/I/2023
        // {0001/0044-$contractor_initial/SI/$company_initial/$month/$year}

        $last_global = DB::connection('operation')
            ->table('shipping_instructions')
            ->select(DB::raw("MAX(CAST(SUBSTRING_INDEX($key, '/', 1) AS UNSIGNED)) AS max_global"))
            ->whereYear('created_at', $year)
            ->first();
        $globalNumber = ($last_global->max_global ?? 0) + 1;

        $last_contractor = DB::connection('operation')
            ->table('shipping_instructions')
            ->select(DB::raw("MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX($key, '/', 2), '/', -1) AS UNSIGNED)) AS max_contractor"))
            ->where('id_kontraktor', $id_kontraktor)
            ->whereYear('created_at', $year)
            ->first();
        $contractorNumber = ($last_contractor->max_contractor ?? 0) + 1;

        $formattedGlobal = str_pad($globalNumber, 4, '0', STR_PAD_LEFT);
        $formattedContractor = str_pad($contractorNumber, 4, '0', STR_PAD_LEFT);

        return "{$formattedGlobal}/{$formattedContractor}-{$contractor_initial}/SI/{$company_initial}/{$month}/{$year}";
    }
}
