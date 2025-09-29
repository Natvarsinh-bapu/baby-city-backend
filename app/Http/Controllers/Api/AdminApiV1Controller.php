<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Requests\Api\v1\Admin\VerifyResetPasswordTokenRequest;
use App\Http\Requests\Api\v1\Admin\SendForgotPasswordLinkRequest;
use App\Http\Requests\Api\v1\Admin\ResetPasswordRequest;
use App\Http\Requests\Api\v1\Admin\UpdateCategoryRequest;
use App\Http\Requests\Api\v1\Admin\AddCategoryRequest;
use App\Http\Requests\Api\v1\Admin\AddProductRequest;
use App\Http\Requests\Api\v1\Admin\LoginRequest;
use App\Http\Resources\Api\v1\Admin\CategoryResource;
use App\Http\Resources\Api\v1\Admin\MessageResource;
use App\Http\Resources\Api\v1\Admin\UserResource;
use App\Events\SendResetPasswordLinkEvent;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class AdminApiV1Controller extends Controller
{
    // login
    public function login(LoginRequest $request)
    {
        try {
            if (Auth::once($request->only('email', 'password'))) {
                $user = Auth::user();
                $token = $user->createToken('api-token');
                return response()->customJson(true, ['token' => $token->plainTextToken], [], 'Login successfully.', '', 200);
            }
            return response()->customJson(false, (object)[], [], 'Invalid credentails.', '', 403);
        } catch (Exception $e) {
            return response()->customJson(false, (object)[], [], $e->getMessage(), '', 500);
        }
    }

    // send forgot password link
    public function sendForgotPasswordLink(SendForgotPasswordLinkRequest $request)
    {
        try {
            $user = User::where('email', $request->input('email'))
                ->where('role', User::ROLE_ADMIN)
                ->first();

            if(!$user){
                return response()->customJson(false, [], [], 'User not found with the given email address.', '', 404);
            }

            $reset_password_token = Str::random(32);
            $url = env('FRONTEND_URL_ADMIN') . "/reset-password?token={$reset_password_token}";

            SendResetPasswordLinkEvent::dispatch($user->email, $url);

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => $reset_password_token,
                'created_at' => now(),
            ]);

            return response()->customJson(true, [], [], 'Reset password link send to your email.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, (object)[], [], $e->getMessage(), '', 500);
        }
    }

    // verify reset password token
    public function verifyResetPasswordToken(VerifyResetPasswordTokenRequest $request)
    {
        try {
            $record = DB::table('password_reset_tokens')
                ->where('token', $request->input('password_reset_token'))
                ->first();

            if($record){
                return response()->customJson(true, ['is_valid_token' => true], [], '', '', 200);
            } else {
                return response()->customJson(true, ['is_valid_token' => false], [], 'Your reset password link is expired. Please try again.', '', 404);
            }
        } catch (Exception $e) {
            return response()->customJson(false, (object)[], [], $e->getMessage(), '', 500);
        }
    }

    // reset password
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $record = DB::table('password_reset_tokens')
                ->where('token', $request->input('password_reset_token'))
                ->first();

            if(!$record){
                return response()->customJson(false, [], [], 'Invalid token.', '', 404);
            }

            $user = User::where('email', $record->email)->first();

            $user->update([
                'password' => Hash::make($request->input('password'))
            ]);

            DB::table('password_reset_tokens')->where('token', $record->token)->delete();

            return response()->customJson(true, [], [], 'Password reset successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // logout
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->customJson(true, [], [], 'Logout successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // dashboard
    public function dashboard()
    {
        try {
            $data = [
                'count_categories' => Category::count(),
                'count_products'   => Product::count(),
                'count_messages'   => Message::count(),
            ];

            return response()->customJson(true, $data, [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // contact us
    public function contactUs(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'message' => 'required|string'
            ]);

            Message::create($request->only('name', 'email', 'message'));

            return response()->customJson(true, [], [], 'Message sent successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // change password
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:6|confirmed'
            ]);

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->customJson(false, [], [], 'Current password does not match.', '', 200);
            }

            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->customJson(true, [], [], 'Password changed successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }
    // categories list
    public function categories()
    {
        try {
            $categories = Category::with('subCategories')->get();
            return response()->customJson(true, CategoryResource::collection($categories), [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // add category
    public function addCategory(AddCategoryRequest $request)
    {
        try {
            // Ensure directories exist
            if (!file_exists(public_path('images/categories'))) {
                mkdir(public_path('images/categories'), 0755, true);
            }
            if (!file_exists(public_path('images/subcategories'))) {
                mkdir(public_path('images/subcategories'), 0755, true);
            }

            // Handle category image
            $categoryImagePath = null;

            if ($request->hasFile('image')) {
                $categoryImage = $request->file('image');
                $originalName = str_replace(' ', '_', $categoryImage->getClientOriginalName());
                $categoryImageName = time() . '_' . $originalName;
                $categoryImage->move(public_path('images/categories'), $categoryImageName);
                $categoryImagePath = 'images/categories/' . $categoryImageName;
            }

            // Create category
            $category = Category::create([
                'name' => $request->input('name'),
                'image' => $categoryImagePath,
            ]);

            // Handle subcategories
            if ($request->has('sub_categories')) {
                foreach ($request->input('sub_categories') as $index => $subCategoryData) {

                    $subImagePath = null;

                    // Check if the file exists in the request
                    if ($request->hasFile("sub_categories.$index.image")) {
                        $subImage = $request->file("sub_categories.$index.image");
                        $originalName = str_replace(' ', '_', $subImage->getClientOriginalName());
                        $subImageName = time() . '_' . $originalName;
                        $subImage->move(public_path('images/subcategories'), $subImageName);
                        $subImagePath = 'images/subcategories/' . $subImageName;
                    }

                    $category->subCategories()->create([
                        'name' => $subCategoryData['name'],
                        'image' => $subImagePath,
                    ]);
                }
            }

            return response()->customJson(true, $category->load('subCategories'), [], 'Category created successfully.', '', 200);

        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // update category
    public function updateCategory(UpdateCategoryRequest $request, $id)
    {
        try {
            $category = Category::with('subCategories')->findOrFail($id);

            // Update category name
            if ($request->has('name')) {
                $category->name = $request->name;
            }

            // Update category image
            if ($request->hasFile('image')) {
                // Delete old image
                if ($category->image && file_exists(public_path($category->image))) {
                    unlink(public_path($category->image));
                }

                if (!file_exists(public_path('images/categories'))) {
                    mkdir(public_path('images/categories'), 0755, true);
                }

                $categoryImage = $request->file('image');
                $categoryImageName = time() . '_' . str_replace(' ', '_', $categoryImage->getClientOriginalName());
                $categoryImage->move(public_path('images/categories'), $categoryImageName);
                $category->image = 'images/categories/' . $categoryImageName;
            }

            $category->save();

            // Handle subcategories
            $submittedSubCategories = $request->input('sub_categories', []);

            $existingIds = $category->subCategories->pluck('id')->toArray();
            $submittedIds = collect($submittedSubCategories)->pluck('id')->filter()->toArray();

            // Delete removed subcategories AND their images
            $toDelete = array_diff($existingIds, $submittedIds);
            if ($toDelete) {
                $subsToDelete = SubCategory::whereIn('id', $toDelete)->get();
                foreach ($subsToDelete as $sub) {
                    if ($sub->image && file_exists(public_path($sub->image))) {
                        unlink(public_path($sub->image));
                    }
                    $sub->delete();
                }
            }

            // Add or update subcategories
            foreach ($submittedSubCategories as $index => $subData) {
                $subImagePath = null;

                // Handle new subcategory image upload
                if ($request->hasFile("sub_categories.$index.image")) {
                    $subImage = $request->file("sub_categories.$index.image");
                    if (!file_exists(public_path('images/subcategories'))) {
                        mkdir(public_path('images/subcategories'), 0755, true);
                    }

                    $subImageName = time() . "_" . str_replace(' ', '_', $subImage->getClientOriginalName());
                    $subImage->move(public_path('images/subcategories'), $subImageName);
                    $subImagePath = 'images/subcategories/' . $subImageName;
                }

                if (!empty($subData['id'])) {
                    // Update existing
                    $subCategory = SubCategory::findOrFail($subData['id']);
                    $subCategory->name = $subData['name'];

                    // Delete old subcategory image if replaced
                    if ($subImagePath && $subCategory->image && file_exists(public_path($subCategory->image))) {
                        unlink(public_path($subCategory->image));
                    }

                    if ($subImagePath) $subCategory->image = $subImagePath;
                    $subCategory->save();
                } else {
                    // Create new subcategory
                    $category->subCategories()->create([
                        'name' => $subData['name'],
                        'image' => $subImagePath,
                    ]);
                }
            }

            return response()->customJson(true, $category->load('subCategories'), [], 'Category updated successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // category details
    public function categoryDetails($id)
    {
        try {
            $category = Category::with('subCategories')->findOrFail($id);
            return response()->customJson(true, new CategoryResource($category), [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // delete category
    public function deleteCategory($id)
    {
        try {
            $category = Category::with('subCategories')->findOrFail($id);

            // Delete category image
            if ($category->image && file_exists(public_path($category->image))) {
                unlink(public_path($category->image));
            }

            // Delete subcategory images
            foreach ($category->subCategories as $sub) {
                if ($sub->image && file_exists(public_path($sub->image))) {
                    unlink(public_path($sub->image));
                }
            }

            // Delete subcategories
            $category->subCategories()->delete();

            // Delete category
            $category->delete();

            return response()->customJson(true, [], [], 'Category and related subcategories deleted successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // products list
    public function products()
    {
        try {
            $products = Product::latest()->paginate(20);

            foreach ($products as $product) {
                $product->image_url = $product->image ? asset($product->image) : null;
            }

            return response()->customJson(true, $products, [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // add product
    public function AddProduct(AddProductRequest $request)
    {
        try {
            // Ensure directories exist
            if (!file_exists(public_path('images/products'))) {
                mkdir(public_path('images/products'), 0755, true);
            }
            if (!file_exists(public_path('images/products/gallery'))) {
                mkdir(public_path('images/products/gallery'), 0755, true);
            }

            // Handle main product image
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $originalName = str_replace(' ', '_', $image->getClientOriginalName());
                $imageName = time() . '_' . $originalName;
                $image->move(public_path('images/products'), $imageName);
                $imagePath = 'images/products/' . $imageName;
            }

            // Handle gallery images
            $gallery = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $originalName = str_replace(' ', '_', $file->getClientOriginalName());
                    $fileName = time() . '_' . $originalName;
                    $file->move(public_path('images/products/gallery'), $fileName);
                    $gallery[] = 'images/products/gallery/' . $fileName;
                }
            }

            // Normalize attributes
            $attributes = collect($request->input('attributes', []))->map(function ($attr) {
                return [
                    'key' => $attr['key'] ?? null,
                    'value' => $attr['value'] ?? null,
                ];
            })->filter(fn($attr) => $attr['key'] && $attr['value'])->values()->toArray();

            // Create product
            $product = Product::create([
                'name' => $request->name,
                'slug' => $request->slug ?? \Str::slug($request->name),
                'description' => $request->description,
                'short_description' => $request->short_description,
                'price' => $request->price,
                'sale_price' => $request->sale_price,
                // ✅ Convert booleans to integers
                'in_stock' => $request->boolean('in_stock') ? 1 : 0,
                'active' => $request->boolean('active') ? 1 : 0,

                'image' => $imagePath,
                'gallery' => $gallery,
                'attributes' => $attributes,
            ]);

            // Save product subcategories using hasMany
            if ($request->has('subcategories') && is_array($request->subcategories)) {
                $subCategoryData = collect($request->subcategories)->map(function ($subCategoryId) {
                    return ['sub_category_id' => $subCategoryId];
                })->toArray();

                $product->subCategories()->createMany($subCategoryData);
            }

            return response()->customJson(true, $product, [], 'Product created successfully.', '', 200);

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

            // Add full image URLs
            $product->imageUrl = $product->image ? asset($product->image) : null;
            $product->galleryUrls = collect($product->gallery)->map(fn($g) => asset($g))->toArray();

            return response()->customJson(true, $product, [], '', '', 200);

        } catch (\Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    public function updateProduct(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            // Basic fields
            $product->name = $request->name ?? $product->name;
            $product->slug = $request->slug ?? $product->slug;
            $product->description = $request->description ?? $product->description;
            $product->short_description = $request->short_description ?? $product->short_description;
            $product->price = $request->price ?? $product->price;
            $product->sale_price = $request->sale_price ?? $product->sale_price;
            $product->in_stock = $request->has('in_stock') ? filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN) : $product->in_stock;
            $product->active = $request->has('active') ? filter_var($request->active, FILTER_VALIDATE_BOOLEAN) : $product->active;

            // === Main product image ===
            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image && file_exists(public_path($product->image))) {
                    unlink(public_path($product->image));
                }

                if (!file_exists(public_path('images/products'))) {
                    mkdir(public_path('images/products'), 0755, true);
                }

                $image = $request->file('image');
                $originalName = str_replace(' ', '_', $image->getClientOriginalName());
                $imageName = time() . '_' . $originalName;
                $image->move(public_path('images/products'), $imageName);

                $product->image = 'images/products/' . $imageName;
            }

            $product->save();

            // === Gallery images ===
            if ($request->hasFile('gallery')) {
                // Delete old gallery images if they exist
                if (is_array($product->gallery)) {
                    foreach ($product->gallery as $oldFile) {
                        if ($oldFile && file_exists(public_path($oldFile))) {
                            unlink(public_path($oldFile));
                        }
                    }
                }

                if (!file_exists(public_path('images/products/gallery'))) {
                    mkdir(public_path('images/products/gallery'), 0755, true);
                }

                $galleryFiles = $request->file('gallery');
                $galleryPaths = [];

                foreach ($galleryFiles as $file) {
                    $originalName = str_replace(' ', '_', $file->getClientOriginalName());
                    $fileName = time() . '_' . $originalName;
                    $file->move(public_path('images/products/gallery'), $fileName);

                    $galleryPaths[] = 'images/products/gallery/' . $fileName;
                }

                $product->gallery = $galleryPaths; // assuming casted as array in model
                $product->save();
            }

            // === Subcategories ===
            if ($request->has('subcategories') && is_array($request->subcategories)) {
                $product->subCategories()->delete();

                $subCategoryData = collect($request->subcategories)->map(function ($subCategoryId) {
                    return ['sub_category_id' => $subCategoryId];
                })->toArray();

                $product->subCategories()->createMany($subCategoryData);
            }

            // === Attributes ===
            if ($request->has('attributes') && is_array($request->attributes)) {
                $product->attributes = $request->attributes;
            }

            $product->save();

            return response()->customJson(true, $product, [], 'Product updated successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // delete product
    public function deleteProduct($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->customJson(true, [], [], 'Product deleted successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // messages list
    public function messages()
    {
        try {
            $messages = Message::latest()->paginate(20);

            return response()->customJson(true, $messages, [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // delete message
    public function deleteMessage(Request $request)
    {
        try {
            $request->validate(['id' => 'required|exists:messages,id']);
            $message = Message::findOrFail($request->input('id'));
            $message->delete();

            return response()->customJson(true, [], [], 'Message deleted successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // get settings
    public function settings(Request $request)
    {
        try {
            $settings = Setting::get()->pluck('value', 'key');

            return response()->customJson(true, $settings, [], 'Settings fetched successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // update settings
    public function updateSettings(Request $request)
    {
        try {
            $data = $request->all();

            foreach ($data as $key => $value) {
                Setting::setValue($key, $value);
            }

            return response()->customJson(true, [], [], 'Settings updated successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // slider list
    public function sliderList()
    {
        try {
            $slider_data = Setting::where('key', 'slider_image')->get();

            foreach ($slider_data as &$sliderImage) {
                $sliderImage->image_url = asset($sliderImage->value);
            }

            return response()->customJson(true, $slider_data, [], '', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // save slider images
    public function saveSliderImage(Request $request)
    {
        try {
            if ($request->hasFile('image')) {
                if (!file_exists(public_path('images/sliders'))) {
                    mkdir(public_path('images/sliders'), 0755, true);
                }

                $sliderImage = $request->file('image');
                $originalName = str_replace(' ', '_', $sliderImage->getClientOriginalName());
                $sliderImageName = time() . '_' . $originalName;
                $sliderImage->move(public_path('images/sliders'), $sliderImageName);
                $sliderImagePath = 'images/sliders/' . $sliderImageName;

                // Save each image in settings table
                Setting::create([
                    'key' => 'slider_image',
                    'value' => $sliderImagePath,
                ]);
            }

            return response()->customJson(true, [], [], 'Slider images saved successfully.', '', 200);
        } catch (Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }

    // delete slider image
    public function deleteSliderImage($id)
    {
        try {
            $slider = Setting::findOrFail($id);

            // Delete physical image file
            if ($slider->value && file_exists(public_path($slider->value))) {
                unlink(public_path($slider->value));
            }

            // Delete DB record
            $slider->delete();

            return response()->customJson(true, [], [], 'Slider image deleted successfully.', '', 200);
        } catch (\Exception $e) {
            return response()->customJson(false, [], [], $e->getMessage(), '', 500);
        }
    }
}
