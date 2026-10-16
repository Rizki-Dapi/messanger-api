<?php

namespace App\Providers;

use App\Repositories\ChatRepository;
use App\Repositories\ConversationRepository;
use App\Repositories\FriendRepository;
use App\Repositories\Interfaces\ChatRepositoryInterface;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use App\Repositories\Interfaces\FriendRepositoryInterface;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\StoryRepositoryInterface;
use App\Repositories\Interfaces\StoryViewRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\LogRepository;
use App\Repositories\StoryRepository;
use App\Repositories\StoryViewRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
        $this->app->bind(ChatRepositoryInterface::class, ChatRepository::class);
        $this->app->bind(FriendRepositoryInterface::class, FriendRepository::class);
        $this->app->bind(StoryRepositoryInterface::class, StoryRepository::class);
        $this->app->bind(StoryViewRepositoryInterface::class, StoryViewRepository::class);
        $this->app->bind(LogRepositoryInterface::class, LogRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
