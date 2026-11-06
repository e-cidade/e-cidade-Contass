<?
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

require_once("libs/db_stdlib.php");
require_once("libs/db_conecta.php");
require_once("libs/db_sessoes.php");
require_once("libs/db_usuariosonline.php");
require_once("classes/db_empelemento_classe.php");
require_once("classes/db_conplanoreduz_classe.php");
require_once("dbforms/db_funcoes.php");

?>
<html>
<head>
<title>DBSeller Inform&aacute;tica Ltda - P&aacute;gina Inicial</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="estilos.css" rel="stylesheet" type="text/css">
<link href="estilos/grid.style.css" rel="stylesheet" type="text/css">
<link href="estilos/tab.style.css" rel="stylesheet" type="text/css">
<script language="JavaScript" type="text/javascript" src="scripts/scripts.js"></script>
<script language="JavaScript" type="text/javascript" src="scripts/prototype.js"></script>
<script language="JavaScript" type="text/javascript" src="scripts/widgets/dbmessageBoard.widget.js"></script>
<script language="JavaScript" type="text/javascript" src="scripts/widgets/windowAux.widget.js"></script>
<script language="JavaScript" type="text/javascript" src="scripts/classes/infoLancamentoContabil.classe.js"></script>
<script language="JavaScript" type="text/javascript" src="scripts/datagrid.widget.js"></script>
<script language="JavaScript" type="text/javascript" src="scripts/strings.js"></script>
<script language="JavaScript" type="text/javascript" src="scripts/widgets/datagrid/plugins/DBHint.plugin.js"></script>
<link href="estilos/grid.style.css" rel="stylesheet" type="text/css">
</head>
<body bgcolor=#CCCCCC>
<center>
<fieldset>
  <legend>Itens</legend>
    <div id ='ctmitens'>
    </div>
</fieldset>

</center>
</body>
</html>
<script>

var oGet = js_urlToObject();

var sUrlRPC = 'com4_ordemdecompra001.RPC.php';
js_gridItens();

 function js_gridItens() {

  oGridItens = new DBGrid('Itens');
  oGridItens.nameInstance = 'oGridItens';
  oGridItens.setCellWidth(['80px' ,
                           '80px' ,
                           '270px',
//                           '270px',
                           '70px',
                           '80px',
                           '80px',
                           '80px',
                           '80px',]);

  oGridItens.setCellAlign(['center'  ,
                           'center'  ,
                           'left',
//                           'left',
                           'right'  ,
                           'right'  ,
                           'right'  ,
                           'right'  ,
                           'right']);


  oGridItens.setHeader(['Empenho',
                        'Ordem',
                        'Item',
//                        'Observação',
                        'Quantidade',
                        'Valor Unitário',
                        'Valor Total',
                        'Qtd Anulada',
                        'Vlr. Anulado']);

//  oGridItens.aHeaders[6].lDisplayed = false;

  oGridItens.setHeight(180);
  oGridItens.show($('ctmitens'));
  oGridItens.clearAll(true);

  js_getItensOrdem();

}


 function js_getItensOrdem() {

   var oParametros              = new Object();
       oParametros.exec         = 'getItens';
       oParametros.iOrdemCompra = oGet.m51_codordem;

   js_divCarregando("Aguarde, pesquisando itens da ordem de compra...",'msgBox');

   new Ajax.Request(sUrlRPC,
                   {method: "post",
                    parameters:'json='+Object.toJSON(oParametros),
                    onComplete: js_retornoItensOrdem
                   });
 }

 function js_retornoItensOrdem(oAjax){

   js_removeObj('msgBox');

   var oRetorno = eval("("+oAjax.responseText+")");
   oGridItens.clearAll(true);

   if (oRetorno.iStatus == 2 ) {

     alert(oRetorno.sMessage.urlDecode());
     return false;
   }

   oRetorno.aItensOrdem.each(

     function (oDado, iInd) {

       var aRow     = [];
           aRow[0]  = oDado.sNumeroEmpenho                   ;
           aRow[1]  = oDado.iSequencia                       ;
           aRow[2]  = oDado.sDescricaoMaterial.urlDecode().substr(0, 45)   ;
           aRow[3]  = '&nbsp;' + oDado.iQuantidade           ;
           aRow[4]  = oDado.nValorUnitario                   ;
           aRow[5]  = oDado.nValorTotal                      ;
           aRow[6] = oDado.nQuantidadeAnulada               ;
           aRow[7] = oDado.nValorAnulado                     ;
           oGridItens.addRow(aRow);

   });
   oGridItens.renderRows();

   oRetorno.aItensOrdem.each(function (oDado, iLinha) {

      oParametros = {iWidth:'150', oPosition : {sVertical : 'T', sHorizontal : 'L'}};
     // oGridItens.setHint(iLinha, 1, oDado.sDebito,  oParametros);
      // ou sem passar parametros
      oGridItens.setHint(iLinha, 2, oDado.sDescricaoCompleta.urlDecode());
    });

 }
</script>