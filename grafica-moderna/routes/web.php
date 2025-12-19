use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

// Rotas Públicas
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/produto/{id}', [ProductController::class, 'show'])->name('products.show');

// Rotas do Carrinho
Route::get('/carrinho', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrinho/adicionar/{id}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrinho/atualizar/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrinho/remover/{id}', [CartController::class, 'remove'])->name('cart.remove');