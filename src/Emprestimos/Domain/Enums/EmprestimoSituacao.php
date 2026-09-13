<?php

namespace Emprestimos\Domain\Enums;

enum EmprestimoSituacao: int
{
    case Andamento = 1;
    case Devolvido = 2;
    case Atrasado = 3;
}
