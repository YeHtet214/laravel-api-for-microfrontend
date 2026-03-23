<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'sort' => ['nullable', 'string', 'in:latest,oldest'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Product::with(['category', 'variants.attributes']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sort = $request->query('sort', 'latest');
        if ($sort === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $perPage = $request->query('per_page', 15);
        
        return ProductResource::collection($query->paginate($perPage));
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        
        $product = Product::create($data);
        return new ProductResource($product);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'variants.attributes']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $product->update($data);
        return new ProductResource($product);
    }

    public function destroy(Product $product): Response
    {
        $product->delete();
        return response()->noContent();
    }
}
