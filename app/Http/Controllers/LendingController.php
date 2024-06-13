<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InboundStuff;
use App\Models\Lending;
use App\Models\StuffStock;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ApiFormatter;
use App\Models\Restoration;

class LendingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $data = lending::with('stuff', 'user', 'stuff.Stock', 'restorations')->get();
        return ApiFormatter::sendResponse(200, true, 'Berhasil melihat semua data lending', $data);
    }

    public function store(Request $request)
    {
        try {

            $this->validate($request, [
                'stuff_id' => 'required',
                'date_time' => 'required',
                'name' => 'required',
                'total_stuff' => 'required',
            ]);

            $totalAvailable = StuffStock::where('stuff_id', $request->stuff_id)->value('total_available');

            if (is_null($totalAvailable)) {
                return ApiFormatter::sendResponse(400, 'bad request', 'belum ada data inbound!');
            } elseif ((int) $request->total_stuff > (int)$totalAvailable) {
                return ApiFormatter::sendResponse(400, 'bad request', 'stok kosong');
            } else {
                $lending = Lending::create([
                    'stuff_id' =>  $request->stuff_id,
                    'date_time' => $request->date_time,
                    'name' => $request->name,
                    'notes' => $request->notes ? $request->notes : '_',
                    'total_stuff' => $request->total_stuff,
                    'user_id' => auth()->user()->id,
                ]);

                $totalAvailableNow = (int)$totalAvailable  - (int)$request->total_stuff;
                $stuffStock = StuffStock::where('stuff_id', $request->stuff_id)->update(['total_available' => $totalAvailableNow]);

                $dataLending = Lending::where('id', $lending['id'])->with('user', 'stuff', 'stuff.Stock')->first();

                return ApiFormatter::sendResponse(200, true, 'success', $dataLending);
            }
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, false, 'bad request', $err->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $inbound = lending::find($id)->delete();

            return ApiFormatter::sendResponse(200, 'success', 'lending berhasil dihapus.');
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $data = Lending::where('id', $id)->with('user', 'restorations', 'restorations.user', 'stuff', 'stuff.Stock')->first();
            return ApiFormatter::sendResponse(200, true, 'success', $data);
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, false, 'bad request', $err->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            lending::where('id', $id)->update([
                'name' => $request->name,
                'notes' => $request->notes ? $request->notes : '_',
                'total_stuff' => $request->total_stuff,
            ]);

            return ApiFormatter::sendResponse(200, true, "Berhasil Mengubah Data lending dengan id $id", ['id' => $id]);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal silahkan coba lagi", $th->getMessage());
        }
    }
}
