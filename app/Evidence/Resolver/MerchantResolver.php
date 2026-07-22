<?php

declare(strict_types=1);

namespace App\Evidence\Resolver;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * MerchantResolver — Match merchant name ke merchant yang sudah ada.
 *
 * Saat ini belum ada tabel merchants.
 * Resolver ini menormalkan nama merchant dan mengembalikan confidence berdasarkan matching.
 * Akan diimplementasi penuh ketika tabel merchants tersedia.
 */
class MerchantResolver
{
    private array $restaurantMerchants;

    private array $retailMerchants;

    private array $pharmacyMerchants;

    private array $merchantAliases;

    public function __construct()
    {
        $this->restaurantMerchants = config('shopping_parser.restaurant_merchants', []);
        $this->retailMerchants = config('shopping_parser.retail_merchants', []);
        $this->pharmacyMerchants = config('shopping_parser.pharmacy_merchants', []);
        $shoppingAliases = config('shopping_parser.merchant_aliases', []);
        $qrisAliases = config('qris_parser.merchant_aliases', []);
        $this->merchantAliases = array_merge($shoppingAliases, $qrisAliases);
    }

    /**
     * Resolve merchant dari merchant_name.
     *
     * @return array{merchant_id: int|null, merchant_name: string|null, merchant_category: string|null, confidence: float}
     */
    public function resolve(User $user, ?string $merchantName): array
    {
        if (! $merchantName) {
            return [
                'merchant_id' => null,
                'merchant_name' => null,
                'merchant_category' => null,
                'confidence' => 0.0,
            ];
        }

        // Normalisasi nama merchant
        $normalizedName = $this->normalizeMerchantName($merchantName);

        // Deteksi kategori merchant
        $category = $this->detectMerchantCategory($normalizedName);

        // TODO: Ketika tabel merchants tersedia, lakukan matching di sini
        // $merchant = Merchant::where('user_id', $user->id)
        //     ->whereRaw('LOWER(name) = ?', [strtolower($normalizedName)])
        //     ->first();

        // Hitung confidence berdasarkan apakah merchant dikenali
        $confidence = 0.5;
        if ($category !== null) {
            $confidence = 0.85;
        }

        Log::info('Merchant resolver resolved', [
            'original' => $merchantName,
            'normalized' => $normalizedName,
            'category' => $category,
            'confidence' => $confidence,
        ]);

        return [
            'merchant_id' => null,
            'merchant_name' => $normalizedName,
            'merchant_category' => $category,
            'confidence' => $confidence,
        ];
    }

    /**
     * Deteksi kategori merchant.
     *
     * @return string|null 'restaurant', 'retail', 'pharmacy', atau null
     */
    private function detectMerchantCategory(string $name): ?string
    {
        $lower = strtolower($name);

        foreach ($this->restaurantMerchants as $merchant) {
            if (str_contains($lower, strtolower($merchant))) {
                return 'restaurant';
            }
        }

        foreach ($this->retailMerchants as $merchant) {
            if (str_contains($lower, strtolower($merchant))) {
                return 'retail';
            }
        }

        foreach ($this->pharmacyMerchants as $merchant) {
            if (str_contains($lower, strtolower($merchant))) {
                return 'pharmacy';
            }
        }

        return null;
    }

    /**
     * Normalisasi nama merchant.
     */
    private function normalizeMerchantName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);

        // Coba resolve dari merchant aliases
        $lower = strtolower($name);
        foreach ($this->merchantAliases as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($lower, strtolower($alias))) {
                    return $canonical;
                }
            }
        }

        // Hapus prefix yang tidak perlu
        $prefixes = ['toko', 'warung', 'kedai', 'Outlet', 'Store', 'Shop'];
        foreach ($prefixes as $prefix) {
            if (stripos($name, $prefix.' ') === 0) {
                $name = substr($name, strlen($prefix.' '));
                break;
            }
        }

        return trim($name);
    }
}
