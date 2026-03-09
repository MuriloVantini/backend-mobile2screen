<?php

use App\Models\Alert;
use App\Models\ApiKey;
use App\Models\Tag;
use App\Models\Webhook;

test('cross-owner resources are forbidden for tags alerts api keys and webhooks', function () {
    $owner = createUser();
    actingAsUser();

    $tag = Tag::factory()->for($owner)->create();
    $alert = Alert::factory()->for($owner)->create();
    $apiKey = ApiKey::factory()->for($owner)->create();
    $webhook = Webhook::factory()->for($owner)->create();

    $this->getJson('/api/tags/' . $tag->id)->assertForbidden();
    $this->getJson('/api/alerts/' . $alert->id)->assertForbidden();
    $this->putJson('/api/api-keys/' . $apiKey->id, ['name' => 'X'])->assertForbidden();
    $this->getJson('/api/webhooks/' . $webhook->id)->assertForbidden();
});
