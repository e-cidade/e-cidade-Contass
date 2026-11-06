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
 * model para controle da ordem de compra
 * @author Rafael Lopes
 * @version $Revision: 1.13 $
 * @package compras
 */
class OrdemDeCompra {

  /**
   * codigo da ordem de compra
   * @var integer
   */
  public  $iCodigoOrdem;

  /**
   * data de emissao da ordem
   * @var DBDate
   */
  private $oEmissao;

  /**
   * data de anulação da ordem
   * @var DBDate
   */
  private $oAnulacao;

  /**
   * Observação da anulação da ordem
   * @var ?string
   */
  private $sObsAnulacao;

  /**
   * departamento da ordem de compra
   * @var DBDepartamento
   */
  private $oDepartamento;

  /**
   * fornecedor da ordem de compra
   * @var CgmFisico|CgmJuridico
   */
  private $oFornecedor;

  /**
   * observação da ordem de compra
   * @var string
   */
  private $sObservacao;

  /**
   * tipo da compra
   * @var integer
   */
  private $iTipoCompra;

  /**
   * valor total da ordem
   * @var float
   */
  private $nTotalOrdem;

  /**
   * valor anulado da ordem
   * @var ?float
   */
  private $nValorAnulado;

  /**
   * valor lancado
   * @var float
   */
  private $nValorLancado;

  /**
   * valor a lancar
   * @var float
   */
  private $nValorLancar;

  /**
   * Armazena os itens da ordem de compra
   * @var ItemOrdemDeCompra[]
   */
  private $aItens = array();

  /**
   * Código do Departamento
   * @var integer
   */
  private $iCodigoDepartamento;

  /**
   * Código do Fornecedor
   * @var integer
   */
  private $iCodigoFornecedor;

  /**
   * @var EmpenhoFinanceiro
   */
  private $oEmpenhoFinanceiro;

  /**
   * Constante com o caminho das mensagens utilizadas pelo model
   * @const URL_MENSAGEM
   */
  const URL_MENSAGEM = "patrimonial.compras.OrdemDeCompra.";


  /**
   * Carrega os dados da ordem de compra
   * @param integer $iCodigoOrdem
   */
  public function __construct( $iCodigoOrdem = null ) {

    $this->iCodigoOrdem = $iCodigoOrdem;
    if (!empty($this->iCodigoOrdem)) {

      $oDaoMatOrdem         = db_utils::getDao('matordem');
      $oDaoMatestoqueitemoc = db_utils::getDao('matestoqueitemoc');
      $sSqlMatestoqueitemoc = $oDaoMatestoqueitemoc->sql_query(null,null,"sum(m71_valor) ", null,"m52_codordem = {$this->iCodigoOrdem} and m73_cancelado is false");

      $sCamposMatOrdem  = "m51_codordem   , ";
      $sCamposMatOrdem .= "m51_data       , ";
      $sCamposMatOrdem .= "m51_depto      , ";
      $sCamposMatOrdem .= "m51_numcgm     , ";
      $sCamposMatOrdem .= "m51_obs        , ";
      $sCamposMatOrdem .= "m51_tipo       , ";
      $sCamposMatOrdem .= "m51_valortotal , ";
      $sCamposMatOrdem .= "m53_data       , ";
      $sCamposMatOrdem .= "($sSqlMatestoqueitemoc) as valorlancado ";
      $sCamposMatOrdem .= ", m53_obs      , ";
      $sCamposMatOrdem .= "(select coalesce(sum(m36_vrlanu), 0) from matordemitemanu inner join matordemitem on matordemitemanu.m36_matordemitem = matordemitem.m52_codlanc and m52_codordem = {$this->iCodigoOrdem}) as valoranulado ";

      $sSqlMatOrdem = $oDaoMatOrdem->sql_query_tot( null,$sCamposMatOrdem , null, "m51_codordem = {$iCodigoOrdem}");
      $rsMatOrdem   = $oDaoMatOrdem->sql_record($sSqlMatOrdem);

      if ($oDaoMatOrdem->erro_status == "0") {
        throw new BusinessException(_M(self::URL_MENSAGEM."ordem_de_compra_nao_encontrada"));
      }

      $oDadosMatOrdem = db_utils::fieldsMemory($rsMatOrdem, 0);
      $nLancar        = $oDadosMatOrdem->m51_valortotal - $oDadosMatOrdem->valoranulado - $oDadosMatOrdem->valorlancado;
      $this->iCodigoFornecedor = $oDadosMatOrdem->m51_numcgm;
      $this->iCodigoDepartamento = $oDadosMatOrdem->m51_depto;
      $this->setEmissao(new DBDate($oDadosMatOrdem->m51_data));
      $this->setObservacao($oDadosMatOrdem->m51_obs);
      $this->setTipoCompra($oDadosMatOrdem->m51_tipo);
      $this->setTotalOrdem($oDadosMatOrdem->m51_valortotal);
      $this->setValorLancado($oDadosMatOrdem->valorlancado);
      $this->setValorAnulado($oDadosMatOrdem->valoranulado);
      $this->setValorLancar($nLancar);


      /*
       * verificamos se a ordem esta anulada, caso venha valor no campo m53_data
       * instanciamos e setamos o objeto anulacao
       */
      if ( $oDadosMatOrdem->m53_data != '' ) {
        $this->setAnulacao(new DBDate($oDadosMatOrdem->m53_data));
        $this->setObsAnulacao(substr($oDadosMatOrdem->m53_obs, 0, 90));
      }
    }
  }

