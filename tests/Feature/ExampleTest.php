<?php

test('a aplicacao retorna uma resposta com sucesso', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
