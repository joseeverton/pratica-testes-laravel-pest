<?php

it('retorna uma resposta de sucesso na rota inicial', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