  /**
   * retorna o codigo da ordem de compra
   * @return integer
   */
  public function getCodigoOrdem(){
    return $this->iCodigoOrdem;
  }

  /**
   * define o codigo da ordem de compra
   * @param integer $iCodigoOrdem
   */
  private function setCodigoOrdem($iCodigoOrdem){
    $this->iCodigoOrdem = $iCodigoOrdem;
  }

  /**
   * retorna a data de emissao
   * @return DBDate
   */
  public function getEmissao(){
    return $this->oEmissao;
  }

  /**
   * define a data de emissao
   * @param DBDate $oDaTaEmissao
   */
  public function setEmissao(DBDate $oDaTaEmissao){
    $this->oEmissao = $oDaTaEmissao;
  }

  /**
   * retorna a data de anulacao da ordem de compra
   * @return DBDate
   */
  public function getAnulacao(){
    return $this->oAnulacao;
  }

  /**
   * definimos a data de anulação da ordem de compra
   * @param DBDate $oDataAnulacao
   */
  public function setAnulacao(DBDate $oDataAnulacao){
    $this->oAnulacao  = $oDataAnulacao;
  }

  /**
   * retorna a observação de anulacao da ordem de compra
   * @return string
   */
  public function getObsAnulacao(){
    return $this->sObsAnulacao;
  }

  /**
   * definimos a observação de anulação da ordem de compra
   * @param ?string $sObsAnulacao
   */
  public function setObsAnulacao(?string $sObsAnulacao){
    $this->sObsAnulacao = $sObsAnulacao;
  }

  /**
   * retorna o departamento da ordem de compra
   * @return DBDepartamento
   */
  public function getDepartamento(){

    if (!empty($this->iCodigoDepartamento)) {
      $this->oDepartamento = DBDepartamentoRepository::getDBDepartamentoByCodigo($this->iCodigoDepartamento);
    }
    return $this->oDepartamento;
  }

  /**
   * definimos o departamento da ordem de compra
   * @param DBDepartamento $oDepartamento
   */
  public function setDepartamento(DBDepartamento $oDepartamento){
    $this->oDepartamento = $oDepartamento;
  }

  /**
   * retorna o fornecedor da ordem de compra
   * @return CgmFisico|CgmJuridico
   */
  public function getFornecedor(){

    if (!empty($this->iCodigoFornecedor) && empty($this->oFornecedor)) {
      $this->oFornecedor = CgmFactory::getInstanceByCgm($this->iCodigoFornecedor);
    }
    return $this->oFornecedor;
  }

  /**
   * Fornecedor
   * @param CgmJuridico|CgmFisico|CgmBase $oFornecedor
   */
  public function setFornecedor(CgmBase $oFornecedor){
    $this->oFornecedor = $oFornecedor;
  }

  /**
   * retorna a observacao de uma ordem de compra
   * @return string
   */
  public function getObservacao(){
    return $this->sObservacao;
  }

  /**
   * define a observao para ordem de compra
   * @param string $sObservacao
   */
  public function setObservacao($sObservacao){
    $this->sObservacao = $sObservacao;
  }

  /**
   * retorna tipo da compra
   * 1 - normal
   * 2 - virtual
   * @return integer
   */
  public function getTipoCompra(){
    return $this->iTipoCompra;
  }

  /**
   * define tipo da compra
   * 1 - normal
   * 2 - virtual
   * @param integer $sTipoCompra
   */
  public function setTipoCompra($iTipoCompra){
    $this->iTipoCompra = $iTipoCompra;
  }

  /**
   * retorna valor lancado
   * @return float
   */
  public function getValorLancado(){
    return $this->nValorLancado;
  }

