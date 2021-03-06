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
 * Gestion des individus et de leurs datas
 *
 * @author Bertrand Boutillier <b.boutillier@gmail.com>
 * @contrib fr33z00 <https://www.github.com/fr33z00>
 */

class msPeople
{

/**
 * @var int $_toID ID de l'individus concerné
 */
    public $_toID;
/**
 * @var int $_fromID ID de l'utilisteur enregistrant la donnée
 */
    private $_fromID;
/**
 * @var int $_dataset Le jeu de data concerné
 */
    private $_dataset;
/**
 * @var int $_type Type : patient ou pro
 */
    private $_type='patient';
/**
 * @var int $_creationDate Date de création de la donnée (si besoin)
 */
    private $_creationDate;


/**
 * Définir l'individu concerné
 * @param int $v ID de l'individu concerné
 * @return int toID
 */
    public function setToID($v)
    {
        if (is_numeric($v)) {
            return $this->_toID = $v;
        } else {
            throw new Exception('ToID is not numeric');
        }
    }

/**
 * Définir l'utilisateur qui enregsitre la donnée
 * @param int $v ID de l'utilisateur
 * @return int fromID
 */
    public function setFromID($v)
    {
        if (is_numeric($v)) {
            return $this->_fromID = $v;
        } else {
            throw new Exception('FromID is not numeric');
        }
    }

/**
 * Définir la date de création de la donnée enregistrée
 * @param string $v Date au format mysql Y-m-d H:i:s
 * @return void
 */
    public function setCreationDate($v)
    {
        $this->_creationDate=$v;
    }

/**
 * Définir le type d'individu concerné : patient ou pro
 * @param string $t patient|pro
 * @return string type
 */
    public function setType($t)
    {
        if (in_array($t, array('patient', 'pro', 'externe'))) {
            return $this->_type = $t;
        } else {
            throw new Exception('Type n\'est pas d\'une valeur autorisée');
        }
    }
/**
 * Définir le jeu de données
 * @param string $v jeu de données
 * @return string Dataset
 */
    public function setDataset($v)
    {
        if (is_string($v)) {
            return $this->_dataset = $v;
        } else {
            throw new Exception('Dataset is not string');
        }
    }
/**
 * Est-ce un patient externe?
 * @return value true/false
 */
    public function isExterne() {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }
        return msSQL::sqlUniqueChamp("SELECT type='externe' FROM people WHERE id='".$this->_toID."' limit 1")==1;
    }
