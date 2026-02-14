<?php

namespace JordJD\EloquentAttributeValuePrediction\Tests\Unit;

use JordJD\EloquentAttributeValuePrediction\Helpers\PathHelper;
use JordJD\EloquentAttributeValuePrediction\ServiceProvider;
use JordJD\EloquentAttributeValuePrediction\Tests\Unit\TestClasses\Eloquent\IrisFlower;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

final class TrainingTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    public function testTraining()
    {
        $speciesModelPath = PathHelper::getModelPath(IrisFlower::class, 'species');
        $petalWidthModelPath = PathHelper::getModelPath(IrisFlower::class, 'petal_width');

        if (file_exists($speciesModelPath)) {
            unlink($speciesModelPath);
        }

        if (file_exists($petalWidthModelPath)) {
            unlink($petalWidthModelPath);
        }

        $this->artisan('eavp:train', ['model' => Irisflower::class]);

        $this->assertFileExists($speciesModelPath);
        $this->assertFileExists($petalWidthModelPath);
    }

}
