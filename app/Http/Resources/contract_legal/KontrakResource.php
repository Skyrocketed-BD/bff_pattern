<?php

namespace App\Http\Resources\contract_legal;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
// use Illuminate\Support\Collection;
class KontrakResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sisa_waktu = Carbon::parse($this->tgl_akhir)->diffInDays(now()->startOfDay(), false); 

        if ($sisa_waktu > 0) {
            $sisa_hari = 0; 
        } else {
            $sisa_hari = abs($sisa_waktu); 
        }
        
        return [
            'id_kontrak'        => $this->id_kontrak,
            'no_kontrak'        => $this->no_kontrak,
            'nama_perusahaan'   => $this->nama_perusahaan,
            'status'            => $this->status,
            'tgl_mulai'         => $this->tgl_mulai,
            'tgl_akhir'         => $this->tgl_akhir,
            'sisa_waktu'        => $sisa_hari,
            'attachment'        => $this->attachment ? asset_upload('file/kontrak/' . $this->attachment) : null,
            'doc_pendukung'     => $this->doc_pendukung ? asset_upload('file/doc_pendukung/' . $this->doc_pendukung) : null,
            'unassigned'        => empty($this->toKontak) ? true : false,
            'lampiran_kontrak'  => $this->toLampiranKontrak->map(function ($lampiran) {
                return [
                    'id_lampiran_kontrak' => $lampiran->id_lampiran_kontrak,
                    'judul'               => $lampiran->judul,
                    'url'                 => asset_upload('file/lampiran_kontrak/' . $lampiran->file)
                ];
            }),
            'tahapan'           => $this->toKontrakTahapan->sortByDesc('id_kontrak_tahapan')->values()->map(function ($tahapan) {
                $tahapanData = [
                    'id_kontrak_tahapan'        => $tahapan->id_kontrak_tahapan,
                    'id_tahapan_k'              => $tahapan->id_tahapan_k,
                    'tahapan'                   => $tahapan->toTahapanK->name,
                    'tgl'                       => $tahapan->tgl,
                    'status'                    => $tahapan->status,
                    'keterangan'                => $tahapan->keterangan,
                    'files'                     => $tahapan->toUploadKontrakTahapan->map(function ($file) {
                        return [
                            'id_upload_kontrak_tahapan' => $file->id_upload_kontrak_tahapan,
                            'judul'                     => $file->judul,
                            'url'                       => asset_upload('file/upload_kontrak_tahapan/' . $file->file)
                        ];
                    })
                ];

                if ($tahapan->id_tahapan_k === 3 || $tahapan->id_tahapan_k === 5) {
                        $tahapanData['revisi'] = $tahapan->toRevisi ? $tahapan->toRevisi->sortByDesc('id_revisi')->values()->map(function ($revisi) {
                            return [
                                'id_revisi'     => $revisi->id_revisi,
                                'revisi_ke'     => $revisi->revisi_ke,
                                'keterangan'    => $revisi->keterangan,
                                'files'         => $revisi->toUploadRevisi->map(function ($file) {
                                    $originalUpload = $file->originalUpload;

                                    return [
                                        'id_upload_revisi'              => $file->id_upload_revisi,
                                        'judul'                         => $originalUpload ? $originalUpload->judul : null,
                                        'url'                           => asset_upload('file/upload_revisi/' . $file->file),
                                        'original_upload_id'            => $file->id_upload_kontrak_tahapan
                                    ];
                                })
                            ];
                        }) : [];
                    }

                    return $tahapanData;
                })
        ];
    }
}

// class KontrakResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id_kontrak' => $this->id_kontrak,
//             'no_kontrak' => $this->no_kontrak,
//             'nama_kontraktor' => $this->nama_kontraktor,
//             'status' => $this->status,
//             'tgl_mulai' => $this->tgl_mulai,
//             'tgl_akhir' => $this->tgl_akhir,
//             'attachment' => asset_upload('file/kontrak/' . $this->attachment),
//             'tahapan' => $this->whenLoaded('toKontrakTahapan', function() {
//                 return $this->toKontrakTahapan
//                     ->sortByDesc('id_kontrak_tahapan')
//                     ->values()
//                     ->map(function ($tahapan) {
//                         $tahapanData = [
//                             'id_kontrak_tahapan' => $tahapan->id_kontrak_tahapan,
//                             'id_tahapan_k' => $tahapan->id_tahapan_k,
//                             'tahapan' => optional($tahapan->toTahapanK)->name,
//                             'tgl' => $tahapan->tgl,
//                             'keterangan' => $tahapan->keterangan,
//                             'files' => $this->handleFiles($tahapan->toUploadKontrakTahapan)
//                         ];

//                         // Only add revisi for specific tahapan types (3 or 5)
//                         if (in_array($tahapan->id_tahapan_k, [3, 5])) {
//                             $tahapanData['revisi'] = $this->handleRevisi($tahapan);
//                         }

//                         return $tahapanData;
//                     });
//             }, [])
//         ];
//     }

//     protected function handleFiles($files)
//     {
//         return collect($files ?: [])
//             ->sortByDesc('id_upload_kontrak_tahapan')
//             ->values()
//             ->map(function ($file) {
//                 return [
//                     'id_upload_kontrak_tahapan' => $file->id_upload_kontrak_tahapan,
//                     'judul' => $file->judul,
//                     'url' => asset_upload('file/upload_kontrak_tahapan/' . $file->file)
//                 ];
//             });
//     }

//     protected function handleRevisi($tahapan)
//     {
//         // Check if the tahapan has revisi relationship
//         if (!method_exists($tahapan, 'revisi') && !method_exists($tahapan, 'toRevisi')) {
//             return [];
//         }

//         // Try both possible relationship names (revisi or toRevisi)
//         $revisiRelation = method_exists($tahapan, 'revisi') ?
//                          $tahapan->revisi :
//                          $tahapan->toRevisi;

//         return collect($revisiRelation ?: [])
//             ->sortByDesc('id_revisi')
//             ->values()
//             ->map(function ($revisi) {
//                 return [
//                     'id_revisi' => $revisi->id_revisi,
//                     'revisi_ke' => $revisi->revisi_ke,
//                     'keterangan' => $revisi->keterangan,
//                     'files' => $this->handleRevisiFiles($revisi)
//                 ];
//             });
//     }

//     protected function handleRevisiFiles($revisi)
//     {
//         // Check if the revisi has file relationship
//         if (!method_exists($revisi, 'files') && !method_exists($revisi, 'toUploadRevisi')) {
//             return [];
//         }

//         // Try both possible relationship names
//         $filesRelation = method_exists($revisi, 'files') ?
//                         $revisi->files :
//                         $revisi->toUploadRevisi;

//         return collect($filesRelation ?: [])
//             ->sortByDesc('id_upload_revisi')
//             ->values()
//             ->map(function ($file) {
//                 $originalUpload = $file->originalUpload ?? null;

//                 return [
//                     'id_upload_revisi' => $file->id_upload_revisi,
//                     'judul' => $originalUpload ? $originalUpload->judul : null,
//                     'url' => asset_upload('file/upload_revisi/' . $file->file),
//                     'original_upload_id' => $file->id_upload_kontrak_tahapan
//                 ];
//             });
//     }
// }
