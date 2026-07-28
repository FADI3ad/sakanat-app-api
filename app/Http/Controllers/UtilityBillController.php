<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\UtilityBill;
use App\Http\Requests\UtilityBill\StoreUtilityBillRequest;
use App\Http\Requests\UtilityBill\UpdateUtilityBillRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UtilityBillController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helper: Format a single utility bill record for API response
    |--------------------------------------------------------------------------
    */
    private function formatBill(UtilityBill $bill): array
    {
        return [
            'id'          => $bill->id,
            'property_id' => $bill->property_id,
            'type'        => $bill->type->value,
            'month'       => $bill->month,
            'amount'      => $bill->amount,
            'is_paid'     => $bill->is_paid,
            'paid_at'     => $bill->paid_at?->toDateTimeString(),
            'notes'       => $bill->notes,
            'created_at'  => $bill->created_at,
            'updated_at'  => $bill->updated_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Ensure the authenticated user owns the property
    |--------------------------------------------------------------------------
    */
    private function authorizePropertyOwner(Request $request, Property $property): bool
    {
        return $request->user()->id === $property->user_id;
    }

    /*
    |--------------------------------------------------------------------------
    | List all bills for a property (with optional month filter)
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/bills
    */
    public function index(Request $request, Property $property)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض فواتير هذا العقار.',
            ], 403);
        }

        $query = $property->utilityBills();

        // فلترة اختيارية بالشهر (مثلاً: ?month=2026-07)
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        // فلترة اختيارية بنوع الفاتورة (مثلاً: ?type=electricity)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // فلترة اختيارية بحالة الدفع (مثلاً: ?is_paid=0 أو ?is_paid=1)
        if ($request->has('is_paid')) {
            $query->where('is_paid', filter_var($request->is_paid, FILTER_VALIDATE_BOOLEAN));
        }

        $bills = $query->orderBy('month', 'desc')->orderBy('type')->get();

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الفواتير بنجاح',
            'data'    => $bills->map(fn($b) => $this->formatBill($b)),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Add a new bill to a property
    |--------------------------------------------------------------------------
    | POST /v1/properties/{property}/bills
    */
    public function store(StoreUtilityBillRequest $request, Property $property)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بإضافة فواتير لهذا العقار.',
            ], 403);
        }

        $data = $request->validated();
        $data['property_id'] = $property->id;
        $data['is_paid']     = false;

        $bill = UtilityBill::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة الفاتورة بنجاح',
            'data'    => $this->formatBill($bill),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show a single bill
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/bills/{bill}
    */
    public function show(Request $request, Property $property, UtilityBill $bill)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض هذه الفاتورة.',
            ], 403);
        }

        // التأكد إن الفاتورة تبع العقار المحدد
        if ($bill->property_id !== $property->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الفاتورة لا تنتمي إلى هذا العقار.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل الفاتورة بنجاح',
            'data'    => $this->formatBill($bill),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update bill details (type, month, amount, notes)
    |--------------------------------------------------------------------------
    | PUT /v1/properties/{property}/bills/{bill}
    */
    public function update(UpdateUtilityBillRequest $request, Property $property, UtilityBill $bill)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل هذه الفاتورة.',
            ], 403);
        }

        if ($bill->property_id !== $property->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الفاتورة لا تنتمي إلى هذا العقار.',
            ], 404);
        }

        $bill->update($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث الفاتورة بنجاح',
            'data'    => $this->formatBill($bill->fresh()),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Mark a bill as paid (records paid_at timestamp automatically)
    |--------------------------------------------------------------------------
    | PATCH /v1/properties/{property}/bills/{bill}/pay
    */
    public function markAsPaid(Request $request, Property $property, UtilityBill $bill)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل هذه الفاتورة.',
            ], 403);
        }

        if ($bill->property_id !== $property->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الفاتورة لا تنتمي إلى هذا العقار.',
            ], 404);
        }

        if ($bill->is_paid) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الفاتورة مدفوعة بالفعل.',
            ], 422);
        }

        $bill->update([
            'is_paid' => true,
            'paid_at' => Carbon::now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تسجيل الدفع بنجاح',
            'data'    => $this->formatBill($bill->fresh()),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete a bill
    |--------------------------------------------------------------------------
    | DELETE /v1/properties/{property}/bills/{bill}
    */
    public function destroy(Request $request, Property $property, UtilityBill $bill)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بحذف هذه الفاتورة.',
            ], 403);
        }

        if ($bill->property_id !== $property->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الفاتورة لا تنتمي إلى هذا العقار.',
            ], 404);
        }

        $bill->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الفاتورة بنجاح',
        ]);
    }
}
