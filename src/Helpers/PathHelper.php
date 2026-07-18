<?php

namespace JordJD\EloquentAttributeValuePrediction\Helpers;

abstract class PathHelper
{
    public static function getModelPath($modelClass, $classAttribute)
    {
        $modelDirectory = storage_path('eavp/models/');

        if (!is_dir($modelDirectory)) {
            if (!@mkdir($modelDirectory, 0777, true) && !is_dir($modelDirectory)) {
                throw new \RuntimeException('Unable to create the prediction model directory at '.$modelDirectory.'.');
            }
        }

        $model = new $modelClass;

        $predictableAttributes = $model->registerPredictableAttributes();

        return $modelDirectory.sha1(serialize([$modelClass, $classAttribute, $predictableAttributes])).'.model';
    }
}
