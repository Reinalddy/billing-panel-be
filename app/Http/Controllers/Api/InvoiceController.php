<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function invoices(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);

            $invoices = Invoice::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'code' => 200,
                'data' => $invoices,
            ]);
        } catch (Exception $e) {
            Log::error('Get Invoices Error', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function invoice_detail(Request $request, $id)
    {
        try {
            $invoice = Invoice::with([
                'items',
                'service',
            ])
                ->where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Invoice tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'code' => 200,
                'data' => $invoice,
            ]);
        } catch (Exception $e) {
            Log::error('Get Invoice Detail Error', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function invoice_pay(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $invoice = Invoice::with('service')
                ->where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (! $invoice) {
                DB::rollBack();
                return response()->json([
                    'code' => 404,
                    'message' => 'Invoice tidak ditemukan',
                ], 404);
            }

            if ($invoice->status === 'paid') {
                DB::rollBack();
                return response()->json([
                    'code' => 400,
                    'message' => 'Invoice sudah dibayar',
                ], 400);
            }

            // 1️⃣ Update invoice
            $invoice->update([
                'status'   => 'paid',
                'paid_at'  => now(),
            ]);

            $service = $invoice->service;

            // 2️⃣ Aktifkan service
            $startDate = Carbon::now();
            $endDate   = $startDate->copy()->addMonths($invoice->duration_month);

            $service->update([
                'status'     => 'active',
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ]);

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => 'Invoice berhasil dibayar, service aktif',
                'data' => [
                    'invoice_status' => $invoice->status,
                    'service_status' => $service->status,
                    'start_date'     => $service->start_date,
                    'end_date'       => $service->end_date,
                ],
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Pay Invoice Error', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
