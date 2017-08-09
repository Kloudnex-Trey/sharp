<?php

namespace Code16\Sharp\Tests\Feature\Api;

use Code16\Sharp\EntityList\Commands\EntityCommand;
use Code16\Sharp\EntityList\Commands\InstanceCommand;
use Code16\Sharp\Exceptions\Form\SharpApplicativeException;
use Code16\Sharp\Tests\Fixtures\PersonSharpEntityList;

class CommandControllerTest extends BaseApiTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->login();
    }

    /** @test */
    public function we_can_call_an_info_entity_command()
    {
        $this->buildTheWorld();
        $this->disableExceptionHandling();

        $this->json('post', '/sharp/api/list/person/command/entity_info')
            ->assertStatus(200)
            ->assertJson([
                "action" => "info",
                "message" => "ok",
            ]);
    }

    /** @test */
    public function we_can_call_an_info_instance_command()
    {
        $this->buildTheWorld();
        $this->disableExceptionHandling();

        $this->json('post', '/sharp/api/list/person/command/instance_info/1')
            ->assertStatus(200)
            ->assertJson([
                "action" => "info",
                "message" => "ok",
            ]);
    }

    /** @test */
    public function we_can_call_a_reload_entity_command()
    {
        $this->buildTheWorld();
        $this->disableExceptionHandling();

        $this->json('post', '/sharp/api/list/person/command/entity_reload')
            ->assertStatus(200)
            ->assertJson([
                "action" => "reload"
            ]);
    }

    /** @test */
    public function we_can_call_a_view_entity_command()
    {
        $this->buildTheWorld();
        $this->disableExceptionHandling();

        $this->json('post', '/sharp/api/list/person/command/entity_view')
            ->assertStatus(200)
            ->assertJson([
                "action" => "view",
            ]);
    }

    /** @test */
    public function we_can_call_a_refresh_entity_command()
    {
        $this->buildTheWorld();
        $this->disableExceptionHandling();

        $this->json('post', '/sharp/api/list/person/command/entity_refresh')
            ->assertStatus(200)
            ->assertJson([
                "action" => "refresh",
                "items" => [
                    [
                        "id" => 1,
                        "name" => "John <b>Wayne</b>",
                        "age" => 22
                    ], [
                        "id" => 2,
                        "name" => "Mary <b>Wayne</b>",
                        "age" => 26
                    ]
                ]
            ]);
    }

    /** @test */
    public function we_can_call_a_refresh_instance_command()
    {
        $this->buildTheWorld();
        $this->disableExceptionHandling();

        $json = $this->json('post', '/sharp/api/list/person/command/instance_refresh/1')
            ->assertStatus(200)
            ->assertJson([
                "action" => "refresh",
                "items" => [
                    [
                        "id" => 1,
                        "name" => "John <b>Wayne</b>",
                        "age" => 22
                    ]
                ]
            ])->decodeResponseJson();

        $this->assertCount(1, $json["items"]);
    }

    /** @test */
    public function we_can_call_a_form_entity_command()
    {
        $this->buildTheWorld();
        $this->disableExceptionHandling();

        $this->json('post', '/sharp/api/list/person/command/entity_form', [
            "data" => ["name" => "John"]
        ])->assertStatus(200);
    }

    /** @test */
    public function applicative_exception_returns_a_417_as_always()
    {
        $this->buildTheWorld();

        $this->json('post', '/sharp/api/list/person/command/entity_exception')
            ->assertStatus(417)
            ->assertJson([
                "message" => "error"
            ]);
    }

    /** @test */
    public function we_get_a_422_when_posting_invalid_data_for_a_command_with_a_form()
    {
        $this->buildTheWorld();

        $this->json('post', '/sharp/api/list/person/command/entity_form')
            ->assertStatus(422)
            ->assertJson([
                "name" => [
                    "The name field is required."
                ]
            ]);
    }

    /** @test */
    public function we_cant_call_an_unauthorized_entity_command()
    {
        $this->buildTheWorld();

        $this->json('post', '/sharp/api/list/person/command/entity_unauthorized')
            ->assertStatus(403);
    }

    /** @test */
    public function we_cant_call_an_unauthorized_instance_command()
    {
        $this->buildTheWorld();

        $this->json('post', '/sharp/api/list/person/command/instance_unauthorized_odd_id/1')
            ->assertStatus(403);

        $this->json('post', '/sharp/api/list/person/command/instance_unauthorized_odd_id/2')
            ->assertStatus(200);
    }

    protected function buildTheWorld()
    {
        parent::buildTheWorld();

        $this->app['config']->set(
            'sharp.entities.person.list',
            EntityCommandPersonSharpEntityList::class
        );
    }
}

class EntityCommandPersonSharpEntityList extends PersonSharpEntityList {

    function buildListConfig()
    {
        $this->addEntityCommand("entity_info", new class() extends EntityCommand {
            public function label(): string { return "label"; }
            public function execute(array $params = []) {
                return $this->info("ok");
            }

        })->addInstanceCommand("instance_info", new class() extends InstanceCommand {
            public function label(): string { return "label"; }
            public function execute($instanceId, array $params = []) {
                return $this->info("ok");
            }

        })->addEntityCommand("entity_reload", new class() extends EntityCommand {
            public function label(): string { return "label"; }
            public function execute(array $params = []) {
                return $this->reload();
            }

        })->addEntityCommand("entity_view", new class() extends EntityCommand {
            public function label(): string { return "label"; }
            public function execute(array $params = []) {
                return $this->view("welcome");
            }

        })->addEntityCommand("entity_refresh", new class() extends EntityCommand {
            public function label(): string { return "label"; }
            public function execute(array $params = []) {
                return $this->refresh([1, 2]);
            }

        })->addInstanceCommand("instance_refresh", new class() extends InstanceCommand {
            public function label(): string { return "label"; }
            public function execute($instanceId, array $params = []) {
                return $this->refresh(1);
            }

        })->addInstanceCommand("entity_exception", new class() extends EntityCommand {
            public function label(): string { return "label"; }
            public function execute(array $params = []) {
                throw new SharpApplicativeException("error");
            }

        })->addEntityCommand("entity_form", new class() extends EntityCommand {
            public function label(): string { return "label"; }
            public function execute(array $params = []) {
                $this->validate($params, ["name"=>"required"]);
                return $this->reload();
            }

        })->addInstanceCommand("entity_unauthorized", new class() extends EntityCommand {
            public function label(): string { return "label"; }
            public function authorize(): bool { return false; }
            public function execute(array $params = []) {
                return $this->reload();
            }

        })->addInstanceCommand("instance_unauthorized_odd_id", new class() extends InstanceCommand {
            public function label(): string { return "label"; }
            public function authorizeFor($instanceId): bool { return $instanceId%2==0; }
            public function execute($instanceId, array $params = []) {
                return $this->reload();
            }
        });

    }
}