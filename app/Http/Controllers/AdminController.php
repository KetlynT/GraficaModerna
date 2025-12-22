<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

use App\Services\AuthService;
use App\Services\DashboardService;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\ContentService;
use App\Services\CouponService;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\CouponRequest;
use App\Http\Requests\ContentPageRequest;
use App\Http\Requests\EmailTemplateRequest;

use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CouponResource;
use App\Http\Resources\ContentPageResource;
use App\Http\Resources\EmailTemplateResource;

use App\Models\EmailTemplate;
use App\Models\RefundRequest;

class AdminController extends Controller
{
    private const MAX_FILE_SIZE = 52428800; 
    private const MAX_IMAGE_DIMENSION = 2048;

    protected AuthService $authService;
    protected DashboardService $dashboardService;
    protected ProductService $productService;
    protected OrderService $orderService;
    protected ContentService $contentService;
    protected CouponService $couponService;
    protected HtmlSanitizer $sanitizer;

    public function __construct(
        AuthService $authService,
        DashboardService $dashboardService,
        ProductService $productService,
        OrderService $orderService,
        ContentService $contentService,
        CouponService $couponService
    ) {
        $this->authService = $authService;
        $this->dashboardService = $dashboardService;
        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->contentService = $contentService;
        $this->couponService = $couponService;

        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('class', '*')
            ->allowAttribute('style', '*');

        foreach (['img', 'iframe', 'figure', 'figcaption'] as $element) {
            $config->allowElement($element);
        }

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        $data['isAdminLogin'] = true;

        $result = $this->authService->login($data);

        $this->setTokenCookies($result['accessToken'], $result['refreshToken']);

        return response()->json([
            'email' => $result['user']['email'],
            'role' => $result['user']['role'],
            'message' => 'Login administrativo realizado com sucesso.'
        ]);
    }

