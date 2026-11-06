<?php
/*
 *     E-cidade Software Publico para Gestao Municipal
 *  Copyright (C) 2014  DBselller Servicos de Informatica
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

use App\Repositories\Patrimonial\Compras\PrazoEntregaRepository;

require_once ("libs/db_stdlib.php");
require_once ("libs/db_utils.php");
require_once ("libs/db_app.utils.php");
require_once ("libs/db_conecta.php");
require_once ("libs/db_sessoes.php");
require_once ("libs/JSON.php");
require_once ("libs/exceptions/BusinessException.php");
require_once ("libs/exceptions/DBException.php");
require_once ("libs/exceptions/ParameterException.php");
require_once ("dbforms/db_funcoes.php");

$oJson                  = new services_json();
$oParam                 = $oJson->decode(str_replace("\\","",$_POST["json"]));

$oRetorno               = new stdClass();
$oRetorno->iStatus      = 1;
$oRetorno->sMessage     = '';

$aDadosRetorno          = array();
try {
  switch ($oParam->exec) {

    case "getDadosOrdem":

      $oOrdemCompra = new OrdemDeCompra($oParam->iOrdemCompra);
      $oDadosOrdem  = new stdClass();

      $oDadosOrdem->iCodigoOrdem  = $oOrdemCompra->getCodigoOrdem();
      $oDadosOrdem->dEmissao      = db_formatar($oOrdemCompra->getEmissao()->getDate(),'d');
      $oDadosOrdem->iDepto        = $oOrdemCompra->getDepartamento()->getCodigo();
      $oDadosOrdem->sDepto        = urlencode($oOrdemCompra->getDepartamento()->getNomeDepartamento());
      $oDadosOrdem->iCgm          = $oOrdemCompra->getFornecedor()->getCodigo();
      $oDadosOrdem->sCgm          = urlencode($oOrdemCompra->getFornecedor()->getNome());
      $oDadosOrdem->sObservacao   = urlencode($oOrdemCompra->getObservacao());
      $oDadosOrdem->dAnulacao     = '';
      $oDadosOrdem->sObsAnulacao  = '';
      $oDadosOrdem->iTipoCompra   = $oOrdemCompra->getTipoCompra();
      $oDadosOrdem->nTotalOrdem   = db_formatar($oOrdemCompra->getTotalOrdem(), 'f');
      $oDadosOrdem->nValorAnulado = db_formatar($oOrdemCompra->getValorAnulado(), 'f');
      $oDadosOrdem->nValorLancado = db_formatar($oOrdemCompra->getValorLancado(), 'f');
      $oDadosOrdem->nValorLancar  = db_formatar($oOrdemCompra->getValorLancar(), 'f');

      if ($oOrdemCompra->getAnulacao()){
        $oDadosOrdem->dAnulacao    = db_formatar($oOrdemCompra->getAnulacao()->getDate(),'d');
        $oDadosOrdem->sObsAnulacao = db_formatar($oOrdemCompra->getObsAnulacao(),'s');
      }
      $oRetorno->oDadosOrdem = $oDadosOrdem;

    break;

    case "getItens" :

      $oOrdemCompra          = new OrdemDeCompra($oParam->iOrdemCompra);
      $aItensOrdem           = $oOrdemCompra->getItens();
      $oRetorno->aItensOrdem = array();

      foreach ($aItensOrdem as $oItemOrdemDeCompra) {

        $oEmpenhoItem       = $oItemOrdemDeCompra->getItemEmpenho();
        $oEmpenhoFinanceiro = $oEmpenhoItem->getEmpenhoFinanceiro();
        $iQuantidadeAnulada = $oItemOrdemDeCompra->getQuantidadeAnulada();

        if (empty($iQuantidadeAnulada)) {
          $iQuantidadeAnulada = "";
        }

        $oDadosItem = new stdClass();
        $oDadosItem->sNumeroEmpenho        = $oEmpenhoFinanceiro->getCodigo() . "/" . $oEmpenhoFinanceiro->getAnoUso();
        $oDadosItem->iSequencia            = $oEmpenhoItem->getSequencialAutorizacaoItem();
        $oDadosItem->sDescricaoMaterial    = "{$oEmpenhoItem->getItemMaterialCompras()->getMaterial()} - {$oEmpenhoItem->getItemMaterialCompras()->getDescricao()}";
        $oDadosItem->sDescricaoCompleta    = "{$oEmpenhoItem->getItemMaterialCompras()->getDescricao()}. {$oEmpenhoItem->getItemMaterialCompras()->getComplemento()}";
        $oDadosItem->iQuantidade           = $oItemOrdemDeCompra->getQuantidade();
        $oDadosItem->nValorUnitario        = db_formatar($oItemOrdemDeCompra->getValorUnitario(), 'f');
        $oDadosItem->nValorTotal           = db_formatar($oItemOrdemDeCompra->getValor(), 'f');
        $oDadosItem->nQuantidadeAnulada    = $iQuantidadeAnulada;
        $oDadosItem->nValorAnulado         = db_formatar($oItemOrdemDeCompra->getValorAnulado(), 'f');
        $oRetorno->aItensOrdem[] = $oDadosItem;
      }

    break;

    case "getEntradas" :

      $oOrdemCompra        = new OrdemDeCompra($oParam->iOrdemCompra);
      $aEntradas           = $oOrdemCompra->getEntradas();
      $aDadosEntrada       = array();

      foreach ($aEntradas as $iEntradas => $oEntradas) {

        $oDados = new stdClass();
        $oDados->iMaterial         = $oEntradas->getItem()->getCodigo();
        $oDados->sMaterial         = urlencode($oEntradas->getItem()->getNome());
        $oDados->iQuantidade       = $oEntradas->getQuantidade();
        $oDados->iQuantidadeEntrada= $oEntradas->getQuantidadeEntrada();
        $oDados->iValor            = db_formatar($oEntradas->getValor(), "f");
        $oDados->sAlmoxarifado     = $oEntradas->getAlmoxarifado()->getNomeDepartamento();
        $oDados->sTipoMovimentacao = urlencode($oEntradas->getTipoMovimentacao()->getDescricao());

        $aDadosEntrada[]           = $oDados;

      }

      $oRetorno->aEntradas = $aDadosEntrada;

    break;

    case "adicionarItemOrdemTabela" :



      $oOrdemCompraItemTabela = new OrdemDeCompra();
      $oParam->descricao = db_stdClass::normalizeStringJsonEscapeString($oParam->descricao);

      $oOrdemCompraItemTabela->addItemTabela($oParam);
      
      $aItens           = $oOrdemCompraItemTabela->getItensOrdemTabela($oParam);
      
      $aDadosEntrada       = array();
      
      foreach ($aItens as $oItens) {
        
        $oDados = new stdClass();
        $oDados->pc01_codmater         = $oItens->pc01_codmater;
        $oDados->iQuantidade         = $oItens->l223_quant;
        $oDados->iUnit       = $oItens->l223_vlrn;
        $oDados->iValor       = $oItens->l223_total;
        $oDados->iSequencial= $oItens->l223_sequencial;
        $oDados->iDescricao = urlencode($oItens->l223_descr);

        $aDadosEntrada[]           = $oDados;

      }

      $oRetorno->itens = $aDadosEntrada;
      
      
    break;

    case "adicionarItensOrdemTabela" :



      $oOrdemCompraItemTabela = new OrdemDeCompra();
      foreach ($oParam->itens as $oItens) {
        $oItens->descricao = db_stdClass::normalizeStringJsonEscapeString($oItens->descricao);

        $oOrdemCompraItemTabela->addItensTabela($oItens);
        $oRetorno->ordem = $oItens->ordem;
      }

      
      
      
    break;

    case "buscaItemOrdemTabela" :

      $oOrdemCompraItemTabela = new OrdemDeCompra();
      $aItens           = $oOrdemCompraItemTabela->getItensOrdemTabela($oParam);
      
      $aDadosEntrada       = array();
      
      foreach ($aItens as $oItens) {
        
        $oDados = new stdClass();
        $oDados->pc01_codmater         = $oItens->pc01_codmater;
        $oDados->iQuantidade         = $oItens->l223_quant;
        $oDados->iUnit       = $oItens->l223_vlrn;
        $oDados->iValor       = $oItens->l223_total;
        $oDados->iSequencial= $oItens->l223_sequencial;
        $oDados->iDescricao = urlencode($oItens->l223_descr);

        $aDadosEntrada[]           = $oDados;

      }

      $oRetorno->itens = $aDadosEntrada;

    break;

    case "alterarItemOrdemTabela" :
      $oOrdemCompraItemTabela = new OrdemDeCompra();

      $oOrdemCompraItemTabela->alterarItemTabela($oParam);
      
      $aItens           = $oOrdemCompraItemTabela->getItensOrdemTabela($oParam);
      
      $aDadosEntrada       = array();
      
      foreach ($aItens as $oItens) {
        
        $oDados = new stdClass();
        $oDados->pc01_codmater         = $oItens->pc01_codmater;
        $oDados->iQuantidade         = $oItens->l223_quant;
        $oDados->iUnit       = $oItens->l223_vlrn;
        $oDados->iValor       = $oItens->l223_total;
        $oDados->iSequencial= $oItens->l223_sequencial;
        $oDados->iDescricao = urlencode($oItens->l223_descr);

        $aDadosEntrada[]           = $oDados;

      }

      $oRetorno->itens = $aDadosEntrada;

    break;

    case "alterarItensOrdemTabela" :
      $oOrdemCompraItemTabela = new OrdemDeCompra();

    
      
      foreach ($oParam->itens as $oItens) {
        
  

        $oOrdemCompraItemTabela->alterarItemTabelaCadastrado($oItens);

        $oRetorno->ordem = $oItens->ordem;

      }

      

    break;

    case "excluirItensOrdemTabela" :
      $oOrdemCompraItemTabela = new OrdemDeCompra();

    
      
      foreach ($oParam->itens as $oItens) {
        
  

        $oOrdemCompraItemTabela->excluirItemTabelaCadastrado($oItens);

        $oRetorno->ordem = $oItens->ordem;

      }

      

    break;

    case "excluirItemOrdemTabela" :
      $oOrdemCompraItemTabela = new OrdemDeCompra();

      $oOrdemCompraItemTabela->excluirItemTabela($oParam);
      
      $aItens           = $oOrdemCompraItemTabela->getItensOrdemTabela($oParam);
      
      $aDadosEntrada       = array();
      
      foreach ($aItens as $oItens) {
        
        $oDados = new stdClass();
        $oDados->pc01_codmater         = $oItens->pc01_codmater;
        $oDados->iQuantidade         = $oItens->l223_quant;
        $oDados->iUnit       = $oItens->l223_vlrn;
        $oDados->iValor       = $oItens->l223_total;
        $oDados->iSequencial= $oItens->l223_sequencial;
        $oDados->iDescricao = urlencode($oItens->l223_descr);

        $aDadosEntrada[]           = $oDados;

      }

      $oRetorno->itens = $aDadosEntrada;


    break;

    case "confereitemtabela" :
      $oOrdemCompraItemTabela = new OrdemDeCompra();
      
      $Valortotal           = $oOrdemCompraItemTabela->getValorOrdemTabela($oParam);
      
      
      $oRetorno->valor = $Valortotal;


    break;

    case "buscarPrazo" :
      $sql = "SELECT * 
      FROM pcparam 
      WHERE pc30_prazoent = (SELECT MAX(pc30_prazoent) FROM pcparam);";
      $rsResult = db_query($sql);
      db_fieldsmemory($rsResult, 0);
      $prazo = $pc30_prazoent;

      $oRetorno->prazo = $prazo;

      break;

    default:
      throw new ParameterException("Nenhuma Opção Definida");
    break;

      case "getPrazoAltera":
        $prazoentregaRepository = new PrazoEntregaRepository();
        $oRetorno->prazo = $prazoentregaRepository->getprazoscadastradosAltera($oParam->ordem);
    
        $itensPrazo = [];
        foreach ($oRetorno->prazo as $prazo) {
            $itemPrazo = new stdClass();
            $itemPrazo->pc97_descricao = $prazo->pc97_descricao;
            $itemPrazo->pc97_sequencial = $prazo->pc97_sequencial; 
            $itensPrazo[] = $itemPrazo;
        }
        $oRetorno->prazoCadastrado = $itensPrazo;
    
        break;

    case "getPrazo":
      $prazoentregaRepository = new PrazoEntregaRepository();
      $oRetorno->prazo = $prazoentregaRepository->getprazoscadastrados();
  
      $itensPrazo = [];
      foreach ($oRetorno->prazo as $prazo) {
          $itemPrazo = new stdClass();
          $itemPrazo->pc97_descricao = $prazo->pc97_descricao;
          $itemPrazo->pc97_sequencial = $prazo->pc97_sequencial; 
          $itensPrazo[] = $itemPrazo;
      }
      $oRetorno->prazoCadastrado = $itensPrazo;
  
      break;

  }

  $oRetorno->sMessage = urlencode($oRetorno->sMessage);

} catch (Exception $eErro){

  $oRetorno->iStatus  = 2;
  $oRetorno->sMessage = urlencode($eErro->getMessage());

}
echo $oJson->encode($oRetorno);