  /**
   * define valor lancado
   * @param float $nValorLancado
   */
  public function setValorLancado($nValorLancado){
    $this->nValorLancado = $nValorLancado;
  }

  /**
   * retorna total da ordem
   * @return float
   */
  public function getTotalOrdem(){
    return $this->nTotalOrdem;
  }

  /**
   * define total da ordem
   * @param float $nTotalOrdem
   */
  public function setTotalOrdem($nTotalOrdem){
    $this->nTotalOrdem = $nTotalOrdem;
  }

    /**
   * retorna valor anulado da ordem
   * @return float
   */
  public function getValorAnulado() {
    return $this->nValorAnulado;
  }

  /**
   * define valor anulado da ordem
   * @param float
   */
  public function setValorAnulado($nValorAnulado) {
    $this->nValorAnulado = $nValorAnulado;
  }

  /**
   * retorna valor a lancar
   * @return float
   */
  public function getValorLancar(){
    return $this->nValorLancar;
  }

  /**
   * define valor a lancar
   * @param float $nValorLancar
   */
  public function setValorLancar($nValorLancar){
    $this->nValorLancar = $nValorLancar;
  }

  /**
   * Retorna array de todos itens de entrada
   * @return MovimentacaoItem[]
   */
  public function getEntradas(){

    $oDaoMatOrdemItem  = db_utils::getDao('matordemitem');
    $sWhereOrdemCompra = "m81_codtipo in (19, 12) and m52_codordem = {$this->iCodigoOrdem}";
    $sSqlMatOrdemItem  = $oDaoMatOrdemItem->sql_query_entradas( null, "*", null, $sWhereOrdemCompra);
    $rsMatOrdemItem    = $oDaoMatOrdemItem->sql_record($sSqlMatOrdemItem);

    $aItensEntrada     = array();
    if ($oDaoMatOrdemItem->numrows > 0) {

      $iTotalItens    = $oDaoMatOrdemItem->numrows;
      for ($iRowItem = 0; $iRowItem < $iTotalItens; $iRowItem++) {

        $oDadosMatOrdemItem  = db_utils::fieldsMemory($rsMatOrdemItem, $iRowItem);
        $oItem               = new Item($oDadosMatOrdemItem->m70_codmatmater);
        $oAlmoxarifado       = new Almoxarifado($oDadosMatOrdemItem->m80_coddepto);
        $oTipoMovimentacao   = TipoMovimentacaoEstoqueRepository::getTipoMovimentaoPorCodigo($oDadosMatOrdemItem->m81_codtipo);

        $oMovimentacaoItem   = new MovimentacaoItem($oItem);
        $oMovimentacaoItem->setQuantidade($oDadosMatOrdemItem->m52_quant);
        $oMovimentacaoItem->setQuantidadeEntrada($oDadosMatOrdemItem->m71_quant);
        $oMovimentacaoItem->setValor($oDadosMatOrdemItem->m71_valor);
        $oMovimentacaoItem->setAlmoxarifado($oAlmoxarifado);
        $oMovimentacaoItem->setTipoMovimentacao($oTipoMovimentacao);

        $aItensEntrada[]     = $oMovimentacaoItem;

      }
    }
    return $aItensEntrada;
  }

  /**
   * Retorna os itens da ordem de compra
   * @return ItemOrdemDeCompra[]
   * @throws BusinessException
   */
  public function getItens() {

    if (count($this->aItens) == 0) {

      $oDaoMatOrdemItem  = db_utils::getDao('matordemitem');
      $sSqlItens         = $oDaoMatOrdemItem->sql_query_ordcons(null, 'm52_codlanc', "m52_sequen", "m52_codordem = ".$this->getCodigoOrdem());
      $rsMatOrdemItem    = $oDaoMatOrdemItem->sql_record($sSqlItens);

      if ($oDaoMatOrdemItem->erro_status == "0") {
        throw new BusinessException(_M(self::URL_MENSAGEM."itens_nao_encontrados"));
      }

      for ($iRowItem = 0; $iRowItem < $oDaoMatOrdemItem->numrows; $iRowItem++){

        $iCodigoItemOrdemDeCompra = db_utils::fieldsMemory($rsMatOrdemItem, $iRowItem)->m52_codlanc;
        $this->aItens[]           = new ItemOrdemDeCompra($iCodigoItemOrdemDeCompra);
      }
    }
    return $this->aItens;
  }

