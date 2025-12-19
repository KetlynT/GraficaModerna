<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Services\AuthService;
use App\Services\DashboardService;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\ContentService;
use App\Services\CouponService;
use App\Services\UnitOfWork;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Intervention\Image\Facades\Image;

class AdminController extends Controller
{
    private const MAX_FILE_SIZE = 52428800; // 50MB
    private const MAX_IMAGE_DIMENSION = 2048;

    protected AuthService $authService;
    protected DashboardService $dashboardService;
    protected ProductService $productService;
    protected OrderService $orderService;
    protected ContentService $contentService;
    protected CouponService $couponService;
    protected UnitOfWork $uow;
    protected HtmlSanitizer $sanitizer;

    public function __construct(
        AuthService $authService,
        DashboardService $dashboardService,
        ProductService $productService,
        OrderService $orderService,
        ContentService $contentService,
        CouponService $couponService,
        UnitOfWork $uow
    ) {
        $this->middleware(['auth:api', 'role:admin', 'throttle:admin'])->except('login');

        $this->authService = $authService;
        $this->dashboardService = $dashboardService;
        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->contentService = $contentService;
        $this->couponService = $couponService;
        $this->uow = $uow;

        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('class', '*')
            ->allowAttribute('style', '*')
            ->allowElements(['img', 'iframe', 'figure', 'figcaption']);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    // ======================================================
    // AUTH & DASHBOARD
    // ======================================================

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $result = $this->authService->login([
            'email' => $request->email,
            'password' => $request->password,
            'isAdminLogin' => true
        ]);

        $this->setTokenCookies($result->accessToken, $result->refreshToken);

        return response()->json([
            'email' => $result->email,
            'role' => $result->role,
            'message' => 'Login administrativo realizado com sucesso.'
        ]);
    }

    public function dashboardStats()
    {
        return response()->json(
            $this->dashboardService->getStats()
        );
    }

    // ======================================================
    // UPLOAD
    // ======================================================

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200'
        ]);

        $file = $request->file('file');

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return response()->json([
                'message' => 'Arquivo excede o limite de 50MB.'
            ], 400);
        }

        $extension = $this->detectExtensionFromSignature($file->getRealPath());
        if (!$extension) {
            return response()->json([
                'message' => 'Formato de arquivo inválido ou corrompido.'
            ], 400);
        }

        $filename = Str::uuid() . $extension;
        $path = storage_path("app/public/uploads/{$filename}");

        if ($this->isVideo($extension)) {
            $file->move(dirname($path), $filename);
        } else {
            $image = Image::make($file->getRealPath());
            if ($image->width() > self::MAX_IMAGE_DIMENSION || $image->height() > self::MAX_IMAGE_DIMENSION) {
                $image->resize(
                    self::MAX_IMAGE_DIMENSION,
                    self::MAX_IMAGE_DIMENSION,
                    fn ($c) => $c->aspectRatio()->upsize()
                );
            }
            $image->save($path);
        }

        return response()->json([
            'url' => asset("storage/uploads/{$filename}")
        ]);
    }

    // ======================================================
    // ORDERS
    // ======================================================

    public function getOrders(Request $request)
    {
        return response()->json(
            $this->orderService->getAll(
                $request->query('page', 1),
                $request->query('pageSize', 10)
            )
        );
    }

    public function updateOrderStatus(string $id, Request $request)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        try {
            $this->orderService->updateAdminOrder($id, $request->status);
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // ======================================================
    // PRODUCTS
    // ======================================================

    public function getProducts(Request $request)
    {
        return response()->json(
            $this->productService->getCatalog(
                $request->query('search'),
                $request->query('sort'),
                $request->query('order'),
                $request->query('page', 1),
                $request->query('pageSize', 8)
            )
        );
    }

    public function getProductById(string $id)
    {
        $product = $this->productService->getById($id);
        if (!$product) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }
        return response()->json($product);
    }

    public function createProduct(Request $request)
    {
        return response()->json(
            $this->productService->create($request->all()),
            201
        );
    }

    public function updateProduct(string $id, Request $request)
    {
        try {
            $this->productService->update($id, $request->all());
            return response()->noContent();
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }
    }

    public function deleteProduct(string $id)
    {
        try {
            $this->productService->delete($id);
            return response()->noContent();
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }
    }

    // ======================================================
    // COUPONS
    // ======================================================

    public function getCoupons()
    {
        return response()->json(
            $this->couponService->getAll()
        );
    }

    public function createCoupon(Request $request)
    {
        try {
            return response()->json(
                $this->couponService->create($request->all())
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteCoupon(string $id)
    {
        $this->couponService->delete($id);
        return response()->noContent();
    }

    // ======================================================
    // CONTENT & SETTINGS
    // ======================================================

    public function createPage(Request $request)
    {
        $data = $request->all();
        $data['content'] = $this->sanitizer->sanitize($data['content']);

        return response()->json(
            $this->contentService->createPage($data)
        );
    }

    public function updatePage(string $slug, Request $request)
    {
        $data = $request->all();
        $data['content'] = $this->sanitizer->sanitize($data['content']);

        $this->contentService->updatePage($slug, $data);
        return response()->ok();
    }

    public function updateSettings(Request $request)
    {
        $this->contentService->updateSettings($request->all());
        return response()->ok();
    }

    public function getEmailTemplates()
    {
        return response()->json(
            $this->uow->emailTemplates()->all()
        );
    }

    public function updateEmailTemplate(string $id, Request $request)
    {
        $template = $this->uow->emailTemplates()->find($id);
        if (!$template) {
            return response()->json(['message' => 'Template não encontrado.'], 404);
        }

        $template->subject = $request->subject;
        $template->body_content = $request->body_content;
        $template->updated_at = now();

        $this->uow->emailTemplates()->save($template);
        $this->uow->commit();

        return response()->noContent();
    }

    // ======================================================
    // HELPERS
    // ======================================================

    private function setTokenCookies(string $access, string $refresh): void
    {
        cookie()->queue(cookie(
            'accessToken',
            $access,
            15,
            null,
            null,
            true,
            true,
            false,
            'Lax'
        ));

        cookie()->queue(cookie(
            'refreshToken',
            $refresh,
            10080,
            null,
            null,
            true,
            true,
            false,
            'Lax'
        ));
    }

    private function isVideo(string $ext): bool
    {
        return in_array($ext, ['.mp4', '.webm', '.mov']);
    }

    private function detectExtensionFromSignature(string $path): ?string
    {
        $bytes = file_get_contents($path, false, null, 0, 16);
        return match (true) {
            str_starts_with($bytes, "\xFF\xD8\xFF") => '.jpg',
            str_starts_with($bytes, "\x89PNG") => '.png',
            str_starts_with($bytes, "\x1A\x45\xDF\xA3") => '.webm',
            substr($bytes, 4, 4) === 'ftyp' => '.mp4',
            default => null
        };
    }
}
