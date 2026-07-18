<?php

namespace JordJD\EloquentAttributeValuePrediction\Exceptions;

use RuntimeException;

class ModelNotTrainedException extends RuntimeException
{
    public static function forModel(string $modelClass, string $attribute): self
    {
        return new self(
            'No trained prediction model exists for '.$modelClass.'::'.$attribute.'. '
            .'Run `php artisan eavp:train '.ltrim($modelClass, '\\').'` first.'
        );
    }
}
