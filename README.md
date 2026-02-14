<hr/>

<h1 align="center">🖥️🧠💪 Eloquent Attribute Value Prediction</h1>
<p align="center">
    ❤️ <b>Machine Learning For Laravel Developers!</b> ❤️ 
</p>

<p align="center">
    <a href="https://github.com/Jord-JD/eloquent-attribute-value-prediction/actions">
        <img src="https://github.com/Jord-JD/eloquent-attribute-value-prediction/workflows/Tests/badge.svg" />
    </a>
    <a href="https://packagist.org/packages/jord-jd/eloquent-attribute-value-prediction/stats">
        <img src="https://img.shields.io/packagist/dt/Jord-JD/eloquent-attribute-value-prediction.svg" />
    </a>
</p>

<hr/>

With this package you'll be able to predict attribute values for your Laravel Eloquent models using the power of machine learning! 🌟

With an intuitive syntax you can predict the values of both categorical (string) and continuous (numeric) attributes. Take a look at the examples below.

```php
$animal = new \App\Models\Animal();
$animal->size = 'small';
$animal->has_wings = false;
$animal->domesticated = true;

$animal->name = $animal->predict('name');

// 'cat'

$predictions = $animal->getPredictions('name');

// [
//   'cat' => 0.43,
//   'dog' => 0.40,
//   'bird' => 0.10,
//   'elephant => 0.9,
// ]

$house = new \App\Models\House();
$house->num_bedrooms = 3;
$house->num_bathrooms = 1;

$house->value = $house->predict('value');

// 180000
```

## Installation

To install just run the following Composer command.

```bash
composer require jord-jd/eloquent-attribute-value-prediction
```

After installation, you need to set up your model for attribute prediction.

## Setup

Let's say you have an `IrisFlowers` table that contains data about each of 
three species of the Iris flower. In this example, we want to be able to 
predict the flower's species given the sepal length and width, and 
petal length and width.

First, we need to set up out `IrisFlower` model for attribut prediction.

This is done by adding the `HasPredictableAttributes` interface and 
`PredictsAttributes` trait to our model as shown below.

```php
<?php

namespace App\Models;

use JordJD\EloquentAttributeValuePrediction\Interfaces\HasPredictableAttributes;
use JordJD\EloquentAttributeValuePrediction\Traits\PredictsAttributes;
use Illuminate\Database\Eloquent\Model;

class IrisFlower extends Model implements HasPredictableAttributes
{
    use PredictsAttributes;

}
``` 

We then need to tell our model which attributes we wish to predict. We do this
by adding the `registerPredictableAttributes()` function. 

In this example, we want to use predict the `species` attribute based on the 
`sepal_length`, `sepal_width`, `petal_length`, and `petal_width` attributes.
This can be done by returning an array in the following format.

```php
public function registerPredictableAttributes(): array
    {
        return [
            'species' => [
                'sepal_length',
                'sepal_width',
                'petal_length',
                'petal_width',
            ],
        ];
    }
```

You also need to add the attributes you are using to the `$casts` array. 
It is important that the machine learning algorithm knows the type of data
stored in each attribute, and that it is consistent.

For our `IrisFlower` example, the following format is appropriate.

```php
protected $casts = [
        'sepal_length' => 'float',
        'sepal_width' => 'float',
        'petal_length' => 'float',
        'petal_width' => 'float',
        'species' => 'string',
    ];
``` 

## Training

Before you can make attribute value predictions, you must train a machine 
learning model on your data. As a general rule, the more data you provide
your model, the better it will perform, and the more accurate it will be.

You can train your model(s) using the `eavp:train` Artisan command, as shown
in the example below.

```bash
php artisan eavp:train \\App\\Models\\IrisFlower
```

One model will be trained for each of the attributes you wish to predict. When
they are trained, they will be saved into the `storage/eavp/model/` directory
for future use.

Be aware that the training process can take some time to complete depending 
on the amount of data you are using, and the complexity of your machine 
learning model. Training progress will be output to the console where possible.

You can re-run this command (manually, or on a schedule) to re-train your 
machine learning model(s). Previously trained models will be replaced 
automatically. 

## Prediction

Once you have set up your Eloquent model, and trained your machine learning 
model(s), you can begin predicting attributes.

For example, to predict the species of an Iris flower, you can create a new
`IrisFlower` object and populate a few of its known attributes, then call the
`predict` method.

```php
$flower = new \App\Models\IrisFlower();
$flower->sepal_length = 5.1;
$flower->sepal_width = 3.5;
$flower->petal_length = 1.4;
$flower->petal_width = 0.2;

$species = $flower->predict('species');  
```

The `predict` method should be passed the attribute name you wish to predict.
It will then returns the prediction as a string or numeric type. 

In our example, this should be the 'setosa'
species, based on [Iris flower data set](https://en.wikipedia.org/wiki/Iris_flower_data_set).

## Advanced

### Prediction confidence

If you wish, you can also retrieve the machine learning model's
confidence that a prediction is correct. This is done with the `getPredictions`
method.

```php
$flower = new \App\Models\IrisFlower();
$flower->sepal_length = 4.5;
$flower->sepal_width = 2.3;
$flower->petal_length = 1.3;
$flower->petal_width = 0.3;

$predictions = $flower->getPredictions('species');

/*
array:3 [
  "setosa" => 0.69785665879791
  "versicolor" => 0.30214334120209
  "virginica" => 0.0
]
*/
```

In this example, you can see that the machine learning model is ~70% confident
the flower is a Setosa, ~30 confident the flower is a Versicolor, and 0% 
confident the flower is a Virginica.

Note that you can only use the `getPredictions` method if the attribute you are
attempting to predict the value of is non-numeric. 

### Changing attribute estimators

By default, attribute values are predicted using K-d Neighbors. 
This is a more efficient form of a standard K Nearest Neighbors algorithm.

The machine learning algorithm that is used to predict your attribute values
is known as an 'estimator'. If you wish, you can modify the estimator which
is used for each attribute.

To do this, you need to add a `registerEstimators` method to your model.

```php
public function registerEstimators(): array
{
    return [
        'species' => new MultilayerPerceptron([
            new Dense(50),
            new Dense(50),
        ]),
    ];
}
```

In the example above, we are changing the estimator for the `species` attribute
to a multilayer perceptron classifier (neural network) with two densely connected 
hidden layers.

Under the hood, this package uses the [Rubix ML](https://rubixml.com/) library.
This means you can use any estimator is supports.

See the [Choosing an Estimator](https://docs.rubixml.com/latest/choosing-an-estimator.html)
page for a list of all available estimators you can use for attribute prediction.
