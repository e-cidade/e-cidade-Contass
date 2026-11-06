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
 * Class ItemOrdemDeCompra
 */
class ItemOrdemDeCompra {


  /**
   * codigo de lancamento (sequencial)
   * @var integer
   */
  private $iCodigoLancamento;

  /**
   * codigo do item empenho
   * @var integer
   */
  private $iCodigoItemEmpenho;

  /**
   * objeto ItemEmpenho
   * @var EmpenhoFinanceiroItem
   */
  private $oItemEmpenho;

  /**
   * quantidade de itens
   * @var integer
   */
  private $iQuantidade;

  /**
   * valor total
   * @var number
   */
  private $nValor;

  /**
   * valor unitario do item
   * @var number
   */
  private $nValorUnitario;


  private $iQuantidadeAnulada;

  /**
   * valor anulado do item
   * @var number
   */
  private $nValorAnulado;

  /**
   * retorna codigo do lancamento
   * @return integer
   */
  public function getCodigoLancamento(){
    return $this->iCodigoLancamento;
  }
  /**
   * define codigo do lancamento
   * @param integer $iCodigoLancamento
   */
  public function setCodigoLancamento($iCodigoLancamento){
    $this->iCodigoLancamento = $iCodigoLancamento;
  }
  /**
   * retorna quantidade de itens
   * @return integer
   */
  public function getQuantidade(){
    return $this->iQuantidade;
  }
  /**
   * define quantidade de itens
   * @param integer $iQuantidade
   */
  public function setQuantidade($iQuantidade){
    $this->iQuantidade = $iQuantidade;
  }
  /**
   * retorna valor
   * @return number
   */
  public function getValor(){
    return $this->nValor;
  }
  /**
   * define valor
   * @param number $nValor
   */
  public function setValor($nValor){
    $this->nValor = $nValor;
  }
  /**
   * retorna valor unitario do item
   * @return number
   */
  public function getValorUnitario(){
    return $this->nValorUnitario;
  }
  /**
   * define valor unitario do item
   * @param number $nValorUnitario
   */
  public function setValorUnitario($nValorUnitario){
    $this->nValorUnitario = $nValorUnitario;
  }


  public function getQuantidadeAnulada() {
    return $this->iQuantidadeAnulada;
  }

  public function setQuantidadeAnulada( $iQuantidadeAnulada ){
    $this->iQuantidadeAnulada = $iQuantidadeAnulada;
  }

  /**
   * Define o valor anulado do item
   * @param float $valorAnulado
   */
  public function setValorAnulado(float $valorAnulado ) {
    $this->nValorAnulado = $valorAnulado;
  }

  /**
   * Retorna o valor anulado do item
   * @return float $nValorAnulado
   */
  public function getValorAnulado() {
    return $this->nValorAnulado;
  }

  public function __construct( $iCodigoLancamento = null) {

    if (!empty($iCodigoLancamento)) {

      $oDaoMatOrdemItem = db_utils::getDao('matordemitem');

      $sCamposMatOrdemItem  = "m52_codlanc, m52_quant, m52_valor, m52_vlruni, e62_sequencial, ";
      $sCamposMatOrdemItem .= "(select coalesce(sum(m36_qtd), 0) from matordemitemanu where m36_matordemitem = m52_codlanc) as qtdanulado";
      $sCamposMatOrdemItem .= ", (select coalesce(sum(m36_vrlanu), 0) from matordemitemanu where m36_matordemitem = m52_codlanc) as vlranulado";

      $sSqlMatOrdemItem = $oDaoMatOrdemItem->sql_queryItemEmpenho(null, $sCamposMatOrdemItem, null, "m52_codlanc = ". $iCodigoLancamento);
      $rsMatOrdemItem   = $oDaoMatOrdemItem->sql_record($sSqlMatOrdemItem);

      if ($oDaoMatOrdemItem->numrows > 0 ) {

        $oDadosMatOrdemItem = db_utils::fieldsMemory($rsMatOrdemItem, 0);

        $this->iCodigoItemEmpenho = $oDadosMatOrdemItem->e62_sequencial;

        $this->setCodigoLancamento($oDadosMatOrdemItem->m52_codlanc);
        $this->setQuantidade($oDadosMatOrdemItem->m52_quant);
        $this->setValor($oDadosMatOrdemItem->m52_valor);
        $this->setValorUnitario($oDadosMatOrdemItem->m52_vlruni);
        $this->setQuantidadeAnulada($oDadosMatOrdemItem->qtdanulado);
        $this->setValorAnulado($oDadosMatOrdemItem->vlranulado);
      }
    }
  }

  /**
   * @return EmpenhoFinanceiroItem
   */
  public function getItemEmpenho () {

    if (empty($this->oItemEmpenho)) {
      $this->oItemEmpenho = new EmpenhoFinanceiroItem($this->iCodigoItemEmpenho);
    }
    return $this->oItemEmpenho;
  }
}