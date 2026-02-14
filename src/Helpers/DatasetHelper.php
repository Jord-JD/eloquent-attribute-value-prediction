<?php

namespace JordJD\EloquentAttributeValuePrediction\Helpers;

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

            $value = $model->getAttributeValue($attribute);

            if ($value === null) {
                if ($model->isAttributeContinuous($attribute)) {
                    $value = NAN;
                } else {
                    $value = '?';
                }
            }

            $sample[] = $value;
        }

        return $sample;
    }
}