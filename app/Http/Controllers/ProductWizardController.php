<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use App\Models\Uom;
use App\Models\PackingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductWizardController extends Controller
{
    public function index()
    {
        return view('product.wizard', [
            'categories'   => ProductCategory::where('status', 1)->orderBy('name')->get(),
            'groups'       => ProductGroup::where('status', 1)->orderBy('name')->get(),
            'uoms'         => Uom::where('status', 1)->orderBy('name')->get(),
            'packingTypes' => PackingType::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
        ]);

        $category = ProductCategory::create([
            'name'   => trim($request->name),
            'status' => 1,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Product Category created successfully!',
            'category' => $category,
        ]);
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_groups,name',
        ]);

        $group = ProductGroup::create([
            'name'   => trim($request->name),
            'status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product Group created successfully!',
            'group'   => $group,
        ]);
    }

    public function storeUom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:uoms,name',
        ]);

        $uom = Uom::create([
            'name'   => trim($request->name),
            'status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'UOM created successfully!',
            'uom'     => $uom,
        ]);
    }

    public function storePackingType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:packing_types,name',
        ]);

        $packingType = PackingType::create([
            'name'   => trim($request->name),
            'status' => 1,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Packing Type created successfully!',
            'packingType' => $packingType,
        ]);
    }

    public function deleteCategory(ProductCategory $category)
    {
        try {
            $category->delete();
            return response()->json(['success' => true, 'message' => 'Category deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Cannot delete: Category is linked to existing products.'], 422);
        }
    }

    public function deleteGroup(ProductGroup $group)
    {
        try {
            $group->delete();
            return response()->json(['success' => true, 'message' => 'Group deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Cannot delete: Group is linked to existing products.'], 422);
        }
    }

    public function deleteUom(Uom $uom)
    {
        try {
            $uom->delete();
            return response()->json(['success' => true, 'message' => 'UOM deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Cannot delete: UOM is linked to existing products.'], 422);
        }
    }

    public function deletePackingType(PackingType $packingType)
    {
        try {
            $packingType->delete();
            return response()->json(['success' => true, 'message' => 'Packing Type deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Cannot delete: Packing Type is linked to existing products.'], 422);
        }
    }

    public function storeProduct(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'item_code'           => 'required|string|max:100|unique:products,item_code',
            'name'                => 'required|string|max:255',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_group_id'    => 'required|exists:product_groups,id',
            'uom_id'              => 'required|exists:uoms,id',
            'packing_type_id'     => 'required|exists:packing_types,id',
            'pack_size'           => 'required|integer|min:1',
            'cartons_per_pallet'  => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(' | ', $validator->errors()->all()),
            ], 422);
        }

        $product = Product::create([
            'item_code'           => trim($request->item_code),
            'name'                => trim($request->name),
            'product_category_id' => $request->product_category_id,
            'product_group_id'    => $request->product_group_id,
            'uom_id'              => $request->uom_id,
            'packing_type_id'     => $request->packing_type_id,
            'pack_size'           => $request->pack_size,
            'cartons_per_pallet'  => $request->cartons_per_pallet ?: null,
            'status'              => $request->has('status') ? 1 : 0,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Product "' . $product->name . '" (' . $product->item_code . ') has been created successfully!',
            'redirect' => route('product.index'),
        ]);
    }
}
