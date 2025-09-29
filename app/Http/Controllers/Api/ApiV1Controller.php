<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Api\v1\Admin\MessageResource;
use App\Http\Resources\Api\v1\Admin\CategoryResource;
use App\Http\Resources\Api\v1\Admin\ProductResource;
use App\Http\Resources\Api\v1\HeaderDataResource;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Product;
use App\Models\Message;
use App\Models\Setting;
use Exception;

class ApiV1Controller extends Controller
{
    // Get header data
    public function headerData()
    {
        try {
            $categories = Category::with('subCategories')->get();

            return response()->customJson(true, HeaderDataResource::collection($categories), [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // Get footer data
    public function footerData()
    {
        try {
            $data = Setting::where('key', '!=', 'slider_image')->pluck('value', 'key');

            return response()->customJson(true, $data, [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // Get home data
    public function homeData()
    {
        try {
            $slider_images = Setting::where('key', 'slider_image')->get();
            $sub_categories = SubCategory::orderBy('name', 'asc')->get();

            foreach ($slider_images as $slider_image) {
                $data['slider_images'][] = [
                    'image_url' => asset($slider_image->value),
                ];
            }

            foreach ($sub_categories as &$sub_category) {
                $sub_category->image_url = $sub_category->image ? asset($sub_category->image) : null;
            }

            $data['sub_categories'] = $sub_categories;

            return response()->customJson(true, $data, [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // Get all products
    public function products()
    {
        try {
            $products = Product::with(['subCategories'])
                ->when(request()->get('q'), function ($query) {
                    $query->where('name', 'like', '%' . request()->get('q') . '%')
                        ->orWhere('description', 'like', '%' . request()->get('q') . '%')
                        ->orWhere('short_description', 'like', '%' . request()->get('q') . '%');
                })
                ->when(request()->get('categoryId'), function ($query) {
                    $query->whereHas('subCategories', function ($q) {
                        $q->where('sub_category_id', request()->get('categoryId'));
                    });
                })
                ->paginate(20);

            foreach ($products as $product) {
                $product->image_url = $product->image ? asset($product->image) : null;
            }

            return response()->customJson(true, $products, [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // product details
    public function productDetails($id)
    {
        try {
            $product = Product::with('subCategories')->findOrFail($id);

            // Decode JSON fields
            $product->gallery = is_array($product->gallery)
                ? $product->gallery
                : json_decode($product->gallery, true) ?? [];

            $product->attributes = is_array($product->attributes)
                ? $product->attributes
                : json_decode($product->attributes, true) ?? [];

            $product->variants = is_array($product->variants)
                ? $product->variants
                : json_decode($product->variants, true) ?? [];

            $images = [];

            // Add full image URLs
            $images[] = $product->image ? asset($product->image) : null;
            $galleryUrls = collect($product->gallery)->map(fn($g) => asset($g))->toArray();

            $images = array_merge($images, $galleryUrls);

            $product->image_urls = $images;

            return response()->customJson(true, $product, [], '', '', 200);
        } catch (\Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // Send a message (contact form)
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'message' => 'required|string',
            ]);

            $message = Message::create([
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message,
            ]);

            return response()->customJson(true, new MessageResource($message), [], 'Message sent successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // Get all messages
    public function messages()
    {
        try {
            $messages = Message::latest()->get();
            return response()->customJson(true, MessageResource::collection($messages), [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // Get all categories with subcategories
    public function categories()
    {
        try {
            $categories = Category::with('subCategories')->get();

            return response()->customJson(true, CategoryResource::collection($categories), [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }
}
