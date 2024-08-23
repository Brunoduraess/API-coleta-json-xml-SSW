<?php

function validaDiaUtil($dataColeta)
{

    $hora = date("H", strtotime($dataColeta));

    if ($hora > 11) {
        $dataColeta = date('Y-m-d', strtotime('+1 days', strtotime($dataColeta)));
    }

    $dia = date("D", strtotime($dataColeta));
    $hora = date("H", strtotime($dataColeta));
    $ano = date("Y", strtotime($dataColeta));

    $feriados = ['' . $ano . '-01-01', '' . $ano . '-04-21', '' . $ano . '-05-01', '' . $ano . '-05-30', '' . $ano . '-09-07', '' . $ano . '-10-12', '' . $ano . '-11-02', '' . $ano . '-11-15', '' . $ano . '-11-20', '' . $ano . '-12-25'];

    //Valida se a data de entrada é feriado

    while ($dia == "Sat" || $dia == "Sun" || in_array($dataColeta, $feriados)) {
        // Adiciona um dia à data de coleta
        $dataColeta = date('Y-m-d', strtotime('+1 day', strtotime($dataColeta)));

        // Atualiza o dia da semana da nova data de coleta
        $dia = date("D", strtotime($dataColeta));
    }

    $dataColeta = date("Y-m-d", strtotime($dataColeta)) . "T";

    return $dataColeta;

}


