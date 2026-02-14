<?php

namespace JordJD\EloquentAttributeValuePrediction\Tests\Unit;

use JordJD\EloquentAttributeValuePrediction\Helpers\PathHelper;
use JordJD\EloquentAttributeValuePrediction\ServiceProvider;
use JordJD\EloquentAttributeValuePrediction\Tests\Unit\TestClasses\Eloquent\IrisFlower;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

final class PredictionTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('eavp:train', ['model' => IrisFlower::class]);
    }

    public function testSpeciesPrediction()
    {
        $flowers = IrisFlower::all();

        $correct = 0;

        foreach ($flowers as $flower) {
            if ($flower->species === $flower->predict('species')) {
                $correct++;
            }
        }

        $percentageCorrect = ($correct / $flowers->count()) * 100;

        $this->assertGreaterThanOrEqual(95, $percentageCorrect);
    }

    public function testPetalWidthPrediction()
    {
        $flowers = IrisFlower::all();

        $differences = [];

        foreach ($flowers as $flower) {
            $differences[] = $flower->petal_width - $flower->predict('petal_width');
        }

        $averageDiff = abs(array_sum($differences) / count($differences));

        $this->assertLessThanOrEqual(0.002, $averageDiff);
    }

    public function testSpeciesGetPredictions()
    {
        $flowers = IrisFlower::all();

        $correct = 0;

        foreach ($flowers as $flower) {
            $predictions = $flower->getPredictions('species');

            if ($flower->species === array_keys($predictions)[0]) {
                $correct++;
            }
        }

        $percentageCorrect = ($correct / $flowers->count()) * 100;

        $this->assertGreaterThanOrEqual(95, $percentageCorrect);
    }

    public function testPetalWidthGetPredictionsFails()
    {
        $this->expectException('InvalidArgumentException');

        IrisFlower::first()->getPredictions('petal_width');
    }

}
