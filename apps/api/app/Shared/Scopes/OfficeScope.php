<?php

declare(strict_types=1);

namespace App\Shared\Scopes;

use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

final class OfficeScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user instanceof Operator && ! $user->hasRole('system_admin')) {
            if ($user->office_id !== null) {
                $builder->where("{$model->getTable()}.office_id", $user->office_id);
            } else {
                $builder->whereRaw('1 = 0');
            }
        }
    }
}
