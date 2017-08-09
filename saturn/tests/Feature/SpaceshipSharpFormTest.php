<?php

namespace Tests\Feature;

use App\Spaceship;
use App\SpaceshipType;
use App\User;
use Code16\Sharp\Form\Fields\SharpFormTextField;
use Code16\Sharp\Utils\Testing\SharpAssertions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SpaceshipSharpFormTest extends TestCase
{
    use DatabaseMigrations, SharpAssertions;

    protected function setUp()
    {
        parent::setUp();

        $this->initSharpAssertions();
    }

    /** @test */
    public function we_can_get_a_valid_spaceship_update_form()
    {
        $this->loginAsSharpUser(factory(User::class)->create(["group" => "sharp"]));

        $spaceship = factory(Spaceship::class)->create();

        $this->getSharpForm("spaceship", $spaceship->id)
            ->assertSharpFormHasFieldOfType("name", SharpFormTextField::class)
            ->assertSharpFormHasFields([
                "name", "picture:file", "picture:legend", "capacity", "type_id", "construction_date", "pilots", "reviews"
            ])
            ->assertSharpFormDataEquals("name", $spaceship->name);
    }

    /** @test */
    public function we_can_get_a_valid_spaceship_create_form()
    {
        $this->loginAsSharpUser(factory(User::class)->create(["group" => "sharp"]));

        factory(SpaceshipType::class)->create();

        $this->getSharpForm("spaceship")
            ->assertSharpFormHasFields([
                "name", "picture:file", "picture:legend", "capacity", "type_id", "construction_date", "pilots", "reviews"
            ]);
    }

    /** @test */
    public function we_are_authorized_to_update_a_spaceship_with_an_even_id()
    {
        $this->loginAsSharpUser(factory(User::class)->create(["group" => "sharp"]));

        $spaceship = factory(Spaceship::class)->create(["id"=>2]);

        $this->getSharpForm("spaceship", $spaceship->id)
            ->assertSharpHasAuthorization("update");
    }

    /** @test */
    public function we_are_not_authorize_to_update_a_spaceship_with_an_odd_id()
    {
        $this->loginAsSharpUser(factory(User::class)->create(["group" => "sharp"]));

        $spaceship = factory(Spaceship::class)->create(["id"=>1]);

        $this->getSharpForm("spaceship", $spaceship->id)
            ->assertSharpHasNotAuthorization("update");
    }

    /** @test */
    public function we_can_update_a_spaceship()
    {
        $this->loginAsSharpUser(factory(User::class)->create(["group" => "sharp"]));

        $spaceship = factory(Spaceship::class)->create(["id" => 2, "name" => "old"]);

        $this->updateSharpForm("spaceship", $spaceship->id, array_merge($spaceship->toArray(), [
            "name" => "test",
            "capacity" => 10
        ]))->assertStatus(200);

        $this->assertDatabaseHas("spaceships", [
            "id" => 2,
            "name" => "test",
            "capacity" => 10000
        ]);
    }

    /** @test */
    public function we_can_create_a_spaceship()
    {
        $this->loginAsSharpUser(factory(User::class)->create(["group" => "sharp"]));

        $this->storeSharpForm("spaceship", array_merge(factory(Spaceship::class)->make()->toArray(), [
            "name" => "test_create",
            "capacity" => 10
        ]))->assertStatus(200);

        $this->assertDatabaseHas("spaceships", [
            "name" => "test_create",
            "capacity" => 10000
        ]);
    }

}