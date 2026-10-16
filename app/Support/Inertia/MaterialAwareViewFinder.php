<?php

namespace App\Support\Inertia;

use Illuminate\View\FileViewFinder;
use InvalidArgumentException;

/**
 * View finder yang mengenali namespace halaman Material (`Material/*`).
 *
 * Halaman M3 tinggal di `resources/js/Material/Pages/` (terpisah dari
 * `resources/js/Pages/` sesuai keputusan Multi-UI), sehingga finder bawaan
 * Inertia — yang hanya mencari di `inertia.pages.paths` — tidak dapat
 * menemukan `Material/Dashboard`. Finder ini mendelegasikan dulu ke
 * perilaku bawaan, lalu mencoba root namespace yang terdaftar.
 *
 * Dipakai saat `inertia.pages.ensure_pages_exist` aktif (render) dan oleh
 * `assertInertia()->component()` (testing). Di luar itu tidak ada
 * pengaruh runtime: resolusi halaman produksi tetap ditangani Vite glob
 * di `resources/js/app.js`.
 */
class MaterialAwareViewFinder extends FileViewFinder
{
    public function find($name)
    {
        try {
            return parent::find($name);
        } catch (InvalidArgumentException) {
            // Lanjut ke root namespace di bawah.
        }

        foreach ($this->namespacedRoots() as $prefix => $root) {
            if (str_starts_with($name, $prefix)) {
                $finder = new FileViewFinder($this->files, [$root], $this->extensions);

                return $finder->find(substr($name, strlen($prefix)));
            }
        }

        throw new InvalidArgumentException("View [{$name}] not found.");
    }

    /**
     * @return array<string, string> prefix komponen => direktori root halaman.
     */
    protected function namespacedRoots(): array
    {
        return [
            'Material/' => resource_path('js/Material/Pages'),
        ];
    }
}
