<?php

namespace JordJD\EloquentAttributeValuePrediction\Console\Commands;

use JordJD\EloquentAttributeValuePrediction\Helpers\DatasetHelper;
use JordJD\EloquentAttributeValuePrediction\Helpers\PathHelper;
use JordJD\EloquentAttributeValuePrediction\Interfaces\HasPredictableAttributes;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Rubix\ML\Classifiers\KDNeighbors;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\DataType;
use Rubix\ML\Estimator;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Pipeline;
use Rubix\ML\Regressors\KDNeighborsRegressor;
use Rubix\ML\Transformers\OneHotEncoder;
use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Transformers\MissingDataImputer;


class Train extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eavp:train {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train a model';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $modelClass = $this->argument('model');

        if (!class_exists($modelClass)) {
            $this->error('The provided model class does not exist.');

            return 1;
        }

        /** @var Model $model */
        $model = new $modelClass;

        if (!$model instanceof Model) {
            $this->error('The provided class is not an Eloquent model.');
            return 1;
        }

        if (!$model instanceof HasPredictableAttributes) {
            $this->error('The provided class does not implement HasPredictableAttributes.');

            return 1;
        }

        // Get all model attributes
        $attributes = $model->registerPredictableAttributes();

        // Get estimators
        $estimators = $model->registerEstimators();

        foreach($attributes as $classAttribute => $attributesToTrainFrom) {
            $this->line('Training model for '.$classAttribute.' attribute from '.count($attributesToTrainFrom).' other attribute(s)...');

            $modelPath = PathHelper::getModelPath(get_class($model), $classAttribute);

            if (array_key_exists($classAttribute, $estimators)) {
                $baseEstimator = $estimators[$classAttribute];
            } else {
                $baseEstimator = $this->getDefaultBaseEstimator($model->isAttributeContinuous($classAttribute));
            }

            $estimator = $this->getEstimator($modelPath, $baseEstimator);

            $samples = [];
            $classes = [];

            $model->query()->chunk(100, function ($instances) use ($attributesToTrainFrom, $classAttribute, &$samples, &$classes) {
                foreach ($instances as $instance) {
                    $samples[] = DatasetHelper::buildSample($instance, $attributesToTrainFrom);

                    $classValue = $instance->getAttribute($classAttribute);
                    if ($classValue === null) {
                        $classValue = '?';
                    }
                    $classes[] = DatasetHelper::normaliseValue($classValue);
                }
            });

            $dataset = new Labeled($samples, $classes);

            $estimator->train($dataset);

            $estimator->save();

            $this->line('Training completed for '.$classAttribute.'.');
        }

        $this->line('All training completed.');

        return 0;
    }

    private function getEstimator(string $modelPath, Estimator $baseEstimator): Estimator
    {
        $estimator = new PersistentModel(
            new Pipeline($this->getTransformers($baseEstimator), $baseEstimator),
            new Filesystem($modelPath)
        );

        return $estimator;
    }

    private function getDefaultBaseEstimator(bool $continuous): Estimator
    {
        $baseEstimator = new KDNeighbors();

        if ($continuous) {
            $baseEstimator = new KDNeighborsRegressor();
        }

        return $baseEstimator;
    }

    private function getTransformers(Estimator $estimator): array
    {
        $dataTypes = $estimator->compatibility();

        $transformers = [];
        $transformers[] = new MissingDataImputer();

        if (!in_array(DataType::categorical(), $dataTypes) && in_array(DataType::continuous(), $dataTypes)) {
            $transformers[] = new OneHotEncoder();
        }

        $transformers[] = new ZScaleStandardizer();

        return $transformers;
    }
}
