<?php

namespace App\Enums;

enum ReservaSituacao: int
{
    case Aguardando = 1;
    case Disponivel = 2;
    case Atendida = 3;
    case Expirada = 4;
    case Cancelada = 5;
}