    public function dashboardStats()
    {
        $stats = $this->dashboardService->getAnalytics();
        return new DashboardStatsResource($stats);
    }

    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'Nenhum ficheiro enviado.'], 400);
        }

        $file = $request->file('file');

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $mb = self::MAX_FILE_SIZE / 1024 / 1024;
            return response()->json(['message' => "O ficheiro excede o tamanho máximo permitido de {$mb}MB."], 400);
        }

        try {
            $extension = $this->detectExtensionFromSignature($file->getRealPath());
            
            if (!$extension) {
                return response()->json(['message' => 'O arquivo parece estar corrompido ou tem um formato não permitido.'], 400);
            }

            $fileName = Str::uuid() . $extension;
            $path = "uploads/{$fileName}";

            if ($this->isVideo($extension)) {
                $file->storeAs('public/uploads', $fileName);
            } else {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($file->getRealPath());
                
                if ($image->width() > self::MAX_IMAGE_DIMENSION || $image->height() > self::MAX_IMAGE_DIMENSION) {
                    $image->scaleDown(width: self::MAX_IMAGE_DIMENSION, height: self::MAX_IMAGE_DIMENSION);
                }
                
                Storage::disk('public')->put($path, (string) $image->encode());
            }

            $fileUrl = asset("storage/{$path}");
            return response()->json(['url' => $fileUrl]);

        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Erro ao processar arquivo.',
                'details' => $ex->getMessage()
            ], 500);
        }
    }

    public function getOrders(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $pageSize = (int) $request->query('pageSize', 10);

        $orders = $this->orderService->getAllOrders($page, $pageSize);
        
        return OrderResource::collection($orders);
    }
    
    public function getRefundRequests(Request $request)
    {
        $query = RefundRequest::with(['order', 'user', 'items.orderItem.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(10));
    }

    public function processRefundRequest(Request $request, $id)
    {
        $refundRequest = RefundRequest::findOrFail($id);
        
        $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string',
            'refunded_amount' => 'required_if:action,approve|numeric|min:0',
            'proof_file' => 'nullable|file|max:2048',
            'restock_items' => 'nullable|array',
            'restock_items.*.item_id' => 'required_with:restock_items|integer', 
            'restock_items.*.quantity_to_stock' => 'required_with:restock_items|integer|min:0'
        ]);

        return DB::transaction(function () use ($request, $refundRequest) {
            
            if ($request->action === 'reject') {
                $refundRequest->update([
                    'status' => 'rejected',
                    'admin_notes' => $request->admin_notes
                ]);
                
                $refundRequest->order->update(['status' => 'delivered']);
            } 
            else {
                $path = null;
                if ($request->hasFile('proof_file')) {
                    $path = $request->file('proof_file')->store('refunds', 'public');
                }

                $refundRequest->update([
                    'status' => 'approved',
                    'refunded_amount' => $request->refunded_amount,
                    'admin_notes' => $request->admin_notes,
                    'proof_file' => $path
                ]);

                if ($request->has('restock_items')) {
                    foreach ($request->restock_items as $restockData) {
                        $reqItem = $refundRequest->items()->where('id', $restockData['item_id'])->first();
                        
                        if ($reqItem && $restockData['quantity_to_stock'] > 0) {
                            $reqItem->update(['quantity_restocked' => $restockData['quantity_to_stock']]);

                            $product = $reqItem->orderItem->product;
                            if ($product) {
                                $product->increment('stock', $restockData['quantity_to_stock']);
                            }
                        }
                    }
                }
                
                $refundRequest->order->update(['status' => 'refunded']); 
            }

            return response()->json(['message' => 'Processado com sucesso', 'data' => $refundRequest]);
        });
    }

    public function updateOrderStatus(string $id, Request $request)
    {
        $request->validate([
            'status' => 'required|string',
            'refundAmount' => 'nullable|numeric|min:0',
            'trackingCode' => 'nullable|string'
        ]);

        try {
            $this->orderService->updateAdminOrder($id, $request->all());
            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getProducts(Request $request)
    {
        $result = $this->productService->getCatalog(
            $request->query('search'),
            $request->query('sort'),
            $request->query('order'),
            (int) $request->query('page', 1),
            (int) $request->query('pageSize', 8)
        );

        return ProductResource::collection($result);
    }

    public function getProductById(string $id)
    {
        $product = $this->productService->getById($id);
        if (!$product) return response()->json(['message' => 'Produto não encontrado.'], 404);
        
        return new ProductResource($product);
    }

    public function createProduct(ProductRequest $request)
    {
        $product = $this->productService->create($request->validated());
        return response()->json(new ProductResource($product), 201);
    }

    public function updateProduct(string $id, ProductRequest $request)
    {
        try {
            $this->productService->update($id, $request->validated());
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar: ' . $e->getMessage()], 400);
        }
    }

    public function deleteProduct(string $id)
    {
        try {
            $this->productService->delete($id);
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao deletar.'], 400);
        }
    }

    public function getCoupons()
    {
        $coupons = $this->couponService->getAll();
        return CouponResource::collection($coupons);
    }

    public function createCoupon(CouponRequest $request)
    {
        $coupon = $this->couponService->create($request->validated());
        return response()->json(new CouponResource($coupon));
    }

    public function deleteCoupon(string $id)
    {
        $this->couponService->delete($id);
        return response()->noContent();
    }

    public function createPage(ContentPageRequest $request)
    {
        $data = $request->validated();
        $data['content'] = $this->sanitizer->sanitize($data['content']);
        
        $page = $this->contentService->createPage($data);

        return response()->json(new ContentPageResource($page));
    }

    public function updatePage(string $slug, ContentPageRequest $request)
    {
        $data = $request->validated();
        $data['content'] = $this->sanitizer->sanitize($data['content']);
        
        $this->contentService->updatePage($slug, $data);

        return response()->noContent();
    }

    public function updateSettings(Request $request)
    {
        $settings = $request->all();
        $this->contentService->updateSettings($settings);
        return response()->noContent();
    }

    public function getEmailTemplates()
    {
        $templates = EmailTemplate::all();
        return EmailTemplateResource::collection($templates);
    }

    public function getEmailTemplateById(string $id)
    {
        $template = EmailTemplate::find($id);
        if (!$template) return response()->json(['message' => 'Template não encontrado.'], 404);
        return new EmailTemplateResource($template);
    }

    public function updateEmailTemplate(string $id, EmailTemplateRequest $request)
    {
        $template = EmailTemplate::find($id);
        if (!$template) return response()->json(['message' => 'Template não encontrado.'], 404);

        $template->subject = $request->input('subject');
        $template->body_content = $request->input('bodyContent'); 
        $template->updated_at = now();
        $template->save();

        return response()->noContent();
    }

    private function setTokenCookies(string $accessToken, string $refreshToken): void
    {
        $secure = app()->environment('production');
        
        Cookie::queue(cookie('jwt', $accessToken, 15, null, null, $secure, true, false, 'Lax'));
        Cookie::queue(cookie('refreshToken', $refreshToken, 60 * 24 * 7, null, null, $secure, true, false, 'Lax'));
    }

    private function isVideo(string $ext): bool
    {
        return in_array($ext, ['.mp4', '.webm', '.mov']);
    }

    private function detectExtensionFromSignature(string $path): ?string
    {
        $handle = fopen($path, 'rb');
        $bytes = fread($handle, 16);
        fclose($handle);

        if (strlen($bytes) < 4) return null;

        if (str_starts_with($bytes, "\xFF\xD8\xFF")) return ".jpg";
        if (str_starts_with($bytes, "\x89\x50\x4E\x47")) return ".png";
        if (str_starts_with($bytes, "\x1A\x45\xDF\xA3")) return ".webm";
        if (strlen($bytes) >= 8 && substr($bytes, 4, 4) === "ftyp") return ".mp4";

        return null;
    }

    public function getAllPages()
    {
        $pages = \App\Models\ContentPage::all();
        return response()->json($pages);
    }

    public function getPageBySlug($slug)
    {
        $page = \App\Models\ContentPage::where('slug', $slug)->firstOrFail();
        return response()->json($page);
    }
}