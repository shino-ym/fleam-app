<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;


class SellTest extends TestCase
{
    use RefreshDatabase;

     /** @test */
    public function 商品出品画面にて必要な情報正しく保存されている()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user);

        // 出品画面を開く
        $response = $this->get(route('sell.create'));
        $response->assertStatus(200); // 画面が正常に表示されることを確認
        $response->assertSee('出品する');

        // 既存画像をアップロード
        $file = new UploadedFile(
        base_path('tests/_files/sample.png'),
        'sample.png',
        'image/png',
        null,
        true
        );

        $category = Category::factory()->create();

        $itemData =[
            'name'=>'テスト商品',
            'brand_name'=>null,
            'description'=>'これはテスト用の商品です',
            'price'=>'1000',
            'condition'=>'良好',
            'category_id' => [$category->id],
            'item_image' => $file,
        ];

        $response = $this->post(route('sell.store'),$itemData);

        // ステータス確認（保存成功）
        $response->assertStatus(302);

        $this->assertDatabaseHas('items',[
            'name' => 'テスト商品',
            'brand_name' => null,
            'description' => 'これはテスト用の商品です',
            'price' => 1000,
            'condition' => '良好',
            'item_image' => 'images/' . $file->hashName(),
            'user_id' => $user->id,
        ]);

        // 中間テーブル側も確認
        $this->assertDatabaseHas('category_item', [
            'item_id' => Item::first()->id,
            'category_id' => $category->id,
        ]);

        // ストレージ保存確認（フェイク）
        Storage::disk('public')->assertExists('images/' . $file->hashName());
    }
}
