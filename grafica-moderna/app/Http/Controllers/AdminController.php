<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cookie;
use Intervention\Image\Facades\Image; // Requer: composer require intervention/image
use Symfony\Component\HtmlSanitizer\HtmlSanitizer; // Requer: composer require symfony/html-sanitizer
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

use App\Services\AuthService;
use App\Services\DashboardService;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\ContentService;
use App\Services\CouponService;
use App\Services\UnitOfWork; // Assumindo padrão UoW ou Repositories

// Form Requests
use App\Http\Requests\LoginRequest; // Valida login
use App\Http\Requests\ProductRequest; // Valida produtos

// Resources
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\OrderResource; // Criado anteriormente
use App\Http\Resources\ProductResource; // Criado anteriormente
use App\Http\Resources\CouponResource; // Criado anteriormente
use App\Http\Resources\ContentPageResource;
use App\Http\Resources\EmailTemplateResource;

class AdminController extends Controller
{
    private const MAX_FILE_SIZE = 52428800; // 50MB (50 * 1024 * 1024)
    private const MAX_IMAGE_DIMENSION = 2048;

    protected AuthService $authService;
    protected DashboardService $dashboardService;
    protected ProductService $productService;
    protected OrderService $orderService;
    protected ContentService $contentService;
    protected CouponService $couponService;
    protected UnitOfWork $uow; // Ou repositórios individuais se não usar UoW
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
        $this->authService = $authService;
        $this->dashboardService = $dashboardService;
        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->contentService = $contentService;
        $this->couponService = $couponService;
        $this->uow = $uow;

