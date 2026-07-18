<?php

namespace JordJD\EloquentAttributeValuePrediction\Helpers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Rubix\ML\Datasets\Unlabeled;

abstract class DatasetHelper
{
    public static function buildUnlabeledDataset($model, string $attributeToPredict): Unlabeled
    {
        $predictableAttributes = $model->registerPredictableAttributes();

        if (!array_key_exists($attributeToPredict, $predictableAttributes)) {
            throw new InvalidArgumentException('Attempted to predict an attribute that is not returned from the model\'s `registerPredictableAttributes` method.');
        }

        $otherAttributes = $predictableAttributes[$attributeToPredict];

        $sample = self::buildSample($model, $otherAttributes);

        return new Unlabeled([$sample]);
    }

    public static function buildSample($model, $attributes)
    {
        $sample = [];

        foreach($attributes as $attribute) {

            $value = strpos($attribute, '.') !== false
                ? data_get($model, $attribute)
                : $model->getAttribute($attribute);

            if ($value === null) {
                $isRelationship = strpos($attribute, '.') !== false
                    || $model->relationLoaded($attribute)
                    || method_exists($model, $attribute);

                if (!$isRelationship && $model->isAttributeContinuous($attribute)) {
                    $value = NAN;
                } else {
                    $value = '?';
                }
            }

            $sample[] = self::normaliseValue($value);
        }

        return $sample;
    }

    public static function normaliseValue($value)
    {
        if ($value instanceof Model) {
            return $value->getMorphClass().':'.$value->getKey();
        }

        if ($value instanceof EloquentCollection) {
            $value = $value->all();
        }

        if (is_array($value)) {
            $normalised = array_map([self::class, 'normaliseValue'], $value);

            if (array_keys($normalised) === range(0, count($normalised) - 1)) {
                sort($normalised, SORT_STRING);
            } else {
                ksort($normalised);
            }

            return json_encode($normalised);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : serialize($value);
        }

        return $value;
    }
}
