<?php

namespace JordJD\EloquentAttributeValuePrediction\Tests\Unit\TestClasses\Eloquent;

use JordJD\EloquentAttributeValuePrediction\Interfaces\HasPredictableAttributes;
use JordJD\EloquentAttributeValuePrediction\Traits\PredictsAttributes;
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
        $handle = fopen(__DIR__.'/../../data/iris.csv', 'r');
        $headers = fgetcsv($handle);

        while (($values = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $values);

            foreach (['sepal_length', 'sepal_width', 'petal_length', 'petal_width'] as $attribute) {
                $row[$attribute] = (float) $row[$attribute];
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