/**
 * Obtenir les données administratives d'un individu (version complète)
 * @return array Array avec en clef le typeID
 */
    public function getAdministrativesDatas()
    {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        if($datas=msSQL::sql2tab("select d.id, d.typeID, d.value, t.name, t.label , tt.label as parentLabel, d.parentTypeID, d.creationDate
  			from objets_data as d
  			left join data_types as t on d.typeID=t.id
  			left join data_types as tt on d.parentTypeID=tt.id
  			where d.toID='".$this->_toID."' and d.outdated='' and t.groupe='admin'
  			order by d.parentTypeID ")) {

          foreach ($datas as $v) {
              $tab[$v['typeID']]=$v;
              $tab[$v['name']]=$v;
          }
          return $tab;
        }


    }

/**
 * Obtenir les données administratives d'un individu (version simple, array 1 dimension)
 * @return array Array typeID=>value
 */
    public function getSimpleAdminDatas()
    {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        $tab=msSQL::sql2tabKey("select d.typeID, d.value
        from objets_data as d
        left join data_types as t on d.typeID=t.id
			  where d.toID='".$this->_toID."' and d.outdated=''  and t.groupe='admin'", "typeID", "value");

        return $tab;
    }

/**
 * Obtenir les données administratives d'un individu avec key name
 * @return array Array name=>value
 */
    public function getSimpleAdminDatasByName()
    {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        $tab=msSQL::sql2tabKey("select t.name, d.value
        from objets_data as d
        left join data_types as t on d.typeID=t.id
			  where d.toID='".$this->_toID."' and d.outdated=''  and t.groupe='admin'", "name", "value");

        return $tab;
    }

    /**
     * Obtenir les pros en relation avec ce patient
     * @return array array des pros en relation
     */
    public function getRelationsWithPros()
    {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        $name2typeID = new msData();
        $name2typeID = $name2typeID->getTypeIDsFromName(['relationID', 'relationPatientPraticien', 'relationPatientPatient', 'titre', 'firstname', 'lastname', 'birthname']);

        return msSQL::sql2tab("select o.value as pratID, c.value as typeRelation, p.value as prenom, t.value as titre, CASE WHEN n.value != '' THEN n.value ELSE bn.value END as nom
        from objets_data as o
        inner join objets_data as c on c.instance=o.id and c.typeID='".$name2typeID['relationPatientPraticien']."' and c.value != 'patient'
        left join objets_data as n on n.toID=o.value and n.typeID='".$name2typeID['lastname']."' and n.outdated='' and n.deleted=''
        left join objets_data as bn on bn.toID=o.value and bn.typeID='".$name2typeID['birthname']."' and bn.outdated='' and bn.deleted=''
        left join objets_data as p on p.toID=o.value and p.typeID='".$name2typeID['firstname']."' and p.outdated='' and p.deleted=''
        left join objets_data as t on t.toID=o.value and t.typeID='".$name2typeID['titre']."' and t.outdated='' and t.deleted=''
        where o.toID='".$this->_toID."' and o.typeID='".$name2typeID['relationID']."' and o.deleted='' and o.outdated=''
        group by o.value, c.id, bn.id, n.id, p.id, t.id
        order by typeRelation = 'MT' desc, nom asc");
    }

    /**
     * Obtenir les autres patients liés généalogiquement avec ce patient
     * @return array array des autres patients
     *
     */
    public function getRelationsWithOtherPatients()
    {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        $name2typeID = new msData();
        $name2typeID = $name2typeID->getTypeIDsFromName(['relationID', 'relationPatientPraticien', 'relationPatientPatient', 'titre', 'firstname', 'lastname', 'birthdate', 'birthname']);

        return msSQL::sql2tab("select o.value as patientID, c.value as typeRelation, p.value as prenom, d.value as ddn, CASE WHEN n.value != '' THEN n.value ELSE bn.value END as nom
      from objets_data as o
      inner join objets_data as c on c.instance=o.id and c.typeID='".$name2typeID['relationPatientPatient']."'
      left join objets_data as n on n.toID=o.value and n.typeID='".$name2typeID['lastname']."' and n.outdated='' and n.deleted=''
      left join objets_data as bn on bn.toID=o.value and bn.typeID='".$name2typeID['birthname']."' and bn.outdated='' and bn.deleted=''
      left join objets_data as p on p.toID=o.value and p.typeID='".$name2typeID['firstname']."' and p.outdated='' and p.deleted=''
      left join objets_data as d on d.toID=o.value and d.typeID='".$name2typeID['birthdate']."' and p.outdated='' and p.deleted=''
      where o.toID='".$this->_toID."' and o.typeID='".$name2typeID['relationID']."' and o.deleted='' and o.outdated=''
      group by o.value, c.id, bn.id, n.id, p.id, d.id
      order by nom asc");
    }

/**
 * Sortir tous les types et les valeurs liées à partir d'un groupe de cat
 * @param  string $groupe groupe de données
 * @param  array $col    colonnes sql à retourner
 * @return array         Array de résultat
 */
    public function getPeopleDataFromDataTypeGroupe($groupe, $col=['*'])
    {
        return msSQL::sql2tab("select ".implode(', ', $col)."
        from data_types as dt
        left join objets_data as od on od.typeID=dt.id and od.toID='".$this->_toID."' and od.outdated='' and od.deleted=''
        where dt.groupe='".$groupe."'
        order by dt.displayOrder, dt.label");
    }
/**
 * Obtenir la liste des utilisateurs ayant accès à un service
 * @param  string $service service spécifique
 * @return array          tableau userID=>identité
 */
  public function getUsersListForService($service)
  {
      $name2typeID = new msData();
      $name2typeID = $name2typeID->getTypeIDsFromName([$service, 'firstname', 'lastname', 'birthname']);

      return msSQL::sql2tabKey("select p.id, CASE WHEN o.value != '' THEN concat(o2.value , ' ' , o.value) ELSE concat(o2.value , ' ' , bn.value) END as identite
        from people as p
        join objets_data as dt on dt.toID=p.id and dt.typeID='".$name2typeID[$service]."' and dt.value='true'
        left join objets_data as o on o.toID=p.id and o.typeID='".$name2typeID['lastname']."' and o.outdated='' and o.deleted=''
        left join objets_data as bn on bn.toID=p.id and bn.typeID='".$name2typeID['birthname']."' and bn.outdated='' and bn.deleted=''
        left join objets_data as o2 on o2.toID=p.id and o2.typeID='".$name2typeID['firstname']."' and o2.outdated='' and o2.deleted=''
        where p.pass!='' order by identite", 'id', 'identite');
  }

  /**
   * Obtenir la liste des utilisateurs ayant une valeur spécifique pour un paramètre de configuration donné
   * @param  string $param param spécifique
   * @return array          tableau
   */
    public function getUsersWithSpecificParam($param)
    {
        $name2typeID = new msData();
        $name2typeID = $name2typeID->getTypeIDsFromName([$param, 'firstname', 'lastname', 'birthname']);
        if(!isset($name2typeID[$param])) $name2typeID[$param]='';

        if ($data=msSQL::sql2tab("select p.id, dt.value, CASE WHEN o.value != '' THEN concat(o2.value , ' ' , o.value) ELSE concat(o2.value , ' ' , bn.value) END as identite
          from people as p
          join objets_data as dt on dt.toID=p.id and dt.typeID='".$name2typeID[$param]."'
          left join objets_data as o on o.toID=p.id and o.typeID='".$name2typeID['lastname']."' and o.outdated='' and o.deleted=''
          left join objets_data as bn on bn.toID=p.id and bn.typeID='".$name2typeID['birthname']."' and bn.outdated='' and bn.deleted=''
          left join objets_data as o2 on o2.toID=p.id and o2.typeID='".$name2typeID['firstname']."' and o2.outdated='' and o2.deleted=''
          where p.pass!='' order by identite")) {
            $tab=array();
            foreach ($data as $v) {
                $tab[$v['id']]['identite']=$v['identite'];
                $tab[$v['id']]['paramValue']=$v['value'];
            }
            return $tab;
        }
    }

/**
 * Historique complet des actes pour un individu
 * @return array Array multi avec année en clef de 1er niveau
 */
    public function getHistorique()
    {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        $data = new msData();
        $porteursOrdoIds=array_column($data->getDataTypesFromCatName('porteursOrdo', ['id']), 'id');
        $porteursReglementIds=array_column($data->getDataTypesFromCatName('porteursReglement', ['id']), 'id');
        $name2typeID=$data->getTypeIDsFromName(['mailPorteur', 'docPorteur', 'docType', 'docOrigine', 'dicomStudyID', 'firstname', 'lastname', 'birthname']);

        if ($data = msSQL::sql2tab("select p.id, p.fromID, p.instance as parentID, p.important, p.titre, p.registerDate, DATE_FORMAT(p.creationDate,'%d/%m/%Y') as creationTime, p.creationDate,  DATE_FORMAT(p.creationDate,'%Y') as creationYear,  p.updateDate, t.id as typeCS, t.module as module, t.groupe, t.label, t.formValues as formName, n1.value as prenom, f.printModel, mail.instance as sendMail, doc.value as fileext, doc2.value as docOrigine, img.value as dicomStudy,
        CASE WHEN DATE_ADD(p.creationDate, INTERVAL t.durationLife second) < NOW() THEN 'copy' ELSE 'update' END as iconeType, CASE WHEN n2.value != '' THEN n2.value  ELSE bn.value END as nom
        from objets_data as p
        left join data_types as t on p.typeID=t.id
        left join objets_data as n1 on n1.toID=p.fromID and n1.typeID='".$name2typeID['firstname']."' and n1.outdated='' and n1.deleted=''
        left join objets_data as n2 on n2.toID=p.fromID and n2.typeID='".$name2typeID['lastname']."' and n2.outdated='' and n2.deleted=''
        left join objets_data as bn on bn.toID=p.fromID and bn.typeID='".$name2typeID['birthname']."' and bn.outdated='' and bn.deleted=''
        left join objets_data as mail on mail.instance=p.id and mail.typeID='".$name2typeID['mailPorteur']."'
        left join objets_data as doc on doc.instance=p.id and doc.typeID='".$name2typeID['docType']."'
        left join objets_data as doc2 on doc2.instance=p.id and doc2.typeID='".$name2typeID['docOrigine']."'
        left join objets_data as img on img.instance=p.id and img.typeID='".$name2typeID['dicomStudyID']."'
        left join forms as f on f.internalName=t.formValues
        where (t.groupe in ('typeCS', 'courrier')
        or (t.groupe = 'doc' and  t.id='".$name2typeID['docPorteur']."')
        or (t.groupe = 'ordo' and  t.id in ('".implode("','", $porteursOrdoIds)."'))
        or (t.groupe = 'reglement' and  t.id in ('".implode("','", $porteursReglementIds)."'))
        or (t.groupe='mail' and t.id='".$name2typeID['mailPorteur']."' and p.instance='0')) and p.toID='".$this->_toID."' and p.outdated='' and p.deleted=''
        group by p.id, bn.value, n1.value, n2.value, mail.instance, doc.value, doc2.value, img.value, f.id
        order by p.creationDate desc")) {
              foreach ($data as $v) {
                  $return[$v['creationYear']][]=$v;
            }

            return $return;
        }
    }

/**
 * Historique des actes du jour pour un individu
 * @return array Array multi.
 */
    public function getToday($limit='')
    {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        $data = new msData();
        $porteursOrdoIds=array_column($data->getDataTypesFromCatName('porteursOrdo', ['id']), 'id');
        $porteursReglementIds=array_column($data->getDataTypesFromCatName('porteursReglement', ['id']), 'id');
        $name2typeID=$data->getTypeIDsFromName(['mailPorteur', 'docPorteur', 'docType', 'docOrigine', 'dicomStudyID', 'firstname', 'lastname', 'birthname']);

        return msSQL::sql2tab("select p.id, p.fromID, p.instance as parentID, p.important, p.titre, p.registerDate, p.creationDate, DATE_FORMAT(p.creationDate,'%H:%i:%s') as creationTime,  p.updateDate, t.id as typeCS, t.groupe, t.label, t.module as module, t.formValues as formName, n1.value as prenom, f.printModel, mail.instance as sendMail, doc.value as fileext, doc2.value as docOrigine, img.value as dicomStudy,
        CASE WHEN DATE_ADD(p.creationDate, INTERVAL t.durationLife second) < NOW() THEN 'copy' ELSE 'update' END as iconeType, CASE WHEN n2.value != '' THEN n2.value  ELSE bn.value END as nom
        from objets_data as p
        left join data_types as t on p.typeID=t.id
        left join objets_data as n1 on n1.toID=p.fromID and n1.typeID='".$name2typeID['firstname']."' and n1.outdated='' and n1.deleted=''
        left join objets_data as n2 on n2.toID=p.fromID and n2.typeID='".$name2typeID['lastname']."' and n2.outdated='' and n2.deleted=''
        left join objets_data as bn on bn.toID=p.fromID and bn.typeID='".$name2typeID['birthname']."' and bn.outdated='' and bn.deleted=''
        left join objets_data as mail on mail.instance=p.id and mail.typeID='".$name2typeID['mailPorteur']."'
        left join objets_data as doc on doc.instance=p.id and doc.typeID='".$name2typeID['docType']."'
        left join objets_data as doc2 on doc2.instance=p.id and doc2.typeID='".$name2typeID['docOrigine']."'
        left join objets_data as img on img.instance=p.id and img.typeID='".$name2typeID['dicomStudyID']."'
        left join forms as f on f.internalName=t.formValues
        where (t.groupe in ('typeCS', 'courrier')
          or (t.groupe = 'doc' and  t.id='".$name2typeID['docPorteur']."')
          or (t.groupe = 'ordo' and  t.id in ('".implode("','", $porteursOrdoIds)."'))
          or (t.groupe = 'reglement' and  t.id in ('".implode("','", $porteursReglementIds)."'))
          or (t.groupe='mail' and t.id='".$name2typeID['mailPorteur']."' and p.instance='0'))
        and p.toID='".$this->_toID."' and p.outdated='' and p.deleted='' and DATE(p.creationDate) = CURDATE()
        group by p.id, bn.value, n1.value, n2.value, mail.instance, doc.value, doc2.value, img.value, f.id
        order by p.creationDate desc ".$limit);
    }

/**
 * Calcul l'age
 * @return int Retourne l'age
 */
    public function getAge()
    {
        if (!is_numeric($this->_toID)) {
            throw new Exception('ToID is not numeric');
        }

        $typeID=msData::getTypeIDFromName('birthdate');

        if ($birthdate=msSQL::sqlUniqueChamp("select value from objets_data where toID='".$this->_toID."' and typeID='".$typeID."' order by id desc limit 1")) {
            $annees = DateTime::createFromFormat('d/m/Y', $birthdate)->diff(new DateTime('now'))->y;
            $mois = DateTime::createFromFormat('d/m/Y', $birthdate)->diff(new DateTime('now'))->m;
            $jours = DateTime::createFromFormat('d/m/Y', $birthdate)->diff(new DateTime('now'))->d;
            if ($annees>=3) {
              return $annees.' ans';
            } elseif (($annees*12+$mois)>=3){
              return ($annees*12+$mois).' mois';
            } elseif (((30*$mois+$jours)/7)>=2){
              return round((30*$mois+$jours)/7).' semaines';
            } else {
              return $jours.' jours';
            }
        } else {
            return false;
        }
    }

/**
 * Créer un nouvel individu
 * @return int ID du nouvel individu
 */
    public function createNew()
    {
        if (!is_numeric($this->_fromID)) {
            throw new Exception('FromID is not numeric');
        } else {
            $data=array(
                'pass' => '',
                'type' => $this->_type,
                'registerDate' => date("Y/m/d H:i:s"),
                'fromID' => $this->_fromID
            );

            //pour import
            if (isset($this->_toID)) {
                $data['id']=$this->_toID;
            }
            if (isset($this->_creationDate)) {
                $data['registerDate']=$this->_creationDate;
            }


            $this->_toID=msSQL::sqlInsert('people', $data);

            return $this->_toID;
        }
    }
}
