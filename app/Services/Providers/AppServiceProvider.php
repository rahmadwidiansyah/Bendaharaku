use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Providers\PythonNLPProvider;

/**
 * Register any application services.
 */
public function register(): void
{
    // Binding Kontrak AI ke PythonNLPProvider sebagai default driver saat ini
    $this->app->bind(AIProviderInterface::class, PythonNLPProvider::class);
}