<?php

namespace JordJD\EloquentAttributeValuePrediction\Console\Commands;

use JordJD\EloquentAttributeValuePrediction\Helpers\DatasetHelper;
use JordJD\EloquentAttributeValuePrediction\Helpers\PathHelper;
use JordJD\EloquentAttributeValuePrediction\Interfaces\HasPredictableAttributes;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Rubix\ML\Classifiers\KDNeighbors;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\Classifiers\MultilayerPerceptron;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\DataType;
use Rubix\ML\Estimator;
use Rubix\ML\NeuralNet\Layers\Dense;
use Rubix\ML\NeuralNet\Layers\PReLU;
use Rubix\ML\NeuralNet\Optimizers\Adam;
use Rubix\ML\Other\Loggers\BlackHole;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Pipeline;
use Rubix\ML\Regressors\KDNeighborsRegressor;
use Rubix\ML\Regressors\KNNRegressor;
use Rubix\ML\Regressors\MLPRegressor;
use Rubix\ML\Transformers\OneHotEncoder;
use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Transformers\MissingDataImputer;
use Rubix\ML\Other\Loggers\Screen;
use Rubix\ML\Transformers\NumericStringConverter;


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

        /** @var Model $model */
        $model = new $modelClass;

        if (!$model instanceof Model) {
            $this->error('The provided class is not an Eloquent model.');
            die;
        }

        if (!$model instanceof HasPredictableAttributes) {
            $this->error('The provided class does not implement the AttributeValuePredictionModelInterface.');
            die;
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

                    $classValue = $instance->getAttributeValue($classAttribute);
                    if ($classValue === null) {
                        $classValue = '?';
                    }
                    if (is_object($classValue) || is_array($classValue)) {
                        $classValue = serialize($classValue);
                    }
                    $classes[] = $classValue;
                }
            });

            $dataset = new Labeled($samples, $classes);

            $estimator->train($dataset);

            $estimator->setLogger(new BlackHole());
            $estimator->save();

            $this->line('Training completed for '.$classAttribute.'.');
        }

        $this->line('All training completed.');
    }

    private function getEstimator(string $modelPath, Estimator $baseEstimator): Estimator
    {
        $estimator = new PersistentModel(
            new Pipeline($this->getTransformers($baseEstimator), $baseEstimator),
            new Filesystem($modelPath)
        );

        if (!App::runningUnitTests()) {
            $estimator->setLogger(new Screen('train-model'));
        }

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