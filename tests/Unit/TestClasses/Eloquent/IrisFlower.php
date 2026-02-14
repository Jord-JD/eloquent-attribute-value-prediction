<?php

namespace JordJD\EloquentAttributeValuePrediction\Tests\Unit\TestClasses\Eloquent;

use JordJD\EloquentAttributeValuePrediction\Interfaces\HasPredictableAttributes;
use JordJD\EloquentAttributeValuePrediction\Traits\PredictsAttributes;
use JordJD\uxdm\Objects\Destinations\AssociativeArrayDestination;
use JordJD\uxdm\Objects\Migrator;
use JordJD\uxdm\Objects\Sources\CSVSource;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class IrisFlower extends Model implements HasPredictableAttributes
{
    use PredictsAttributes;
    use Sushi;

    protected $casts = [
        'sepal_length' => 'float',
        'sepal_width' => 'float',
        'petal_length' => 'float',
        'petal_width' => 'float',
        'species' => 'string',
    ];

    public function registerPredictableAttributes(): array
    {
        return [
            'species' => [
                'sepal_length',
                'sepal_width',
                'petal_length',
                'petal_width',
            ],
            'petal_width' => [
                'sepal_length',
                'sepal_width',
                'petal_length',
                'species',
            ]
        ];
    }

    public function getRows()
    {
        $rows = [];

        (new Migrator())
            ->setSource(new CsvSource(__DIR__ . '/../../data/iris.csv'))
            ->setDestination(new AssociativeArrayDestination($rows))
            ->migrate();

        return $rows;
    }
}
