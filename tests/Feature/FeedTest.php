<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_feed(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create([
            'username' => 'post_author',
            'profile_photo' => 'profiles/author.jpg',
            'cover_photo' => 'covers/author.jpg',
        ]);
        $post = Post::factory()->for($author)->create([
            'content' => 'This is a feed post.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/feed');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Feed retrieved successfully.')
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.content', 'This is a feed post.')
            ->assertJsonPath('data.0.author.id', $author->id)
            ->assertJsonPath('data.0.author.name', $author->name)
            ->assertJsonPath('data.0.author.username', 'post_author')
            ->assertJsonPath('data.0.author.profile_photo', 'profiles/author.jpg')
            ->assertJsonPath('data.0.author.cover_photo', 'covers/author.jpg')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_authenticated_user_can_create_post(): void
    {
        $user = User::factory()->create([
            'username' => 'creator',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', [
            'content' => 'A new post from the API.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Post created successfully.')
            ->assertJsonPath('post.content', 'A new post from the API.')
            ->assertJsonPath('post.author.id', $user->id)
            ->assertJsonPath('post.author.username', 'creator');

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'A new post from the API.',
        ]);
    }

    public function test_post_content_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/posts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    public function test_feed_and_post_routes_require_authentication(): void
    {
        $this->getJson('/api/feed')->assertUnauthorized();
        $this->postJson('/api/posts', ['content' => 'Unauthorized post.'])->assertUnauthorized();
    }
}
