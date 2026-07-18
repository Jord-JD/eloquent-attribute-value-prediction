<?php

namespace JordJD\EloquentAttributeValuePrediction\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use JordJD\EloquentAttributeValuePrediction\Helpers\DatasetHelper;
use PHPUnit\Framework\TestCase;

final class RelationshipSampleTest extends TestCase
{
    public function testRelationshipAttributesCanBeUsedAsPredictors()
    {
        $article = new SampleModel();
        $author = new RelatedModel(['id' => 7, 'name' => 'Ada']);
        $article->setRelation('author', $author);

        $sample = DatasetHelper::buildSample($article, ['author.name', 'author']);

        $this->assertSame(['Ada', RelatedModel::class.':7'], $sample);
    }

    public function testToManyRelationshipValuesAreDeterministic()
    {
        $article = new SampleModel();
        $article->setRelation('tags', new Collection([
            new RelatedModel(['id' => 2, 'name' => 'beta']),
            new RelatedModel(['id' => 1, 'name' => 'alpha']),
        ]));

        $sample = DatasetHelper::buildSample($article, ['tags.*.name']);

        $this->assertSame(['["alpha","beta"]'], $sample);
    }

    public function testBooleanFeaturesAreNormalisedCategorically()
    {
        $model = new SampleModel(['published' => false]);

        $this->assertSame(['false'], DatasetHelper::buildSample($model, ['published']));
    }
}

class SampleModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
    ];
}

class RelatedModel extends Model
{
    protected $guarded = [];
}
