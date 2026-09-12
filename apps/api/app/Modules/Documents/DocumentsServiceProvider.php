<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Shared\Crypto\KeyRing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

final class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EncryptedObjectStore::class, function ($app): EncryptedObjectStore {
            return new EncryptedObjectStore(
                $app->make(KeyRing::class),
                Storage::disk('documents')
            );
        });
    }

    public function boot(): void {}
}
