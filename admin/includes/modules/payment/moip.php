<?php
  class osC_Payment_moip extends osC_Payment_Admin {

/**
 * The administrative title of the payment module
 *
 * @var string
 * @access private
 */

    var $_title;

/**
 * The code of the payment module
 *
 * @var string
 * @access private
 */

    var $_code = 'moip';

/**
 * The developers name
 *
 * @var string
 * @access private
 */

    var $_author_name = 'MoIP Labs';

/**
 * The developers address
 *
 * @var string
 * @access private
 */

  var $_author_www = 'http://labs.moip.com.br';

/**
 * The status of the module
 *
 * @var boolean
 * @access private
 */

    var $_status = false;

/**
 * Constructor
 */

    function osC_Payment_moip() {
      global $osC_Language;

      $this->_title = $osC_Language->get('payment_moip_title');
	  $this->_description = $osC_Language->get('payment_moip_description');
	  $this->_method_title = $osC_Language->get('payment_moip_method_title');
      $this->_status = (defined('MODULE_PAYMENT_MOIP_STATUS') && (MODULE_PAYMENT_MOIP_STATUS == '1') ? true : false);
      $this->_sort_order = (defined('MODULE_PAYMENT_MOIP_SORT_ORDER') ? MODULE_PAYMENT_MOIP_SORT_ORDER : null);
    }

/**
 * Checks to see if the module has been installed
 *
 * @access public
 * @return boolean
 */

    function isInstalled() {
      return (bool)defined('MODULE_PAYMENT_MOIP_STATUS');
    }

/**
 * Installs the module
 *
 * @access public
 * @see osC_Payment_Admin::install()
 */

    function install() {
      global $osC_Database;

      parent::install();
      $sort_order = 0;
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Aprovação de Pagamento - MoIP', 'MODULE_PAYMENT_MOIP_STATUS', '-1', 'Voce deseja aprovar compras utilizando o MoIP?', '6', '".$sort_order."', 'osc_cfg_set_boolean_value(array(1,-1))', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( 'Login MoIP', 'MODULE_PAYMENT_MOIP_LOGIN', 'usuario_moip',  'Informar seu login cadastrado com o MoIP.', '6', '".$sort_order++."',  now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  set_function, date_added) values ('Ambiente utilizado', 'MODULE_PAYMENT_MOIP_AMBIENTE', 'Producao',  'Escolha o ambiente desejado para utilização do Módulo, SandBox para ambiente de testes e homologação de sua loja e Producao para efetuar vendas reais.<br>Para utilizar o SandBox você deverá ter uma conta de testes em nosso ambiente SandBox.', '6', '".$sort_order++."',  'osc_cfg_set_boolean_value(array(\'Producao\', \'SandBox\'))', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( 'URL de sua loja', 'MODULE_PAYMENT_MOIP_URL_LOJA', 'http://www.seusite.com/loja/',  'Informe a URL da sua instalação TomatoCart, endereço de sua loja com barra no final, assim como exemplo.<br>Ex: http://www.seusite.com/loja/', '6', '".$sort_order++."',  now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( 'Layout', 'MODULE_PAYMENT_MOIP_LAYOUT', 'default',  'Informar o nome de seu layout criado em sua conta MoIP', '6', '".$sort_order++."',  now())");
	  $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  set_function, date_added) values ('Banner ', 'MODULE_PAYMENT_MOIP_BANNER', 'http://www.moip.com.br/imgs/banner_5_1.gif',  'Você pode escolher a dimensão do banner exibido para seu cliente.<br>Ex: Tamanho X Largura', '6', '".$sort_order++."',  'osc_cfg_select_option(array(\'http://www.moip.com.br/img/banner/160x260.gif\', \'http://www.moip.com.br/img/banner/300x250.gif\', \'http://www.moip.com.br/img/banner/728x90.gif\', \'http://www.moip.com.br/imgs/banner_5_1.gif\', \'PROPRIO\'), ', now())");
	  $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( 'Banner Próprio', 'MODULE_PAYMENT_MOIP_BANNER_PROPRIO', 'http://',  'Caso você tenha marcado como \"PROPRIO\" na opção acima digite a url de seu banner desejado.', '6', '".$sort_order++."',  now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  set_function, date_added) values ('Calculo de frete pelo MoIP ?', 'MODULE_PAYMENT_MOIP_FRETE', 'NAO',  'Você deseja que o MoIP calcule seu frete?<br>Caso seja \"NAO\" o módulo ira pegar o valor de frete gerado pelo TomatoCart. <br>Somente marque como SIM se você não tiver calculo de frete em sua loja.', '6', '".$sort_order++."',  'osc_cfg_set_boolean_value(array(\'SIM\', \'NAO\'))', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  set_function, date_added) values ('API - Habilitar Pagamento Direto', 'MODULE_PAYMENT_MOIP_XML_PAGAMENTO_DIRETO', 'NAO',  'Pagamento Direto para Boleto(É necessario que essa ferramenta esteja habilitada em sua conta MoIP através da central de atendimento.)', '6', '".$sort_order++."',  'osc_cfg_set_boolean_value(array(\'SIM\', \'NAO\'))', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added ) values ( 'Status de Falha', 'MODULE_PAYMENT_MOIP_ORDER_STATUS_ID', '8',  'Atualiza o status dos pedidos efetuados por este módulo de pagamento quando ocorre falha no pagamento.(Ex: Falha no pagamento MoIP), ', '6', '".$sort_order++."',  'osc_get_order_status_name', 'osc_cfg_pull_down_order_statuses', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added ) values ( 'Status MoIP - Autorizado (Pago)', 'MODULE_PAYMENT_MOIP_STATUS_1', '1',  'Pagamento já foi realizado porém ainda não foi creditado na Carteira MoIP recebedora (devido ao floating da forma de pagamento)', '6', '".$sort_order++."',  'osc_get_order_status_name', 'osc_cfg_pull_down_order_statuses', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added ) values ( 'Status MoIP - Iniciado', 'MODULE_PAYMENT_MOIP_STATUS_2', '2',  'Pagamento está sendo realizado ou janela do navegador foi fechada (pagamento abandonado)', '6', '".$sort_order++."',  'osc_get_order_status_name', 'osc_cfg_pull_down_order_statuses', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added ) values ( 'Status MoIP - Boleto Impresso', 'MODULE_PAYMENT_MOIP_STATUS_3', '3',  'Boleto foi impresso e ainda não foi pago', '6', '".$sort_order++."',  'osc_get_order_status_name', 'osc_cfg_pull_down_order_statuses', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added ) values ( 'Status MoIP - Concluido', 'MODULE_PAYMENT_MOIP_STATUS_4', '4',  'Pagamento já foi realizado e dinheiro já foi creditado na Carteira MoIP recebedora', '6', '".$sort_order++."',  'osc_get_order_status_name', 'osc_cfg_pull_down_order_statuses', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added ) values ( 'Status MoIP - Cancelado', 'MODULE_PAYMENT_MOIP_STATUS_5', '5',  'Pagamento foi cancelado pelo pagador, instituição de pagamento, MoIP ou recebedor antes de ser concluído', '6', '".$sort_order++."',  'osc_get_order_status_name', 'osc_cfg_pull_down_order_statuses', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added ) values ( 'Status MoIP - Em análise ', 'MODULE_PAYMENT_MOIP_STATUS_6', '6',  'Pagamento foi realizado com cartão de crédito e autorizado, porém está em análise pela Equipe MoIP. Não existe garantia de que será concluído', '6', '".$sort_order++."',  'osc_get_order_status_name', 'osc_cfg_pull_down_order_statuses', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added ) values ( 'Status MoIP - Estornado', 'MODULE_PAYMENT_MOIP_STATUS_7', '7',  'Pagamento foi estornado pelo pagador, recebedor, instituição de pagamento ou MoIP', '6', '".$sort_order++."',  'osc_get_order_status_name', 'osc_cfg_pull_down_order_statuses', now())");
	  $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( 'URL do logo da sua empresa', 'MODULE_PAYMENT_MOIP_IMG_BOLETO', '',  'Preencha com a url da imagem de sua logo marca para exibição no Boleto Bancario <br>Tamanho: 75x45', '6', '".$sort_order++."',  now())");
 	  $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( '1° Linha de instrução', 'MODULE_PAYMENT_MOIP_BOLETO_L1', '',  'Insira o conteúdo adicional de instruções do pagamento que será impresso no boleto', '6', '".$sort_order++."',  now())");
 	  $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( '2° Linha de instrução', 'MODULE_PAYMENT_MOIP_BOLETO_L2', '',  'Insira o conteúdo adicional de instruções do pagamento que será impresso no boleto', '6', '".$sort_order++."',  now())");
  	  $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( '3° Linha de instrução', 'MODULE_PAYMENT_MOIP_BOLETO_L3', '',  'Insira o conteúdo adicional de instruções do pagamento que será impresso no boleto', '6', '".$sort_order++."',  now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  use_function, set_function, date_added) values ('Regiao Onde usar o Metodo', 'MODULE_PAYMENT_MOIP_ZONE', '0', 'Selecione Brasil.', '6', ". $sort_order++ .", 'osc_cfg_use_get_zone_class_title', 'osc_cfg_set_zone_classes_pull_down_menu', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,  configuration_description, configuration_group_id, sort_order,  date_added ) values ( 'Ordem de exibição', 'MODULE_PAYMENT_MOIP_SORT_ORDER', '0',  'Determina a ordem de exibição do meio de pagamento.', '6', '".$sort_order++."',  now())");

      $osC_Database->simpleQuery("CREATE TABLE moip_transacoes (
                        id INT( 13 ) NOT NULL AUTO_INCREMENT ,
                        referencia VARCHAR( 250 ) NOT NULL ,
                        valor_compra INT( 10 ) NOT NULL ,
                        status_pagamento_moip INT( 4 ) NOT NULL ,
                        cod_transacao_moip VARCHAR( 12 ) NOT NULL ,
                        forma_pagamento_moip INT( 4 ) NOT NULL ,
                        tipo_pagamento_moip VARCHAR( 50 ) NOT NULL ,
                        quant_parcelas_moip VARCHAR( 2 ) NOT NULL ,
                        email_consumidor_moip VARCHAR( 128 ) NOT NULL ,
                        date_created datetime ,
                        PRIMARY KEY ( id ));"
                  );
    }

/**
 * Return the configuration parameter keys in an array
 *
 * @access public
 * @return array
 */

    function getKeys() {
      global $osC_Database;

      if (!isset($this->_keys)) {
		$key_listing = array();
		$qry = "select configuration_key from " . TABLE_CONFIGURATION . " where LOCATE('MODULE_PAYMENT_MOIP_', configuration_key)>0 order by sort_order";
		$findkey = $osC_Database->query($qry);
		while($findkey->next()){
			$key_listing[] = $findkey->value('configuration_key');
		} // while
        $this->_keys =  $key_listing;
      }
      return $this->_keys;
    }
  }
?>
