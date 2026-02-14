<?php

namespace JordJD\EloquentAttributeValuePrediction\Helpers;

abstract class PathHelper
{
    public static function getModelPath($modelClass, $classAttribute)
    {
        $modelDirectory = storage_path('eavp/models/');

        if (!is_dir($modelDirectory)) {
            mkdir($modelDirectory, 0777, true);
        }

        $model = new $modelClass;

        $predictableAttributes = $model->registerPredictableAttributes();

        return $modelDirectory.sha1(serialize([$modelClass, $classAttribute, $predictableAttributes])).'.model';
    }
}