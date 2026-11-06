<?php
/*
 *     E-cidade Software Publico para Gestao Municipal                
 *  Copyright (C) 2014  DBSeller Servicos de Informatica             
 *                            www.dbseller.com.br                     
 *                         e-cidade@dbseller.com.br                   
 *                                                                    
 *  Este programa e software livre; voce pode redistribui-lo e/ou     
 *  modifica-lo sob os termos da Licenca Publica Geral GNU, conforme  
 *  publicada pela Free Software Foundation; tanto a versao 2 da      
 *  Licenca como (a seu criterio) qualquer versao mais nova.          
 *                                                                    
 *  Este programa e distribuido na expectativa de ser util, mas SEM   
 *  QUALQUER GARANTIA; sem mesmo a garantia implicita de              
 *  COMERCIALIZACAO ou de ADEQUACAO A QUALQUER PROPOSITO EM           
 *  PARTICULAR. Consulte a Licenca Publica Geral GNU para obter mais  
 *  detalhes.                                                         
 *                                                                    
 *  Voce deve ter recebido uma copia da Licenca Publica Geral GNU     
 *  junto com este programa; se nao, escreva para a Free Software     
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA          
 *  02111-1307, USA.                                                  
 *  
 *  Copia da licenca no diretorio licenca/licenca_en.txt 
 *                                licenca/licenca_pt.txt 
 */

/**
 * Controle de materias do compras
 * @package compras
 */
class MaterialCompras
{

  /**
   * código do material;
   *
   * @var integer
   */
  protected $iMaterial;

  /**
   * Descrição do material
   *
   * @var ustring
   */
  protected $sDescricao;

  /**
   * Descrição do material
   *
   * @var ?string
   */
  protected $sComplemento;

  /**
   * Verifica se o material é um serviço 
   *
   * @var bool
   */
  protected $lServico;

  /**
   * Elementos vinculados ao material
   *
   * @var array
   */
  protected $aElementos = array();

  /**
   * Codigo anterior
   *
   * @var integer
   */
  protected $iCodanterior = array();

  /**
   * 
   */
  function __construct($iMaterial = null)
  {

    if (!empty($iMaterial)) {

      $this->iMaterial = $iMaterial;
      $oDaoPcmater     = db_utils::getDao("pcmater");
      $sSqlMaterial    = $oDaoPcmater->sql_query_file($iMaterial);
      $rsMaterial      = $oDaoPcmater->sql_record($sSqlMaterial);
      if ($oDaoPcmater->numrows > 0) {

        $oMaterial = db_utils::fieldsMemory($rsMaterial, 0, false, false, true);
        $this->sDescricao = $oMaterial->pc01_descrmater;
        $this->sComplemento = $oMaterial->pc01_complmater;
        $this->lServico   = $oMaterial->pc01_servico == 't' ? true : false;
        $this->iCodanterior = $oMaterial->pc01_codmaterant;
      } else {
        $this->iMaterial = null;
      }
    }
  }
  /**
   * @return integer
   */
  public function getMaterial()
  {

    return $this->iMaterial;
  }

  /**
   * @return integer
   */
  public function getCodanterior()
  {

    return $this->iCodanterior;
  }

  /**
   * @param ustring $sDescricao
   */
  public function setCodanterior($iCodanterior)
  {
    $this->iCodanterior = $iCodanterior;
  }

  /**
   * @return ustring
   */
  public function getDescricao()
  {

    return $this->sDescricao;
  }

  /**
   * @param ustring $sDescricao
   */
  public function setDescricao($sDescricao)
  {
    $this->sDescricao = $sDescricao;
  }

  /**
   * @return ?string
   */
  public function getComplemento()
  {
    return $this->sComplemento;
  }

  /**
   * @param ?string $complemento
   */
  public function setComplemento(?string $complemento)
  {
    $this->sComplemento = $complemento;
  }

  public function getElementos()
  {

    if (count($this->aElementos) == 0) {

      require_once("classes/db_pcmaterele_classe.php");
      $oDaoMaterialElemento  = new cl_pcmaterele;
      $sSqlElementos         = "SELECT o56_codele as codigoelemento,";
      $sSqlElementos        .= "       o56_elemento as elemento,";
      $sSqlElementos        .= "       o56_descr   as descricao";
      $sSqlElementos        .= "  from pcmaterele ";
      $sSqlElementos        .= "       inner join orcelemento on pc07_codele = o56_codele ";
      $sSqlElementos        .= "                             and o56_anousu = " . db_getsession("DB_anousu");
      $sSqlElementos        .= " where pc07_codmater = {$this->iMaterial}";
      $rsElementos           = db_query($sSqlElementos);
      $this->aElementos = db_utils::getCollectionByRecord($rsElementos, false, false, true);
    }
    return $this->aElementos;
  }

  /**
   * verifica se o material é um serviço;
   *
   * @return boolean
   */
  public function isServico()
  {

    return $this->lServico;
  }

  public function getCodigo()
  {
    return $this->iMaterial;
  }
}
