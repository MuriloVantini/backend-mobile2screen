<?php

use App\Models\Alert;
use App\Models\ApiKey;
use App\Models\Tag;

test('recursos de outro proprietario sao proibidos para tags alertas e api keys', function () {
    $owner = createUser();
    actingAsUser();

    $tag = Tag::factory()->for($owner)->create();
    $alert = Alert::factory()->for($owner)->create();
    $apiKey = ApiKey::factory()->for($owner)->create();

    $this->getJson('/api/tags/' . $tag->id)->assertForbidden();
    $this->getJson('/api/alerts/' . $alert->id)->assertForbidden();
    $this->putJson('/api/api-keys/' . $apiKey->id, ['name' => 'X'])->assertForbidden();
});
