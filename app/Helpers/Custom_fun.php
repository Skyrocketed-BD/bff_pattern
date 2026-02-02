<?php

use App\Models\main\Arrangement;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

if (!function_exists('generate_number')) {
    function generate_number($database, $table, $key, $kd)
    {
        $datePrefix = Carbon::now()->format('Ymd');
        $full_prefix = "{$kd}-{$datePrefix}";

        $result = DB::connection($database)->table($table)
            ->select(DB::raw("MAX(CAST(SUBSTRING($key, -4) AS UNSIGNED)) AS max_kd"))
            ->whereRaw("LEFT($key, LENGTH('$full_prefix')) = '$full_prefix'")
            ->first();
        $nextNumber = ($result->max_kd ?? 0) + 1;

        $sequentialPart = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return "{$kd}-{$datePrefix}.{$sequentialPart}";
    }
}

if (!function_exists('generate_random_name_file')) {
    function generate_random_name_file($file)
    {
        return uniqid() . '-' . date('YmdHi') . '.' . $file->extension();
    }
}

if (!function_exists('add_file')) {
    function add_file($request, $location, $prefix = null)
    {
        // nama
        $file = !empty($prefix)
            ? $prefix . "-" . generate_random_name_file($request)
            : generate_random_name_file($request);

        // upload
        $request->move(upload_path('file/' . $location), $file);

        return $file;
    }
}

if (!function_exists('upd_file')) {
    function upd_file($request, $file_name, $location, $prefix = null)
    {
        del_file($file_name, $location);

        $file = add_file($request, $location, $prefix);

        return $file;
    }
}

if (!function_exists('del_file')) {
    function del_file($file_name, $location)
    {
        $file = upload_path('file/' . $location . $file_name);

        // hapus
        if (File::exists($file)) {
            File::delete($file);
        };
    }
}

if (!function_exists('remainder')) {
    function remainder($array, $from = 100)
    {
        if (count($array) > 1) {
            foreach ($array as $key => $value) {
                $from = $from - $value;
            }
        } else {
            $from = 0;
        }

        return $from;
    }
}

if (!function_exists('rupiah')) {
    function rupiah($harga)
    {
        return 'Rp. ' . create_separator($harga) . ',-';
    }
}

if (!function_exists('remove_separator')) {
    function remove_separator($harga)
    {
        return str_replace('.', '', $harga);
    }
}

if (!function_exists('create_separator')) {
    function create_separator($harga)
    {
        return number_format($harga, 0, ',', '.');
    }
}

if (!function_exists('get_arrangement')) {
    function get_arrangement($key)
    {
        try {
            $data = Arrangement::where('key', $key)->first();

            return $data->value;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('start_date_month')) {
    function start_date_month($date)
    {
        $begin = Carbon::now();

        return $date ?? $begin->startOfMonth()->format('Y-m-d');
    }
}

if (!function_exists('end_date_month')) {
    function end_date_month($date)
    {
        $end = Carbon::now();

        return $date ?? $end->format('Y-m-d');
    }
}

if (!function_exists('angka_romawi')) {
    function angka_romawi($number)
    {
        $map = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1
        ];

        $result = '';
        foreach ($map as $roman => $value) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }
        return $result;
    }
}

if (!function_exists('count_month_by_cut_off')) {
    function count_month_by_cut_off($start_date, $end_date)
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $start_date);
        $endDate   = Carbon::createFromFormat('Y-m-d', $end_date);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $count = 0;
        foreach ($dateRange as $key => $value) {
            if ($value->format('d') == get_arrangement('cutoff_date')) {
                $count++;
            }
        }

        return $count;
    }
}

if (!function_exists('count_cut_off')) {
    function count_cut_off($start_date, $end_date)
    {
        $startDate  = Carbon::createFromFormat('Y-m-d', $start_date);
        $endDate    = Carbon::createFromFormat('Y-m-d', $end_date);

        // Ambil tanggal cutoff
        $cutoffDate = (int)get_arrangement('cutoff_date');
        if ($cutoffDate < 1 || $cutoffDate > 31) {
            throw new InvalidArgumentException('Cutoff date must be between 1 and 31.');
        }

        // Pindahkan $startDate ke cutoff berikutnya jika belum melewati cutoff bulan ini
        if ($startDate->day > $cutoffDate) {
            $startDate->addMonth();
        }
        $startDate->day = $cutoffDate; //simpan cutOff awal

        // Pastikan $endDate dihitung sampai cutoff bulan terakhir
        if ($endDate->day < $cutoffDate) {
            $endDate->subMonth();
        }
        $endDate->day = $cutoffDate; //simpan cutOff akhir

        // Hitung jumlah bulan di antara cutoff start dan cutoff end
        if ($startDate > $endDate) {
            return 0; // Tidak ada cutoff yang dilewati
        }

        return $startDate->diffInMonths($endDate) + 1;
    }
}


if (!function_exists('calculate_percentage')) {
    function calculate_percentage($previous, $current, $simple = true)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        if ($simple) {
            $percent = ($current / $previous) * 100;
        } else {
            $percent = (($current - $previous) / $previous) * 100;
        }
        return $percent;
    }
}

// simpan ini buat jaga-jaga, sapa tau mau otomatis ada 2 digit decimal
// if (!function_exists('locale_currency')) {
//     function locale_currency(float $amount, string $currency = 'IDR' ): string
//     {
//         $locale = 'en_US'; //ini harusnya nanti ambil dari arrangement
//         $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
//         return $formatter->formatCurrency($amount, $currency);
//     }
// }


