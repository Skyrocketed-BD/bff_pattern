<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\JournalRequest;
use App\Http\Resources\finance\JournalResource;
use App\Models\finance\Journal;
use App\Models\finance\JournalSet;
use App\Models\finance\TaxCoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class JournalController extends Controller
{
    /**
     * @OA\Get(
     *  path="/journals",
     *  summary="Get the list of journals",
     *  tags={"Finance - Journal"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = Journal::orderBy('name', 'asc')->get();

        return ApiResponseClass::sendResponse(JournalResource::collection($data), 'Journals Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/journals",
     *  summary="Create a new journal",
     *  tags={"Finance - Journal"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Journal Name"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Journal Category (penerimaan, pengeluaran, transfer)"
     *              ),
     *              @OA\Property(
     *                  property="alocation",
     *                  type="string",
     *                  description="Journal Alocation (bank, cash, petty_cash, invoice, transfer)"
     *              ),
     *              @OA\Property(
     *                  property="is_outstanding",
     *                  type="string",
     *                  description="Is Outstanding? (1 or 0)"
     *              ),
     *              @OA\Property(
     *                  property="id_tax_rate",
     *                  type="array",
     *                  @OA\Items(type="integer", example=1),
     *                  description="Tax Rate ID"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="array",
     *                  @OA\Items(type="integer", example=1),
     *                  description="COA ID"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="D or K"
     *              ),
     *              @OA\Property(
     *                  property="open_input",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="y or n"
     *              ),
     *              example={
     *                  "name": "Harian",
     *                  "category": "penerimaan",
     *                  "alocation": "bank",
     *                  "is_outstanding": "0",
     *                  "id_tax_rate": {null, null, 1},
     *                  "id_coa": {1, 2, 3},
     *                  "type": {"D", "K", "K"},
     *                  "open_input": {"n", "n", "n"}
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(JournalRequest $request)
    {
        $id_coa      = $request->id_coa;
        $id_tax_rate = $request->id_tax_rate;
        $type        = $request->type;
        $open_input  = $request->open_input;

        if (count($id_coa) < 2) {
            return Response::json(['success' => false, 'message' => 'Maaf, Jurnal harus memiliki minimal 2 COA!'], 400);
        } else {
            $debit  = [];
            $credit = [];

            if (count($id_coa) == 2) {
                foreach ($id_coa as $key => $value) {
                    if ($type[$key] == "D") {
                        $debit[] = 1;
                    } else {
                        $credit[] = 1;
                    }
                }
            } else {
                foreach ($id_coa as $key => $value) {
                    $coa_tax = TaxCoa::where('id_coa', $value)->first();

                    if ($type[$key] == "D") {
                        if ($coa_tax) {
                            $debit[] = 1;
                        } else if ($open_input[$key] == "y") {
                            $debit[] = 1;
                        } else {
                            $debit[] = 0;
                        }
                    } else {
                        if ($coa_tax) {
                            $credit[] = 1;
                        } else if ($open_input[$key] == "y") {
                            $credit[] = 1;
                        } else {
                            $credit[] = 0;
                        }
                    }
                }
            }

            if (count($debit) == 1 && count($credit) > 1) {
                if (!(in_array(1, $credit) && count(array_keys($credit, 0)) == 1)) {
                    return ApiResponseClass::throw('Maaf, Jurnal tidak valid!', 400);
                }
            }

            if (count($debit) > 1 && count($credit) == 1) {
                if (!(in_array(1, $debit) && count(array_keys($debit, 0)) == 1)) {
                    return ApiResponseClass::throw('Maaf, Jurnal tidak valid!', 400);
                }
            }

            if (count($debit) > 1 && count($credit) > 1) {
                if (!((in_array(1, $debit) && count(array_keys($debit, 0)) == 1) && (in_array(1, $credit) && count(array_keys($credit, 0)) == 1))) {
                    return ApiResponseClass::throw('Maaf, Jurnal tidak valid!', 400);
                }
            }

            DB::connection('finance')->beginTransaction();
            try {
                $journal = new Journal();
                $journal->name           = $request->name;
                $journal->category       = $request->category;
                $journal->alocation      = $request->alocation;
                $journal->is_outstanding = $request->is_outstanding;
                $journal->save();

                $serial_number = 1;

                foreach ($id_coa as $key => $value) {
                    $data[] = [
                        'id_journal'    => $journal->id_journal,
                        'id_tax_rate'   => $id_tax_rate[$key],
                        'id_coa'        => $id_coa[$key],
                        'type'          => $type[$key],
                        'open_input'    => $open_input[$key],
                        'serial_number' => $serial_number++,
                        'created_by'    => auth('api')->user()->id_users
                    ];
                }

                JournalSet::insert($data);

                ActivityLogHelper::log('finance:journal_create', 1, [
                    'finance:journal_name' => $journal->name,
                    'category'             => $journal->alocation,
                    'finance:alocation'    => $journal->category,
                    'finance:outstanding'  => $journal->is_outstanding ? 'Yes' : 'No'
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($data, 'Journal Created Successfully');
            } catch (\Exception $e) {
                ActivityLogHelper::log('finance:journal_create', 0, ['error' => $e->getMessage()]);
                return ApiResponseClass::rollback($e);
            }
        }
    }

    /**
     * @OA\Put(
     *  path="/journals/{id_journal}",
     *  summary="Update a journal",
     *  tags={"Finance - Journal"},
     *  @OA\Parameter(
     *      name="id_journal",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          example="1"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Journal Name"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Journal Category (penerimaan, pengeluaran, transfer)"
     *              ),
     *              @OA\Property(
     *                  property="alocation",
     *                  type="string",
     *                  description="Journal Alocation (bank, cash, petty_cash, invoice, transfer)"
     *              ),
     *              @OA\Property(
     *                  property="is_outstanding",
     *                  type="integer",
     *                  description="Is Outstanding? (1 or 0)"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="array",
     *                  @OA\Items(type="integer", example=1),
     *                  description="COA ID"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="D or K"
     *              ),
     *              @OA\Property(
     *                  property="open_input",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="y or n"
     *              ),
     *              example={
     *                  "name": "Harian",
     *                  "category": "penerimaan",
     *                  "alocation": "bank",
     *                  "id_tax_rate": {null, null, 1},
     *                  "id_coa": {1, 2, 3},
     *                  "type": {"D", "K", "K"},
     *                  "open_input": {"n", "n", "n"}
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(JournalRequest $request, $id_journal)
    {
        $id_coa      = $request->id_coa;
        $id_tax_rate = $request->id_tax_rate;
        $type        = $request->type;
        $open_input  = $request->open_input;

        if (count($id_coa) < 2) {
            return ApiResponseClass::throw('Maaf, Jurnal harus memiliki minimal 2 COA!', 400);
        } else {
            $debit  = [];
            $credit = [];

            if (count($id_coa) == 2) {
                foreach ($id_coa as $key => $value) {
                    if ($type[$key] == "D") {
                        $debit[] = 1;
                    } else {
                        $credit[] = 1;
                    }
                }
            } else {
                foreach ($id_coa as $key => $value) {
                    $coa_tax   = TaxCoa::where('id_coa', $value)->first();

                    if ($type[$key] == "D") {
                        if ($coa_tax) {
                            $debit[] = 1;
                        } else if ($open_input[$key] == "y") {
                            $debit[] = 1;
                        } else {
                            $debit[] = 0;
                        }
                    } else {
                        if ($coa_tax) {
                            $credit[] = 1;
                        } else if ($open_input[$key] == "y") {
                            $credit[] = 1;
                        } else {
                            $credit[] = 0;
                        }
                    }
                }
            }

            if (count($debit) == 1 && count($credit) > 1) {
                if (!(in_array(1, $credit) && count(array_keys($credit, 0)) == 1)) {
                    return ApiResponseClass::throw('Maaf, Jurnal tidak valid!', 400);
                }
            }

            if (count($debit) > 1 && count($credit) == 1) {
                if (!(in_array(1, $debit) && count(array_keys($debit, 0)) == 1)) {
                    return ApiResponseClass::throw('Maaf, Jurnal tidak valid!', 400);
                }
            }

            if (count($debit) > 1 && count($credit) > 1) {
                if (!((in_array(1, $debit) && count(array_keys($debit, 0)) == 1) && (in_array(1, $credit) && count(array_keys($credit, 0)) == 1))) {
                    return ApiResponseClass::throw('Maaf, Jurnal tidak valid!', 400);
                }
            }

            DB::connection('finance')->beginTransaction();
            try {
                $journal = Journal::find($id_journal);

                $journal->update([
                    'name'           => $request->name,
                    'category'       => $request->category,
                    'alocation'      => $request->alocation,
                    'is_outstanding' => $request->is_outstanding,
                ]);

                JournalSet::whereIdJournal($id_journal)->delete();

                $serial_number = 1;

                foreach ($id_coa as $key => $value) {
                    $data[] = [
                        'id_journal'    => $id_journal,
                        'id_tax_rate'   => $id_tax_rate[$key],
                        'id_coa'        => $id_coa[$key],
                        'type'          => $type[$key],
                        'open_input'    => $open_input[$key],
                        'serial_number' => $serial_number++,
                        'created_by'    => auth('api')->user()->id_users
                    ];
                }

                JournalSet::insert($data);

                ActivityLogHelper::log('finance:journal_update', 1, [
                    'finance:journal_name' => $journal->name,
                    'category'             => $journal->alocation,
                    'finance:alocation'    => $journal->category,
                    'finance:outstanding'  => $journal->is_outstanding ? 'Yes' : 'No'
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($data, 'Journal Created Successfully');
            } catch (\Exception $e) {
                ActivityLogHelper::log('finance:journal_update', 0, ['error' => $e->getMessage()]);
                return ApiResponseClass::rollback($e);
            }
        }
    }

    /**
     * @OA\Delete(
     *  path="/journals/{id_journal}",
     *  summary="Delete a journal",
     *  tags={"Finance - Journal"},
     *  @OA\Parameter(
     *      name="id_journal",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          example="1"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id_journal)
    {
        try {
            $data = Journal::find($id_journal);

            $data->delete();

            ActivityLogHelper::log('finance:journal_delete', 1, [
                'finance:journal_name' => $data->name,
                'category'             => $data->category,
                'finance:alocation'    => $data->alocation,
                'finance:outstanding'  => $data->is_outstanding ? 'Yes' : 'No'
            ]);

            return ApiResponseClass::sendResponse($data, 'Journal Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:journal_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *  path="/journals/filter/{type}",
     *  summary="Filter Journal",
     *  tags={"Finance - Journal"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"penerimaan", "pengeluaran"}
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     *
     * @OA\Get(
     *  path="/journals/filter/{type}/{alocation}/{is_outstanding}",
     *  summary="Filter Journal",
     *  tags={"Finance - Journal"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"penerimaan", "pengeluaran"}
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="alocation",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"bank", "cash", "petty_cash"}
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="is_outstanding",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          enum={0, 1}
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function filter($type, $alocation = null, $is_outstanding = null)
    {
        $query = Journal::query();

        if ($alocation) {
            $query->where('alocation', $alocation);
        }

        if ($is_outstanding !== null) {
            $query->where('is_outstanding', $is_outstanding);
        }

        $data = $query->where('category', $type)->orderBy('name')->get();

        $result = [];

        foreach ($data as $key => $value) {
            $result[] = [
                'id_journal'     => $value->id_journal,
                'name'           => $value->name,
                'category'       => $value->category,
                'alocation'      => $value->alocation,
                'is_outstanding' => $value->is_outstanding,
                // 'is_tax'         => 
            ];
        }

        return ApiResponseClass::sendResponse($result, 'Transaction Type Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/journals/filter-outstanding/{type}/{is_outstanding}",
     *  summary="Filter Journal WOutstanding",
     *  tags={"Finance - Journal"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"penerimaan", "pengeluaran"}
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="is_outstanding",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          enum={0, 1}
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function filterWOutstanding($type, $is_outstanding)
    {
        $query = Journal::query();

        $query->with(['toJournalSet']);

        if ($is_outstanding !== null) {
            $query->where('is_outstanding', $is_outstanding);
        }

        $data = $query->where('category', $type)->orderBy('name')->get();

        $result = [];

        foreach ($data as $key => $value) {
            $is_tax = false;

            if ($value->toJournalSet->count() > 0) {
                // cek apakah ada salah satu yang punya id_tax_rate != null
                $is_tax = $value->toJournalSet->contains(function ($set) {
                    return $set->id_tax_rate !== null;
                }) ? true : false;
            }

            $result[] = [
                'id_journal'     => $value->id_journal,
                'name'           => $value->name,
                'category'       => $value->category,
                'alocation'      => $value->alocation,
                'is_outstanding' => $value->is_outstanding,
                'is_tax'         => $is_tax,
            ];
        }

        return ApiResponseClass::sendResponse($result, 'Transaction Type Retrieved Successfully');
    }
}
