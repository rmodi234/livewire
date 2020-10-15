<?php

namespace Tests\Unit;

use Sushi\Sushi;
use Livewire\Livewire;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Livewire\Exceptions\CorruptComponentPayloadException;
use Livewire\Exceptions\CannotBindToModelDataWithoutValidationRuleException;

class ModelCollectionAttributesCanBeBoundDirectlyTest extends TestCase
{
    /** @test */
    public function can_set_a_model_attribute_inside_a_models_collection_and_save()
    {
        // Reset Sushi model.
        (new ModelForBinding)->resolveConnection()->getSchemaBuilder()->drop((new ModelForBinding)->getTable());
        (new ModelForBinding)->migrate();

        Livewire::test(ComponentWithModelsProperty::class)
            ->assertSet('models.0.title', 'foo')
            ->assertPayloadSet('models.0.title', 'foo')
            ->set('models.0.title', 'bo')
            ->assertSet('models.0.title', 'bo')
            ->call('refreshModels')
            ->assertSet('models.0.title', 'foo')
            ->set('models.0.title', 'bo')
            ->call('save')
            ->assertHasErrors('models.0.title')
            ->set('models.0.title', 'boo')
            ->call('save')
            ->call('refreshModels')
            ->assertSet('models.0.title', 'boo');
    }

    /** @test */
    public function can_set_non_persisted_models_in_model_collection()
    {
        // Reset Sushi model.
        (new ModelForBinding)->resolveConnection()->getSchemaBuilder()->drop((new ModelForBinding)->getTable());
        (new ModelForBinding)->migrate();

        Livewire::test(ComponentWithModelsProperty::class)
            ->assertSet('models.2.title', 'baz')
            ->assertSet('models.3', null)
            ->assertPayloadSet('models.3', null)
            ->call('addModel')
            ->assertNotSet('models.3', null)
            ->assertPayloadNotSet('models.3', null)
            ->set('models.3.title', 'bob')
            ->assertSet('models.3.title', 'bob')
            ->assertPayloadSet('models.3.title', 'bob')
            ->set('models.3.title', 'bo')
            ->call('refreshModels')
            ->assertSet('models.3', null)
            ->assertPayloadSet('models.3', null)
            ->call('addModel')
            ->set('models.3.title', 'bo')
            ->call('save')
            ->assertHasErrors('models.3.title')
            ->set('models.3.title', 'boo')
            ->call('save')
            ->call('refreshModels')
            ->assertSet('models.3.title', 'boo');
        ;
    }

    /** @test */
    public function cant_set_a_model_attribute_that_isnt_present_in_rules_array()
    {
        // Reset Sushi model.
        (new ModelForBinding)->resolveConnection()->getSchemaBuilder()->drop((new ModelForBinding)->getTable());
        (new ModelForBinding)->migrate();

        $this->expectException(CannotBindToModelDataWithoutValidationRuleException::class);

        Livewire::test(ComponentWithModelsProperty::class)
            ->set('models.1.restricted', 'bar')
            ->assertSet('models.1.restricted', null);
    }

    /** @test */
    public function an_eloquent_models_meta_cannot_be_hijacked_by_tampering_with_data()
    {
        // Reset Sushi model.
        (new ModelForBinding)->resolveConnection()->getSchemaBuilder()->drop((new ModelForBinding)->getTable());
        (new ModelForBinding)->migrate();

        $this->expectException(CorruptComponentPayloadException::class);

        $component = Livewire::test(ComponentWithModelsProperty::class);

        $component->payload['serverMemo']['dataMeta']['modelCollections']['models']['id'] = [1];

        $component->call('$refresh');
    }
}

class ModelForBinding extends Model
{
    use Sushi;

    protected $rows = [
        ['title' => 'foo'],
        ['title' => 'bar'],
        ['title' => 'baz'],
    ];
}

class ComponentWithModelsProperty extends Component
{
    public $models;

    protected $rules = [
        'models.*.title' => 'required|min:3',
    ];

    public function mount()
    {
        $this->models = ModelForBinding::all();
    }

    public function addModel()
    {
        $this->models->push(new ModelForBinding);
    }

    public function save()
    {
        $this->validate();

        $this->models->each->save();
    }

    public function refreshModels()
    {
        $this->models = $this->models->filter->exists->fresh();
    }

    public function render()
    {
        return view('null-view');
    }
}

class ComponentWithoutRulesArray extends Component
{
    public $models;

    public function mount()
    {
        $this->models = ModelForBinding::all();
    }

    public function render()
    {
        return view('null-view');
    }
}