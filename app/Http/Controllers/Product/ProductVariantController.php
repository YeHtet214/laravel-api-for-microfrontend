<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductVariantRequest;
use App\Http\Requests\Product\UpdateProductVariantRequest;
use App\Http\Resources\Product\ProductVariantResource;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $query = ProductVariant::with('attributes');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return ProductVariantResource::collection($query->get());
    }

    public function store(StoreProductVariantRequest $request): ProductVariantResource
    {
        return DB::transaction(function () use ($request) {
            $variant = ProductVariant::create($request->safe()->except('attributes'));

            if ($request->filled('attributes')) {
                $variant->attributes()->createMany($request->input('attributes'));
            }

            return new ProductVariantResource($variant->load('attributes'));
        });
    }

    public function show(ProductVariant $variant): ProductVariantResource
    {
        return new ProductVariantResource($variant->load('attributes'));
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $variant): ProductVariantResource
    {
        return DB::transaction(function () use ($request, $variant) {
            $variant->update($request->safe()->except('attributes'));

            if ($request->has('attributes')) {
                $variant->attributes()->delete();
                $variant->attributes()->createMany($request->input('attributes'));
            }

            return new ProductVariantResource($variant->load('attributes'));
        });
    }

    public function destroy(ProductVariant $variant): Response
    {
        $variant->delete();
        return response()->noContent();
    }
}
