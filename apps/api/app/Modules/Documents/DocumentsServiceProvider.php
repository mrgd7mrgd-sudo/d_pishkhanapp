<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Modules\Documents\Domain\Models\VaultDocument;
use App\Modules\Documents\Domain\Models\VaultDocumentAttribute;
use App\Modules\Documents\Domain\Models\VaultDocumentVersion;
use App\Modules\Documents\Infrastructure\Policies\VaultDocumentAttributePolicy;
use App\Modules\Documents\Infrastructure\Policies\VaultDocumentPolicy;
use App\Modules\Documents\Infrastructure\Policies\VaultDocumentVersionPolicy;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Shared\Crypto\KeyRing;
use Illuminate\Support\Facades\Gate;
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

    public function boot(): void
    {
        Gate::policy(
            VaultDocument::class,
            VaultDocumentPolicy::class
        );
        Gate::policy(
            VaultDocumentVersion::class,
            VaultDocumentVersionPolicy::class
        );
        Gate::policy(
            VaultDocumentAttribute::class,
            VaultDocumentAttributePolicy::class
        );
    }
}
