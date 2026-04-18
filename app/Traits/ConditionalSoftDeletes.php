<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

trait ConditionalSoftDeletes
{
    use SoftDeletes {
        bootSoftDeletes as protected bootLaravelSoftDeletes;
        initializeSoftDeletes as protected initializeLaravelSoftDeletes;
        restore as protected laravelRestore;
        trashed as protected laravelTrashed;
        performDeleteOnModel as protected laravelPerformDeleteOnModel;
    }

    public static function bootSoftDeletes()
    {
        $instance = new static();

        if ($instance->supportsSoftDeletes()) {
            static::bootLaravelSoftDeletes();
        }
    }

    public function initializeSoftDeletes()
    {
        if ($this->supportsSoftDeletes()) {
            $this->initializeLaravelSoftDeletes();
        }
    }

    public function restore()
    {
        if (! $this->supportsSoftDeletes()) {
            return false;
        }

        return $this->laravelRestore();
    }

    public function trashed()
    {
        if (! $this->supportsSoftDeletes()) {
            return false;
        }

        return $this->laravelTrashed();
    }

    protected function performDeleteOnModel()
    {
        if (! $this->supportsSoftDeletes()) {
            return parent::performDeleteOnModel();
        }

        return $this->laravelPerformDeleteOnModel();
    }

    protected function supportsSoftDeletes()
    {
        return Schema::hasColumn($this->getTable(), $this->getDeletedAtColumn());
    }
}
