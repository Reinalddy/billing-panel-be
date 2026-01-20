<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductDuration;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ServiceController extends Controller
{
    public function services(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);

            $services = Service::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'code' => 200,
                'data' => $services,
            ]);
        } catch (Throwable $e) {
            Log::error('Get Services Error', [
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

    public function checkout_preview(Request $request) 
    {
        try {
            $data = $request->validate([
                'product_id'          => 'required|exists:products,id',
                'product_duration_id' => 'required|exists:product_durations,id',
            ]);

            $duration = ProductDuration::where('id', $data['product_duration_id'])
                ->where('product_id', $data['product_id'])
                ->where('is_active', true)
                ->first();

            if (!$duration) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Durasi tidak valid untuk product ini',
                ], 400);
            }

            $total = $duration->price_per_month * $duration->duration_month;

            return response()->json([
                'code' => 200,
                'data' => [
                    'product_id'       => $duration->product_id,
                    'duration_month'   => $duration->duration_month,
                    'price_per_month'  => $duration->price_per_month,
                    'total'            => $total,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Checkout Preview Error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }

    }

    public function checkout(Request $request)
    {
        DB::beginTransaction();
        try {
            // 1️⃣ Validate input
            $data = $request->validate([
                'product_id'          => 'required|exists:products,id',
                'product_duration_id' => 'required|exists:product_durations,id',
            ]);

            // 2️⃣ Ambil product
            $product = Product::where('id', $data['product_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                DB::rollBack();

                return response()->json([
                    'code' => 404,
                    'message' => 'Product tidak ditemukan atau tidak aktif',
                ], 404);
            }

            // 3️⃣ Ambil duration & validasi relasi
            $productDuration = ProductDuration::where('id', $data['product_duration_id'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$productDuration) {
                DB::rollBack();

                return response()->json([
                    'code' => 400,
                    'message' => 'Durasi tidak valid untuk product ini',
                ], 400);
            }

            // 4️⃣ Hitung harga (SOURCE OF TRUTH)
            $durationMonth  = $productDuration->duration_month;
            $pricePerMonth  = $productDuration->price_per_month;
            $total          = $pricePerMonth * $durationMonth;

            // 5️⃣ Buat service (BELUM AKTIF)
            $service = Service::create([
                'user_id'      => $request->user()->id,
                'service_name' => $product->name,
                'price'        => $pricePerMonth, // simpan harga per bulan yg dipakai
                'start_date'   => null,
                'end_date'     => null,
                'status'       => 'pending',
            ]);

            // 6️⃣ Buat invoice
            $invoice = Invoice::create([
                'user_id'        => $request->user()->id,
                'service_id'     => $service->id,
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'duration_month' => $durationMonth,
                'subtotal'       => $total,
                'total'          => $total,
                'status'         => 'unpaid',
            ]);

            // 7️⃣ Invoice item
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "New Service {$product->name} - {$durationMonth} Bulan",
                'unit_price'  => $pricePerMonth,
                'quantity'    => $durationMonth,
                'total_price' => $total,
            ]);

            DB::commit();

            return response()->json([
                'code' => 201,
                'message' => 'Checkout berhasil, invoice dibuat',
                'data' => [
                    'service_id'     => $service->id,
                    'invoice_id'     => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'duration_month' => $durationMonth,
                    'total'          => $total,
                    'status'         => $invoice->status,
                ],
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'code' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();

            $message = [
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ];

            Log::critical('Checkout Error', $message);

            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function product(Request $request)
    {
        try {
            $products = Product::where('is_active', true)
                // SEARCH MUST ADJUST CAPITALIZE SO WE CONVERT ALL TEXT TO LOWERCASE ALSO FOR DATA COMPARISON
                ->whereRaw(
                    'LOWER(name) LIKE ?',
                    ['%' . strtolower($request->search) . '%']
                )
                ->orderBy('id', 'asc')
                ->paginate(10);

            return response()->json([
                'code' => 200,
                'data' => $products,
            ]);
        } catch (Throwable $e) {
            Log::error('Get Product List Error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function product_detail(Request $request, $id)
    {
        try {
            $product = Product::where('id', $id)
                ->where('is_active', true)
                ->first();

            if (! $product) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Product tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'code' => 200,
                'data' => $product,
            ]);
        } catch (Throwable $e) {
            Log::error('Get Product Detail Error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function product_durations(Request $request, $id)
    {
        try {
            $product = Product::where('id', $id)
                ->where('is_active', true)
                ->with(['durations' => function ($q) {
                    $q->where('is_active', true)
                        ->orderBy('duration_month');
                }])
                ->first();

            if (!$product) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Product tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'code' => 200,
                'data' => $product->durations,
            ]);
        } catch (Throwable $e) {
            Log::error('Get Product Duration Error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
