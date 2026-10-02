<?php

test('a raiz redireciona para o catalogo', function () {
    $this->get('/')->assertRedirect('/emprestimos/catalogo');
});