// if (!function_exists('locale_currency')) {
//     function locale_currency(float $amount, string $currency = 'ID', ?int $decimals = null): string
//     {
//         $locale = 'en_US'; //ini harusnya nanti ambil dari arrangement
//         $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

//         if ($decimals === null) {
//             $decimals = fmod($amount, 1) > 0 ? 2 : 0; //walau decimal tidak diset, tapi kalau amountnya ada decimal, maka tetap tampilkan decimal
//         }

//         $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

//         if ($currency === 'ID') {
//             $formatter->setTextAttribute(NumberFormatter::CURRENCY_SYMBOL, 'Rp');
//             return str_replace('IDR', 'Rp', $formatter->formatCurrency($amount, 'IDR'));
//         }

//         if ($currency === 'IDR') {
//             $formatter->setTextAttribute(NumberFormatter::CURRENCY_SYMBOL, 'Rp');
//         }


//         return $formatter->formatCurrency($amount, $currency);
//     }
// }

if (!function_exists('locale_currency')) {
    function locale_currency(float $amount, string $currency = 'ID', ?int $decimals = null): string
    {
        // tentukan decimal otomatis
        if ($decimals === null) {
            $decimals = fmod($amount, 1) > 0 ? 2 : 0;
        }

        // currency Indonesia
        if ($currency == 'ID') {
            $formatter = new NumberFormatter('id_ID', NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

            return 'Rp' . $formatter->format($amount);
        }

        if ($currency == 'IDR') {
            $formatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

            return 'IDR' . $formatter->format($amount);
        }

        // currency USD (default internasional)
        if ($currency === 'USD') {
            $formatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

            return '$' . $formatter->format($amount);
        }

        // fallback untuk currency lain
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

        return $formatter->formatCurrency($amount, $currency);
    }
}


//simpan dulu punya ryan
// if (!function_exists('locale_currency')) {
//     function locale_currency(float $amount, string $currency_code = 'IDR', ?int $decimals = null): string
//     {
//         $currency_code = strtoupper($currency_code); // Standardize
//         $effective_locale = 'en_US'; // Default for non-IDR currencies
//         $display_currency = $currency_code;

//         if ($currency_code === 'ID' || $currency_code === 'IDR') {
//             $effective_locale = 'id_ID'; // Indonesian locale
//             $display_currency = 'IDR';   // Standard ISO code for NumberFormatter
//         }
//         // Add other elseif conditions here if you want specific locales for other currencies

//         $formatter = new NumberFormatter($effective_locale, NumberFormatter::CURRENCY);

//         if ($decimals === null) {
//             $decimals = fmod($amount, 1) !== 0.00 ? 2 : 0;
//         }
//         $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

//         return $formatter->formatCurrency($amount, $display_currency);
//     }
// }

if (!function_exists('locale_number')) {
    function locale_number(float $number): string
    {
        $locale = 'en_US'; //ini harusnya nanti ambil dari arrangement
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        return $formatter->format($number);
    }
}

if (!function_exists('normalizeNumber')) {
    function normalizeNumber($input)
    {
        // Hilangkan spasi
        $input = trim($input);

        // Deteksi apakah format Eropa (1.000,12) atau format US (1,000.12)
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $input)) {
            // Format Eropa: ganti titik (.) jadi kosong dan koma (,) jadi titik
            $input = str_replace('.', '', $input);
            $input = str_replace(',', '.', $input);
        } elseif (preg_match('/^\d{1,3}(,\d{3})*(\.\d+)?$/', $input)) {
            // Format US: ganti koma (,) jadi kosong
            $input = str_replace(',', '', $input);
        } else {
            // Jika input sudah berupa angka tanpa pemisah (misalnya 3121948875), langsung return sebagai float
            if (is_numeric($input)) {
                return (float)$input;
            } else {
                // Tangani kasus tidak normal seperti 1.12,12 atau 1,12.12
                // Ganti titik jadi koma
                $input = str_replace('.', ',', $input);
                // Pisah berdasarkan koma
                $bagian = explode(',', $input);
                // Ambil elemen terakhir
                $terakhir = end($bagian);
                // Tambahkan ke hasil
                $input = str_replace(['.', ','], '', $input); // Hapus semua pemisah
                // Coba tambahkan titik desimal di dua/lebih digit terakhir jika ada
                if (preg_match('/\d{3,}$/', $input)) {
                    $length = strlen($terakhir);

                    $input = substr_replace($input, '.', - ($length), 0);
                }
            }
        }

        return (float)$input;
    }
}

if (!function_exists('connection_exist')) {
    function connection_exist($connectionName)
    {
        $connections = Config::get('database.connections');

        if (!isset($connections[$connectionName])) {
            echo "Connection '$connectionName' is not defined in config.\n";
            return false;
        }

        try {
            DB::connection($connectionName)->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('count_days')) {
    function count_days($start_date, $end_date)
    {
        $startDate = Carbon::parse($start_date);
        $endDate   = Carbon::parse($end_date);

        return $startDate->diffInDays($endDate);
    }
}

if (!function_exists('sanitize_npwp')) {
    /**
     * Sanitize NPWP by removing dots and dashes
     *
     * @param  string  $npwp
     * @return string
     */
    function sanitize_npwp($npwp)
    {
        return str_replace(['.', '-'], '', $npwp);
    }
}

if (!function_exists('snake_case')) {
    function snake_case($value)
    {
        return \Illuminate\Support\Str::snake($value);
    }
}