  public function getItem($iOrdemCompra, $iCodEmpenho, $iCodMaterial){

    $oDaoMatOrdemItem  = db_utils::getDao('matordemitem');
    $sCamposMatOrdemItem  = "m52_quant as quantidade, m52_valor as valorOrdem,";
    $sCamposMatOrdemItem .= "(SELECT coalesce(sum(m36_qtd), 0)
     FROM matordemitemanu
     WHERE m36_matordemitem = m52_codlanc) AS qtdanulada,

    (SELECT coalesce(sum(m36_vrlanu), 0)
     FROM matordemitemanu
     WHERE m36_matordemitem = m52_codlanc) AS vlranulado";
    $sWhere = "m52_codordem = $iOrdemCompra AND pc01_codmater = $iCodMaterial AND e60_numemp = $iCodEmpenho";
    $oSqlOrdemCompra = $oDaoMatOrdemItem->sql_query_ordcons(null, $sCamposMatOrdemItem, null, $sWhere);
      $oOrdemCompra = $oDaoMatOrdemItem->sql_record($oSqlOrdemCompra);

      return db_utils::fieldsMemory($oOrdemCompra);

  }

  /**
   * @return EmpenhoFinanceiro
   */
  public function getEmpenhoFinanceiro() {

    $aItens = $this->getItens();
    $this->oEmpenhoFinanceiro = $aItens[0]->getItemEmpenho()->getEmpenhoFinanceiro();
    return $this->oEmpenhoFinanceiro;
  }


  public function addItemTabela($item){
    
    $oDaoOrdemItemTabela  = new cl_empordemtabela;
    $oDaoOrdemItemTabela->l223_pcmaterordem = $item->pcmaterordem;
    $oDaoOrdemItemTabela->l223_pcmatertabela = $item->pcmatertabela;
    $oDaoOrdemItemTabela->l223_quant = $item->quantidade;
    $oDaoOrdemItemTabela->l223_vlrn = $item->valorunit;
    $oDaoOrdemItemTabela->l223_total = round($item->quantidade * $item->valorunit,2);
    $oDaoOrdemItemTabela->l223_numemp = $item->codempenho;
    $oDaoOrdemItemTabela->l223_descr = $item->descricao;
    $oDaoOrdemItemTabela->l223_codordem = null;
    $oDaoOrdemItemTabela->incluir();
  }
  public function addItensTabela($item){
    
    $oDaoOrdemItemTabela  = new cl_empordemtabela;
    $oDaoOrdemItemTabela->l223_pcmaterordem = $item->item;
    $oDaoOrdemItemTabela->l223_pcmatertabela = $item->sequencia;
    $oDaoOrdemItemTabela->l223_descr = $item->descricao;
    $oDaoOrdemItemTabela->l223_quant = $item->quantidade;
    $oDaoOrdemItemTabela->l223_vlrn = $item->vlrn;
    $oDaoOrdemItemTabela->l223_total = $item->total;
    $oDaoOrdemItemTabela->l223_numemp = $item->empenho;
    $oDaoOrdemItemTabela->l223_descr = $item->descricao;
    $oDaoOrdemItemTabela->l223_codordem = $item->ordem;
    $oDaoOrdemItemTabela->incluir();
  }

  public function alterarItemTabela($item){
    
    $oDaoOrdemItemTabela  = new cl_empordemtabela;
    $oDaoOrdemItemTabela->l223_pcmaterordem = $item->pcmaterordem;
    $oDaoOrdemItemTabela->l223_descr = $item->descricao;
    $oDaoOrdemItemTabela->l223_pcmatertabela = $item->pcmatertabela;
    $oDaoOrdemItemTabela->l223_quant = $item->quantidade;
    $oDaoOrdemItemTabela->l223_vlrn = $item->valorunit;
    $oDaoOrdemItemTabela->l223_total = $item->quantidade * $item->valorunit;
    $oDaoOrdemItemTabela->l223_numemp = $item->codempenho;
    $oDaoOrdemItemTabela->alterar($item->coditemtabela);
  }

  public function excluirItemTabela($item){
    $oDaoOrdemItemTabela  = new cl_empordemtabela;
    $oDaoOrdemItemTabela->excluir($item->coditemtabela);
    $sqlOrdemItemTabela  = "select * from empordemtabela where l223_numemp = $item->codempenho and l223_codordem = 0 order by l223_pcmatertabela ASC";
    $rsOrdemItemTabela = $oDaoOrdemItemTabela->sql_record($sqlOrdemItemTabela);
    
    if (count($rsOrdemItemTabela) > 0) {
      
      $iTotalItens    = $oDaoOrdemItemTabela->numrows;
      for ($iRowItem = 0; $iRowItem < $iTotalItens; $iRowItem++) {

        

        $oDadosOrdemItemTabela  = db_utils::fieldsMemory($rsOrdemItemTabela, $iRowItem);

        $seq = $iRowItem +1;

        $sqlOrdemItemTabela  = "update empordemtabela set l223_pcmatertabela = $seq where l223_sequencial = $oDadosOrdemItemTabela->l223_sequencial and l223_numemp = $item->codempenho and l223_codordem = 0";
         
        db_query($sqlOrdemItemTabela);

      }
    }
  }

  public function getItensOrdemTabela($item){
    $oDaoOrdemItemTabela  = new cl_empordemtabela;
    $sqlOrdemItemTabela  = "select * from empordemtabela where l223_pcmaterordem  = $item->pcmaterordem and l223_numemp = $item->codempenho and l223_codordem = 0 order by l223_sequencial";
    $rsOrdemItemTabela = $oDaoOrdemItemTabela->sql_record($sqlOrdemItemTabela);
    $aItensEntrada     = array();
    if ($oDaoOrdemItemTabela->numrows > 0) {

      $iTotalItens    = $oDaoOrdemItemTabela->numrows;
      for ($iRowItem = 0; $iRowItem < $iTotalItens; $iRowItem++) {

        $oDadosOrdemItemTabela  = db_utils::fieldsMemory($rsOrdemItemTabela, $iRowItem);

        $oItentabela   = new stdClass();
        $oItentabela->pc01_codmater = $oDadosOrdemItemTabela->pc01_codmater;
        $oItentabela->l223_descr = $oDadosOrdemItemTabela->l223_descr;
        $oItentabela->l223_quant = $oDadosOrdemItemTabela->l223_quant;
        $oItentabela->l223_vlrn = $oDadosOrdemItemTabela->l223_vlrn;
        $oItentabela->l223_total = $oDadosOrdemItemTabela->l223_total;
        $oItentabela->l223_sequencial = $oDadosOrdemItemTabela->l223_sequencial;

        $aItensEntrada[]     = $oItentabela;

      }
    }
    return $aItensEntrada;
  }

  public function getValorOrdemTabela($item){
    
    $oDaoOrdemItemTabela  = new cl_empordemtabela;
    $sqlOrdemItemTabela  = "select sum(l223_total) as l223_total from empordemtabela where l223_numemp = $item->iCodigoempenho and l223_pcmaterordem = $item->iCodigoitem and l223_codordem = 0";
    $rsOrdemItemTabela = $oDaoOrdemItemTabela->sql_record($sqlOrdemItemTabela);
    $oDadosOrdemItemTabela  = db_utils::fieldsMemory($rsOrdemItemTabela, 0);
    return $oDadosOrdemItemTabela->l223_total;
  }
  public function alterarItemTabelaCadastrado($item){
    
    
    $sqlOrdemItemTabela  = "update empordemtabela set l223_descr = '$item->descricao', l223_quant = $item->quantidade, l223_vlrn = $item->vlrn, l223_total = $item->total where l223_pcmatertabela = $item->sequencia and l223_pcmaterordem = $item->item and l223_codordem = $item->ordem";
    
    db_query($sqlOrdemItemTabela);
    return true;
  }

  public function excluirItemTabelaCadastrado($item){
    
    
    $sqlOrdemItemTabela  = "delete from empordemtabela where l223_pcmatertabela = $item->sequencia and l223_pcmaterordem = $item->item and l223_codordem = $item->ordem";
   
    db_query($sqlOrdemItemTabela);
    $oDaoOrdemItemTabela  = new cl_empordemtabela;
    $sqlOrdemItemTabela  = "select * from empordemtabela where l223_codordem = $item->ordem order by l223_pcmatertabela ASC";
    $rsOrdemItemTabela = $oDaoOrdemItemTabela->sql_record($sqlOrdemItemTabela);
    
    if (count($rsOrdemItemTabela) > 0) {
      
      $iTotalItens    = $oDaoOrdemItemTabela->numrows;
      for ($iRowItem = 0; $iRowItem < $iTotalItens; $iRowItem++) {

        

        $oDadosOrdemItemTabela  = db_utils::fieldsMemory($rsOrdemItemTabela, $iRowItem);

        $seq = $iRowItem +1;

        $sqlOrdemItemTabela  = "update empordemtabela set l223_pcmatertabela = $seq where l223_sequencial = $oDadosOrdemItemTabela->l223_sequencial and l223_codordem = $item->ordem";
         
        db_query($sqlOrdemItemTabela);

      }
    }
    return true;
  }

}