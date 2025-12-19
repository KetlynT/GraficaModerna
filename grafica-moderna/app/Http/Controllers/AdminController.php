<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardService;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\ContentService;
use App\Services\CouponService;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\EmailTemplate;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

class AdminController extends Controller
{
    protected $dashboardService;
    protected $productService;
    protected $orderService;
    protected $contentService;
    protected $couponService;
    protected $sanitizer;
    
    public function __construct(
        DashboardService $dashboardService,
        ProductService $productService,
        OrderService $orderService,
        ContentService $contentService,
        CouponService $couponService
    ) {
        // Middleware 'admin' é aplicado na rota
        $this->dashboardService = $dashboardService;
        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->contentService = $contentService;
        $this->couponService = $couponService;

        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('class', '*') // Permite classes CSS (ex: Tailwind)
            ->allowAttribute('style', '*') // Cuidado com style, mas às vezes necessário em CMS
            ->allowElements(['img', 'iframe', 'figure', 'figcaption']); // Tags extras permitidas

        $this->sanitizer = new HtmlSanitizer($config);
    }

    // ==========================================
    // DASHBOARD
    // ==========================================
    public function getDashboardData(Request $request)
    {
        $range = $request->query('range', '7d');
        return response()->json($this->dashboardService->getAnalytics($range));
    }

    // ==========================================
    // PRODUTOS
    // ==========================================
    public function createProduct(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'weight' => 'required|numeric',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'length' => 'required|integer',
            'stock_quantity' => 'required|integer',
            'images.*' => 'image|max:2048' // Validação de array de imagens
        ]);

        // Upload
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                $imageUrls[] = asset('storage/' . $path);
            }
        }
        $data['image_urls'] = $imageUrls;
        $data['is_active'] = true;

        return response()->json($this->productService->create($data), 201);
    }

    public function updateProduct(Request $request, $id)
    {
        $data = $request->all();
        // Lógica simplificada de atualização
        // Num cenário real, você verificaria se enviou novas imagens para substituir ou adicionar
        
        $this->productService->update($id, $data);
        return response()->noContent();
    }

    public function deleteProduct($id)
    {
        $this->productService->delete($id);
        return response()->noContent();
    }

    // ==========================================
    // PEDIDOS
    // ==========================================
    public function getAllOrders(Request $request)
    {
        $query = Order::with('user')->orderBy('created_at', 'desc');
        
        // Filtros simples
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);
        
        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $order->update(['status' => $request->status]);

        // Registrar Histórico
        $order->history()->create([
            'status' => $request->status,
            'message' => "Status alterado de '$oldStatus' para '{$request->status}' pelo Admin.",
            'changed_by' => Auth::user()->full_name ?? 'Admin'
        ]);

        return response()->json($order);
    }

    // ==========================================
    // CUPONS
    // ==========================================
    public function getCoupons()
    {
        return response()->json(Coupon::all());
    }

    public function createCoupon(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|unique:coupons',
            'discountPercentage' => 'required|numeric',
            'validityDays' => 'required|integer'
        ]);

        $coupon = Coupon::create([
            'code' => strtoupper($data['code']),
            'discount_percentage' => $data['discountPercentage'],
            'expiry_date' => now()->addDays($data['validityDays']),
            'is_active' => true
        ]);

        return response()->json($coupon, 201);
    }

    public function deleteCoupon($id)
    {
        Coupon::destroy($id);
        return response()->noContent();
    }

    // ==========================================
    // CONTEÚDO E SETTINGS
    // ==========================================
    public function updateSettings(Request $request)
    {
        $data = $request->all();
        foreach ($data as $key => $value) {
            $this->contentService->updateSetting($key, $value);
        }
        return response()->noContent();
    }

    public function savePage(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required',
            'title' => 'required',
            'content' => 'required'
        ]);
        $data['content'] = $this->sanitizer->sanitize($data['content']);
        $this->contentService->savePage($data);
        return response()->noContent();
    }

    // ==========================================
    // EMAIL TEMPLATES
    // ==========================================
    public function getEmailTemplates()
    {
        return response()->json(EmailTemplate::all());
    }

    public function updateEmailTemplate(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $data = $request->validate([
            'subject' => 'required',
            'html_content' => 'required'
        ]);
        $data['html_content'] = $this->sanitizer->sanitize($data['html_content']);
        $template->update($data);
        return response()->json($template);
    }
}