        // Configuração do Sanitizer igual ao Ganss.Xss do C# (AllowSafeElements)
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

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        
        // IsAdminLogin = true (Lógica do C#)
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
        $stats = $this->dashboardService->getAnalytics(); // Service retorna array
        return new DashboardStatsResource($stats);
    }

    // ======================================================
    // UPLOAD (Lógica Espelhada do C#)
    // ======================================================

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
            // Detecção manual de assinatura (Magic Bytes) igual ao C#
            $extension = $this->detectExtensionFromSignature($file->getRealPath());
            
            if (!$extension) {
                return response()->json(['message' => 'O arquivo parece estar corrompido ou tem um formato não permitido.'], 400);
            }

            $fileName = Str::uuid() . $extension;
            // Caminho: storage/app/public/uploads
            $path = "uploads/{$fileName}";

            if ($this->isVideo($extension)) {
                // Salva vídeo diretamente
                $file->storeAs('public/uploads', $fileName);
            } else {
                // Processa imagem com Intervention (Simulando ImageSharp)
                $image = Image::make($file->getRealPath());
                
                if ($image->width() > self::MAX_IMAGE_DIMENSION || $image->height() > self::MAX_IMAGE_DIMENSION) {
                    $image->resize(self::MAX_IMAGE_DIMENSION, self::MAX_IMAGE_DIMENSION, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize(); // ResizeMode.Max
                    });
                }
                
                // Salva no storage publico
                Storage::disk('public')->put($path, (string) $image->encode());
            }

            // Retorna URL completa
            $fileUrl = asset("storage/{$path}");
            return response()->json(['url' => $fileUrl]);

        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Erro ao processar arquivo.',
                'details' => $ex->getMessage()
            ], 500);
        }
    }

    // ======================================================
    // ORDERS
    // ======================================================

    public function getOrders(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $pageSize = (int) $request->query('pageSize', 10);

        // Service deve retornar LengthAwarePaginator
        $orders = $this->orderService->getAllOrders($page, $pageSize);
        
        return OrderResource::collection($orders);
    }

    public function updateOrderStatus(string $id, Request $request)
    {
        // Validação simples inline ou DTO
        $request->validate(['status' => 'required|string']);

        try {
            $this->orderService->updateAdminOrder($id, $request->input('status'));
            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // ======================================================
    // PRODUCTS
    // ======================================================

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
        
        // CreatedAtAction simulado retornando 201 e o recurso
        return response()->json(new ProductResource($product), 201);
    }

    public function updateProduct(string $id, ProductRequest $request)
    {
        try {
            $this->productService->update($id, $request->validated());
            return response()->noContent();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }
    }

    public function deleteProduct(string $id)
    {
        try {
            $this->productService->delete($id);
            return response()->noContent();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }
    }

    // ======================================================
    // COUPONS
    // ======================================================

    public function getCoupons()
    {
        $coupons = $this->couponService->getAll();
        return CouponResource::collection($coupons);
    }

    public function createCoupon(Request $request)
    {
        // Validação manual ou DTO. C# usa CreateCouponDto.
        $data = $request->validate([
            'code' => 'required|string',
            'discountPercentage' => 'required|numeric|min:1|max:100',
            'validityDays' => 'required|integer|min:1'
        ]);

        try {
            $coupon = $this->couponService->create($data);
            return response()->json(new CouponResource($coupon));
        } catch (\Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 400);
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
        $data = $request->validate([
            'title' => 'required|string',
            'slug' => 'required|string',
            'content' => 'required|string'
        ]);

        $data['content'] = $this->sanitizer->sanitize($data['content']);
        $page = $this->contentService->createPage($data);

        return response()->json(new ContentPageResource($page));
    }

    public function updatePage(string $slug, Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string'
        ]);

        $data['content'] = $this->sanitizer->sanitize($data['content']);
        $this->contentService->updatePage($slug, $data);

        return response()->noContent(); // Ok() vazio
    }

    public function updateSettings(Request $request)
    {
        // C# recebe Dictionary<string, string>, Laravel recebe array associativo
        $settings = $request->all();
        $this->contentService->updateSettings($settings);
        return response()->noContent();
    }

    public function getEmailTemplates()
    {
        // Assumindo que UnitOfWork tem acesso ao repo de EmailTemplates
        // Se não usar UoW, injetar EmailTemplateRepository
        $templates = \App\Models\EmailTemplate::all();
        return EmailTemplateResource::collection($templates);
    }

    public function getEmailTemplateById(string $id)
    {
        $template = \App\Models\EmailTemplate::find($id);
        if (!$template) return response()->json(['message' => 'Template não encontrado.'], 404);
        
        return new EmailTemplateResource($template);
    }

    public function updateEmailTemplate(string $id, Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'bodyContent' => 'required|string' // Frontend manda bodyContent (camelCase)
        ]);

        $template = \App\Models\EmailTemplate::find($id);
        if (!$template) return response()->json(['message' => 'Template não encontrado.'], 404);

        $template->subject = $request->input('subject');
        $template->body_content = $request->input('bodyContent'); // Mapeia para snake_case
        $template->updated_at = now();
        $template->save();

        return response()->noContent();
    }

    // ======================================================
    // PRIVATE HELPERS
    // ======================================================

    private function setTokenCookies(string $accessToken, string $refreshToken): void
    {
        // Access Token: 15 minutos
        Cookie::queue(cookie('jwt', $accessToken, 15, null, null, true, true, false, 'Lax'));
        
        // Refresh Token: 7 dias
        Cookie::queue(cookie('refreshToken', $refreshToken, 60 * 24 * 7, null, null, true, true, false, 'Lax'));
    }

    private function isVideo(string $ext): bool
    {
        return in_array($ext, ['.mp4', '.webm', '.mov']);
    }

    private function detectExtensionFromSignature(string $path): ?string
    {
        // Lê os primeiros 16 bytes do arquivo
        $handle = fopen($path, 'rb');
        $bytes = fread($handle, 16);
        fclose($handle);

        if (strlen($bytes) < 4) return null;

        // Comparação binária idêntica ao C#
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) return ".jpg";
        if (str_starts_with($bytes, "\x89\x50\x4E\x47")) return ".png"; // PNG signature
        if (str_starts_with($bytes, "\x1A\x45\xDF\xA3")) return ".webm";
        
        // Verifica MP4 (ftyp a partir do byte 4)
        if (strlen($bytes) >= 8 && substr($bytes, 4, 4) === "ftyp") return ".mp4";

        return null;
    }
}