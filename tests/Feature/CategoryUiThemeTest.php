<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryUiThemeTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX_PROPS = ['groupedCategories', 'totalCategories'];

    private const CREATE_PROPS = ['types', 'defaultType'];

    private const EDIT_PROPS = ['category', 'types', 'isSystem'];

    private const SHOW_PROPS = [
        'category', 'transactions', 'totalUsage', 'isSystem', 'startDate', 'endDate',
    ];

    private function makeCategory(User $user): Category
    {
        $type = TransactionType::firstOrCreate(
            ['name' => 'Expense'],
            ['keyword' => 'expense'],
        );

        return Category::create([
            'user_id' => $user->id,
            'type_id' => $type->id,
            'category_name' => 'Makan',
            'icon' => 'utensils',
            'keyword' => 'makan',
        ]);
    }

    public function test_default_user_gets_default_categories_index()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);
        $this->makeCategory($user);

        $this->actingAs($user)
            ->get('/categories')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Categories/Index')
                ->where('auth.user.ui_theme', 'default')
                ->hasAll(self::INDEX_PROPS));
    }

    public function test_m3_user_gets_material_categories_index()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);
        $this->makeCategory($user);

        $this->actingAs($user)
            ->get('/categories')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Categories/Index')
                ->where('auth.user.ui_theme', 'm3')
                ->hasAll(self::INDEX_PROPS));
    }

    public function test_default_user_gets_default_categories_create()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get('/categories/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Categories/Create')
                ->hasAll(self::CREATE_PROPS));
    }

    public function test_m3_user_gets_material_categories_create()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/categories/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Categories/Create')
                ->hasAll(self::CREATE_PROPS));
    }

    public function test_default_user_gets_default_categories_edit()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);
        $category = $this->makeCategory($user);

        $this->actingAs($user)
            ->get("/categories/{$category->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Categories/Edit')
                ->hasAll(self::EDIT_PROPS));
    }

    public function test_m3_user_gets_material_categories_edit()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);
        $category = $this->makeCategory($user);

        $this->actingAs($user)
            ->get("/categories/{$category->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Categories/Edit')
                ->hasAll(self::EDIT_PROPS));
    }

    public function test_default_user_gets_default_categories_show()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);
        $category = $this->makeCategory($user);

        $this->actingAs($user)
            ->get("/categories/{$category->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Categories/Show')
                ->hasAll(self::SHOW_PROPS));
    }

    public function test_m3_user_gets_material_categories_show()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);
        $category = $this->makeCategory($user);

        $this->actingAs($user)
            ->get("/categories/{$category->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Categories/Show')
                ->hasAll(self::SHOW_PROPS));
    }
}
