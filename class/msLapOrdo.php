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
 *
 * LAP : gestion ordonnances, liste de traitements, historiques ...
 *
 * @author Bertrand Boutillier <b.boutillier@gmail.com>
 */

class msLapOrdo extends msLap
{
    private $_ordonnanceID;


/**
 * Définir l'ordonnance concernée
 * @param int $v ID de l'ordonnance concernée
 * @return int toID
 */
    public function setOrdonnanceID($id)
    {
        if (is_numeric($id)) {
            return $this->_ordonnanceID = $id;
        } else {
            throw new Exception('OrdonnanceID is not numeric');
        }
    }

/**
 * Obtenir les datas de l'ordonnance
 * @return array data ordonnance
 */
    public function getOrdonnance() {
      if(!isset($this->_ordonnanceID)) throw new Exception('OrdonnanceID is not defined');

      $data = new msData();
      $name2typeID=$data->getTypeIDsFromName(['lapLignePrescription','lapLigneMedicament','lastname','firstname','birthname']);

      $tab['ordoData']=msSQL::sqlUnique("select o.*, CASE WHEN n.value != '' THEN n.value ELSE bn.value END as nom, p.value as prenom
      from objets_data as o
      left join objets_data as n on n.toID=o.fromID and n.typeID='".$name2typeID['lastname']."' and n.outdated='' and n.deleted=''
      left join objets_data as p on p.toID=o.fromID and p.typeID='".$name2typeID['firstname']."' and p.outdated='' and p.deleted=''
      left join objets_data as bn on bn.toID=o.fromID and bn.typeID='".$name2typeID['birthname']."' and bn.outdated='' and bn.deleted=''
      where o.id='".$this->_ordonnanceID."'
      group by  o.id, n.id, p.id, bn.id order by o.id desc");

      //extraction des lignes de prescrition
      if($lignes = msSQL::sql2tabKey("select * from objets_data where instance='".$this->_ordonnanceID."' and typeID='".$name2typeID['lapLignePrescription']."' order by id", 'id')) {
        foreach($lignes as $k=>$l) {
          $lignes[$k]['ligneData']=json_decode($l['value'], true);
        }
        //extraction des medicaments
        if($medicaments = msSQL::sql2tab("select * from objets_data where instance in (".implode(',', array_column($lignes,'id')).") and typeID='".$name2typeID['lapLigneMedicament']."' order by id")) {
          foreach($medicaments as $k=>$m) {
            $lignes[$m['instance']]['medics'][]=json_decode($m['value'],true);
          }
        }

        //print_r($lignes);

        //préparation tableau final
        foreach($lignes as $ligne) {
          if($ligne['ligneData']['isALD']=='true') {
            $zone='ordoMedicsALD';
          } else {
            $zone='ordoMedicsG';
          }
          $tab[$zone][]=array(
            'ligneData'=>$ligne['ligneData'],
            'medics'=>$ligne['medics']
          );
        }

        return $tab;

      }
    }

/**
 * Sauver une ligne de prescription
 * @param  array $ligne data de la ligne de prescription
 * @return [type]        [description]
 */
    public function saveLignePrescription($ligne)
    {
        global $p;
        $lap = new msObjet();
        $lap->setFromID($p['user']['id']);
        $lap->setToID($this->_toID);

        if(is_numeric($this->_ordonnanceID)) {
          $ligneID=$lap->createNewObjetByTypeName('lapLignePrescription', json_encode($ligne['ligneData']),$this->_ordonnanceID);
        } else {
          $ligneID=$lap->createNewObjetByTypeName('lapLignePrescription', json_encode($ligne['ligneData']));
        }

        if (is_numeric($ligneID)) {
            // infos sur la ligne
            $lap->createNewObjetByTypeName('lapLignePrescriptionDatePriseDebut', $ligne['ligneData']['dateDebutPrise'], $ligneID);
            $lap->createNewObjetByTypeName('lapLignePrescriptionDatePriseFin', $ligne['ligneData']['dateFinPrise'], $ligneID);
            $lap->createNewObjetByTypeName('lapLignePrescriptionDureeJours', $ligne['ligneData']['dureeTotaleMachineJours'], $ligneID);
            $lap->createNewObjetByTypeName('lapLignePrescriptionIsALD', $ligne['ligneData']['isALD'], $ligneID);
            $lap->createNewObjetByTypeName('lapLignePrescriptionIsChronique', $ligne['ligneData']['isChronique'], $ligneID);


      // Médicaments
      foreach ($ligne['medics'] as $k=>$m) {
          $medicamentID=$lap->createNewObjetByTypeName('lapLigneMedicament', json_encode($ligne['medics'][$k]), $ligneID);
          if (is_numeric($medicamentID)) {
              $lap->createNewObjetByTypeName('lapMedicamentSpecialiteCodeTheriaque', $m['speThe'], $medicamentID);
              $lap->createNewObjetByTypeName('lapMedicamentPresentationCodeTheriaque', $m['presThe'], $medicamentID);
              $lap->createNewObjetByTypeName('lapMedicamentSpecialiteNom', $m['nomSpe'], $medicamentID);
              $lap->createNewObjetByTypeName('lapMedicamentDC', $m['nomDC'], $medicamentID);
              $lap->createNewObjetByTypeName('lapMedicamentCodeATC', $m['codeATC'], $medicamentID);
              $lap->createNewObjetByTypeName('lapMedicamentEstPrescriptibleEnDC', $m['prescriptibleEnDC'], $medicamentID);
          }
      }
        }
    }

/**
 * Obtenir l'historique des ordonnances
 * @return array tableau de l'historique
 */
    public function getHistoriqueOrdos() {
      $data = new msData();
      $name2typeID=$data->getTypeIDsFromName(['lapOrdonnance','firstname','lastname','birthname']);

      return msSQL::sql2tabKey("select o.*, CASE WHEN n.value != '' THEN n.value ELSE bn.value END as nom, p.value as prenom, year(o.registerDate) as annee
      from objets_data as o
      left join objets_data as n on n.toID=o.fromID and n.typeID='".$name2typeID['lastname']."' and n.outdated='' and n.deleted=''
      left join objets_data as p on p.toID=o.fromID and p.typeID='".$name2typeID['firstname']."' and p.outdated='' and p.deleted=''
      left join objets_data as bn on bn.toID=o.fromID and bn.typeID='".$name2typeID['birthname']."' and bn.outdated='' and bn.deleted=''
      where o.typeID='".$name2typeID['lapOrdonnance']."' and o.toID='".$this->_toID."' and o.deleted='' and o.outdated=''
      group by  o.id, n.id, p.id, bn.id order by o.id desc", 'id');
    }

/**
 * Obtenir le traitement en cours
 * @return array tableau 2 entrées : TTChroniques, TTPonctuels
 */
    public function getTTenCours()
    {
        if (!isset($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        $data = new msData();
        $name2typeID=$data->getTypeIDsFromName(['lapLignePrescription','lapLigneMedicament','lapLignePrescriptionIsChronique','lapLignePrescriptionDatePriseDebut', 'lapLignePrescriptionDatePriseFin', 'lapLignePrescriptionDatePriseFinEffective']);

        if ($lignesPresTTchro=msSQL::sql2tab("select lp.id, lp.value
          from objets_data as lp
          left join objets_data as chro on chro.instance=lp.id and chro.typeID='".$name2typeID['lapLignePrescriptionIsChronique']."'
          left join objets_data as dfe on dfe.instance=lp.id and dfe.typeID='".$name2typeID['lapLignePrescriptionDatePriseFinEffective']."'
          where lp.typeID='".$name2typeID['lapLignePrescription']."' and lp.toID='".$this->_toID."' and lp.outdated='' and lp.deleted='' and chro.value='true'
          and (STR_TO_DATE(dfe.value, '%d/%m/%Y') > CURDATE() or dfe.value is null)
          ")) {
            foreach ($lignesPresTTchro as $l) {
                $ligne['TTChroniques'][$l['id']]['ligneData']=json_decode($l['value'], true);
                $ligne['TTChroniques'][$l['id']]['ligneData']['id']=$l['id'];
            }

            if ($lignesMedicsTTchro=msSQL::sql2tab("select id, value, instance from objets_data where typeID='".$name2typeID['lapLigneMedicament']."' and instance in (".implode(',', array_column($lignesPresTTchro, 'id')).") and outdated='' and deleted='' ")) {
                foreach ($lignesMedicsTTchro as $m) {
                    $ligne['TTChroniques'][$m['instance']]['medics'][]=json_decode($m['value'], true);
                }
            }
            $whereExclu="and lp.id not in (".implode(',', array_column($lignesPresTTchro, 'id')).")";
        } else {
          $whereExclu="";
        }

        if ($lignesPresTTponct=msSQL::sql2tab("select lp.id, lp.value
          from objets_data as lp
          left join objets_data as dd on dd.instance=lp.id and dd.typeID='".$name2typeID['lapLignePrescriptionDatePriseDebut']."'
          left join objets_data as df on df.instance=lp.id and df.typeID='".$name2typeID['lapLignePrescriptionDatePriseFin']."'
          left join objets_data as dfe on dfe.instance=lp.id and dfe.typeID='".$name2typeID['lapLignePrescriptionDatePriseFinEffective']."'
          where lp.typeID='".$name2typeID['lapLignePrescription']."' and lp.toID='".$this->_toID."' and lp.outdated='' and lp.deleted='' ".$whereExclu."
          and STR_TO_DATE(dd.value, '%d/%m/%Y') <= CURDATE()
          and STR_TO_DATE(df.value, '%d/%m/%Y') >= CURDATE()
          and (STR_TO_DATE(dfe.value, '%d/%m/%Y') > CURDATE() or dfe.value is null)
          ")) {
            foreach ($lignesPresTTponct as $l) {
                $ligne['TTPonctuels'][$l['id']]['ligneData']=json_decode($l['value'], true);
                $ligne['TTPonctuels'][$l['id']]['ligneData']['id']=$l['id'];
            }

            if ($lignesMedicsTTponct=msSQL::sql2tab("select id, value, instance from objets_data where typeID='".$name2typeID['lapLigneMedicament']."' and instance in (".implode(',', array_column($lignesPresTTponct, 'id')).") and outdated='' and deleted='' ")) {
                foreach ($lignesMedicsTTponct as $m) {
                    $ligne['TTPonctuels'][$m['instance']]['medics'][]=json_decode($m['value'], true);
                }
            }
        }

        if(isset($ligne['TTPonctuels'])) $ligne['TTPonctuels']=array_values($ligne['TTPonctuels']);
        if(isset($ligne['TTChroniques'])) $ligne['TTChroniques']=array_values($ligne['TTChroniques']);

        return $ligne;
    }

    public function getHistoriqueAnneesDistinctes() {
      $data = new msData();
      $name2typeID=$data->getTypeIDsFromName(['lapLignePrescription','lapLigneMedicament','lapLignePrescriptionIsChronique','lapLignePrescriptionDatePriseDebut', 'lapLignePrescriptionDatePriseFin', 'lapLignePrescriptionDatePriseFinEffective']);

      $tabretour=[date('Y')];
      if ($lignesPres=msSQL::sql2tab("select YEAR(STR_TO_DATE(dd.value, '%d/%m/%Y')) as dd, YEAR(STR_TO_DATE(df.value, '%d/%m/%Y')) as df, YEAR(STR_TO_DATE(dfe.value, '%d/%m/%Y')) as dfe
        from objets_data as lp
        left join objets_data as dd on dd.instance=lp.id and dd.typeID='".$name2typeID['lapLignePrescriptionDatePriseDebut']."'
        left join objets_data as df on df.instance=lp.id and df.typeID='".$name2typeID['lapLignePrescriptionDatePriseFin']."'
        left join objets_data as dfe on dfe.instance=lp.id and dfe.typeID='".$name2typeID['lapLignePrescriptionDatePriseFinEffective']."'
        where lp.typeID='".$name2typeID['lapLignePrescription']."' and lp.toID='".$this->_toID."' and lp.outdated='' and lp.deleted=''
        ")) {

          foreach($lignesPres as $v) {
            if(!in_array($v['dd'],$tabretour) and !empty($v['dd'])) $tabretour[]=$v['dd'];
            if(!in_array($v['df'],$tabretour) and !empty($v['df'])) $tabretour[]=$v['df'];
            if(!in_array($v['dfe'],$tabretour) and !empty($v['dfe'])) $tabretour[]=$v['dfe'];
          }

        }
        rsort($tabretour);
        return $tabretour;
    }

/**
 * Obtenir l'historique des traitement pour une année donnée
 * @param  int $year année
 * @return array       tableau d'historique
 */
    public function getHistoriqueTT($year) {
      $data = new msData();
      $name2typeID=$data->getTypeIDsFromName(['lapLignePrescription','lapLigneMedicament','lapLignePrescriptionIsChronique','lapLignePrescriptionDatePriseDebut', 'lapLignePrescriptionDatePriseFin', 'lapLignePrescriptionDatePriseFinEffective']);

      $final=[];
      if ($lignesPres=msSQL::sql2tabKey("select lp.id, lp.value, dfe.value as dfe, lp.instance as ordonnanceID
        from objets_data as lp
        left join objets_data as dd on dd.instance=lp.id and dd.typeID='".$name2typeID['lapLignePrescriptionDatePriseDebut']."'
        left join objets_data as df on df.instance=lp.id and df.typeID='".$name2typeID['lapLignePrescriptionDatePriseFin']."'
        left join objets_data as dfe on dfe.instance=lp.id and dfe.typeID='".$name2typeID['lapLignePrescriptionDatePriseFinEffective']."'
        where lp.typeID='".$name2typeID['lapLignePrescription']."' and lp.toID='".$this->_toID."' and lp.outdated='' and lp.deleted=''
        and (YEAR(STR_TO_DATE(dd.value, '%d/%m/%Y')) = '".$year."'
        or YEAR(STR_TO_DATE(df.value, '%d/%m/%Y')) = '".$year."'
        or YEAR(STR_TO_DATE(dfe.value, '%d/%m/%Y')) = '".$year."')
        ", 'id')) {

          if ($lignesMedics=msSQL::sql2tab("select id, value, instance from objets_data where typeID='".$name2typeID['lapLigneMedicament']."' and instance in (".implode(',', array_column($lignesPres, 'id')).") and outdated='' and deleted='' ")) {
            foreach($lignesMedics as $medic) {
              $medics[$medic['instance']][$medic['id']]=$medic;
              $medics[$medic['instance']][$medic['id']]['value']=json_decode($medic['value'],true);
              $medics[$medic['instance']][$medic['id']]['ligneData']=json_decode($lignesPres[$medic['instance']]['value'],true);
              $medics[$medic['instance']][$medic['id']]['ordonnanceID']=$lignesPres[$medic['instance']]['ordonnanceID'];
            }
          }
          foreach($lignesPres as $lp) {
            $lp['value']=json_decode($lp['value'],true);

            //start
            $dd=explode('/',$lp['value']['dateDebutPrise']);
            if(isset($final[$dd[1]][$dd[0]]['start'])) {
              $final[$dd[1]][$dd[0]]['start']=$final[$dd[1]][$dd[0]]['start']+$medics[$lp['id']];
            } else {
              $final[$dd[1]][$dd[0]]['start']=$medics[$lp['id']];
            }

            //final
            if($lp['dfe'] != '' ) $df=explode('/',$lp['dfe']); else $df=explode('/',$lp['value']['dateFinPrise']);
            if(isset($final[$df[1]][$df[0]]['stop'])) {
              $final[$df[1]][$df[0]]['stop']=$final[$df[1]][$df[0]]['stop']+$medics[$lp['id']];
            } else {
              $final[$df[1]][$df[0]]['stop']=$medics[$lp['id']];
            }
          }
          krsort($final);
          foreach($final as $mois=>$data) {
            krsort($final[$mois]);
          }
          foreach($final as $mois=>$data) {
            $final[strftime('%B', mktime(0, 0, 0, $mois, 1, 2018))]=$data;
            unset($final[$mois]);
          }
        }
        return $final;
    }


}