<?php
/*
 * This file is part of MedShakeEHR.
 *
 * Copyright (c) 2017
 * Bertrand Boutillier <b.boutillier@gmail.com>
 * http://www.medshake.net
 *
 * MedShakeEHR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * MedShakeEHR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MedShakeEHR.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Patient > action : sauver une ordonnance
 *
 * @author Bertrand Boutillier <b.boutillier@gmail.com>
 * @contrib fr33z00 <https://www.github.com/fr33z00>
 */

if ($_POST['module']!='base' and !isset($delegate)) {
    return;
}
if (count($_POST)>2) {
    $patient = new msObjet();
    $patient->setFromID($p['user']['id']);
    $patient->setToID($_POST['patientID']);

    //support
    if (isset($_POST['objetID'])) {
        $supportID=$patient->createNewObjetByTypeName('ordoPorteur', '', '0', '0', $_POST['objetID']);
    } else {
        $supportID=$patient->createNewObjetByTypeName('ordoPorteur', '');
    }


    //type d'impression modeprintObjetID
    if (isset($_POST['modeprintObjetID'])) {
        $patient->createNewObjetByTypeName('ordoTypeImpression', $_POST['ordoTypeImpression'], $supportID, '0', $_POST['modeprintObjetID']);
    } else {
        $patient->createNewObjetByTypeName('ordoTypeImpression', $_POST['ordoTypeImpression'], $supportID);
    }

    foreach ($_POST as $k=>$v) {
        if (preg_match("#^([0-9]+)_[0-9]+_([0-9]+)$#", $k, $m)) {
            if ($m[2]>0) {
                $postObjetId=$m[2];
            } else {
                $postObjetId='0';
            }
            $id=$patient->createNewObjetByTypeName('ordoLigneOrdo', $v, $supportID, $m[1], $postObjetId);

            if ($postObjetId>0) {
                msSQL::sqlQuery("delete from objets_data where instance='".$postObjetId."' and typeID='".msData::getTypeIDFromName('ordoLigneOrdoALDouPas')."' ");
            }
            if (isset($_POST[$k.'CB'])) {
                $patient->createNewObjetByTypeName('ordoLigneOrdoALDouPas', $_POST[$k.'CB'], $id);
            }
        }
    }

    $pdf= new msPDF();

    $pdf->setFromID($p['user']['id']);
    $pdf->setToID($_POST['patientID']);
    $pdf->setType('ordo');
    $pdf->setObjetID($supportID);

    $pdf->makePDF();
    $pdf->savePDF();
    $pdf->showPDF();
} else {
    echo 'Ordonnance vide !';
}
