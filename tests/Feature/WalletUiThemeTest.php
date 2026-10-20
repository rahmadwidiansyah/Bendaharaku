<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletUiThemeTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX_PROPS = ['wallets', 'totalHutang', 'totalPiutang'];

    private const EDIT_PROPS = ['wallet'];

    private const SHOW_PROPS = ['wallet', 'transactions', 'startDate', 'endDate'];

    private function makeWallet(User $user): Wallet
    {
        return $user->wallets()->create([
            'name' => 'BCA',
            'balance' => 1500000,
            'icon' => 'wallet',
            'keyword' => 'bca',
            'group_type' => 'Liquid',
        ]);
    }

    public function test_default_user_gets_default_wallets_index()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);
        $this->makeWallet($user);

        $this->actingAs($user)
            ->get('/wallets')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Wallets/Index')
                ->where('auth.user.ui_theme', 'default')
                ->hasAll(self::INDEX_PROPS));
    }

    public function test_m3_user_gets_material_wallets_index()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);
        $this->makeWallet($user);

        $this->actingAs($user)
            ->get('/wallets')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Wallets/Index')
                ->where('auth.user.ui_theme', 'm3')
                ->hasAll(self::INDEX_PROPS));
    }

    public function test_default_user_gets_default_wallets_create()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get('/wallets/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Wallets/Create'));
    }

    public function test_m3_user_gets_material_wallets_create()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/wallets/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Material/Wallets/Create'));
    }

    public function test_default_user_gets_default_wallets_edit()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);
        $wallet = $this->makeWallet($user);

        $this->actingAs($user)
            ->get("/wallets/{$wallet->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Wallets/Edit')
                ->hasAll(self::EDIT_PROPS));
    }

    public function test_m3_user_gets_material_wallets_edit()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);
        $wallet = $this->makeWallet($user);

        $this->actingAs($user)
            ->get("/wallets/{$wallet->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Wallets/Edit')
                ->hasAll(self::EDIT_PROPS));
    }

    public function test_default_user_gets_default_wallets_show()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);
        $wallet = $this->makeWallet($user);

        $this->actingAs($user)
            ->get("/wallets/{$wallet->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Wallets/Show')
                ->hasAll(self::SHOW_PROPS));
    }

    public function test_m3_user_gets_material_wallets_show()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);
        $wallet = $this->makeWallet($user);

        $this->actingAs($user)
            ->get("/wallets/{$wallet->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Wallets/Show')
                ->hasAll(self::SHOW_PROPS));
    }
}
