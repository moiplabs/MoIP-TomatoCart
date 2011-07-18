<?php
  class osC_Payment_moip extends osC_Payment {
    var $_title,
        $_code = 'moip',
        $_author_name = 'MoIP Labs',
        $_status = false,
		$enderecoPost,
		$chaveAcesso,
		$urlRetorno,
        $_sort_order;

    function osC_Payment_moip() {
      global $osC_Database, $osC_Language, $osC_ShoppingCart;

      $this->_title = $osC_Language->get('payment_moip_title');
      $this->_method_title = $osC_Language->get('payment_moip_method_title');
      $this->_description = $osC_Language->get('payment_moip_description');
      $this->_status = (defined('MODULE_PAYMENT_MOIP_STATUS') && (MODULE_PAYMENT_MOIP_STATUS == '1') ? true : false);
      $this->_sort_order = (defined('MODULE_PAYMENT_MOIP_SORT_ORDER') ? MODULE_PAYMENT_MOIP_SORT_ORDER : null);
      $this->urlRetorno = MODULE_PAYMENT_MOIP_URL_RETORNO;
	  $this->chaveAcesso  = MODULE_PAYMENT_MOIP_CHAVE;
	  $this->token  = MODULE_PAYMENT_MOIP_CHAVE;
	  
	  
      if (MODULE_PAYMENT_MOIP_AMBIENTE == 'Producao') {
      $this->form_action_url = "https://www.moip.com.br/PagamentoMoIP.do";
      $this->apc_url = "https://www.moip.com.br/PagamentoMoIP.do";
      }
      else
        $this->form_action_url = "https://desenvolvedor.moip.com.br/sandbox/PagamentoMoIP.do";
        $this->apc_url = "https://desenvolvedor.moip.com.br/sandbox/PagamentoMoIP.do";
	      
      if ($this->_status === true) {
        $this->order_status = MODULE_PAYMENT_MOIP_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MOIP_ORDER_STATUS_ID : (int)ORDERS_STATUS_PAID;
        
        if ((int)MODULE_PAYMENT_MOIP_ZONE > 0) {
          $check_flag = false;

          $Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');
          $Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
          $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_MOIP_ZONE);
          $Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
          $Qcheck->execute();

          while ($Qcheck->next()) {
            if ($Qcheck->valueInt('zone_id') < 1) {
              $check_flag = true;
              break;
            } elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {
              $check_flag = true;
              break;
            }
          }

          if ($check_flag == false) {
            $this->_status = false;
          }
        }
      }
    }

    function selection() {
      global $order;
      $shipping_cost = $order->info['shipping_cost'];
      $fields = array();
      $fields[] = array('title' => 'Pagamento realizado pelo MoIP',
                        'text'  => "Finalize seu pagamento no site seguro do MoIP, e proteja-se de fraudes.");
      $fields[] = array('title' => 'O MoIP dispõe de:',
                        'text'  => '');
      $fields[] = array('title' => '<img src="http://www.moip.com.br/imgs/banner_5_1.jpg">',
                        'text'  => '');
      if (MODULE_PAYMENT_MOIP_SHIPPING=="") { // C&#225;lculo do frete pelo PagSeguro habilitado
          $tipoFretes = array (array('id' => 'EN',
                                     'text' => 'PAC'),
                               array('id' => 'SD',
                                     'text' => 'Sedex'));
      }
      $selection = array('id' => $this->_code,
                         'module' => $this->_method_title,
                         'fields' => $fields);
      return $selection;
    }
    
    function pre_confirmation_check() {
		return false;
    }
    
    function confirmation() {
      global $osc_Database;
      $this->_order_id = osC_Order::insert(ORDERS_STATUS_PREPARING);
	  osC_Order::process($this->_order_id, $this->order_status);
    }

    function process_button() {
      global $osC_ShoppingCart, $osC_Currencies, $osC_Customer, $osC_Tax;
      
     $process_button_string = '';
      
      $params['id_carteira'] = MODULE_PAYMENT_MOIP_LOGIN;
	  $id_pedido = $this->_order_id;
   	  $params['redirect'] = 'true';
      $params['layout'] = MODULE_PAYMENT_MOIP_LAYOUT;
      $params['id_transacao'] = 'Pedido: '.$this->_order_id.' - Cliente: '.$osC_Customer->getID();
	  $params['url_retorno'] = MODULE_PAYMENT_MOIP_URL_LOJA."checkout_process.php";
      $params['valor'] = str_replace(".", "",$osC_ShoppingCart->getTotal());
      $params['valor'] = str_replace(".","",str_replace(",",".",$osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $osC_Currencies->getCode())));
      $params['pagador_cidade'] = $osC_ShoppingCart->getBillingAddress('city');
	  $params['pagador_bairro'] = $osC_ShoppingCart->getBillingAddress('suburb');
	  $params['pagador_estado'] = $osC_ShoppingCart->getBillingAddress('zone_code');
	  $replacements = array(" ", ".", ",", "-", ";");
      $params['pagador_telefone'] = $osC_ShoppingCart->getBillingAddress('telephone_number');
      $params['pagador_email'] = $osC_Customer->getEmailAddress('email_address');
      
      if ($osC_ShoppingCart->hasShippingAddress()) {
        $params['pagador_nome'] = $osC_ShoppingCart->getShippingAddress('firstname') . ' ' . $osC_ShoppingCart->getShippingAddress('lastname');
        $params['pagador_logradouro'] = $osC_ShoppingCart->getShippingAddress('street_address');
        $params['pagador_cep'] = $osC_ShoppingCart->getShippingAddress('postcode');
		
      }else {
        $params['pagador_nome'] = $params['billing_fullname'];
        $params['pagador_logradouro'] = $params['billing_address'];
        $params['pagador_cep'] = $params['billing_postcode'];
      }
      $products_description = array();
      if ($osC_ShoppingCart->hasContents()) {
        $products = $osC_ShoppingCart->getProducts();
        
        foreach($products as $product) {
          $product_name = $product['quantity'] . 'x : ' . $product['name']. '  R$ ' . $product['price'] . '<br/>';          
          if ($osC_ShoppingCart->hasVariants($product['id'])) {
            foreach ($osC_ShoppingCart->getVariants($product['id']) as $variant) {
              $product_name .= ' - ' . $variant['groups_name'] . ': ' . $variant['values_name']. $variant['values_price'] . '<br/>';
            }
          }
          
          $products_description[] = $product_name;
        }
        
        $params['nome'] = "Pedido: " + $this->_order_id . " [".$_SERVER["SERVER_NAME"]."] ";
        $params['descricao'] = implode( '           <br/>    ' ,$products_description);
      }
      
      
      foreach($params as $key => $value) {
        $process_button_string .= osc_draw_hidden_field($key, $value);
      }
      
      return $process_button_string;
    }
    
   function callback() {
      global $osC_Database, $osC_Currencies;
      
      foreach ($_POST as $key => $value) {
        $post_string .= $key . '=' . urlencode($value) . '&';
      } 
      
      $post_string = substr($post_string, 0, -0);
      
      $this->_transaction_response = $this->sendTransactionToGateway($this->apc_url, $post_string);
		
	$token = $this->chaveAcesso;
	$enderecoPost = "https://www.pagamentodigital.com.br/checkout/verify/";
    $this->_transaction_response = 'VERIFICADO';
	$resposta = osc_href_link(FILENAME_CHECKOUT, 'success_url' . $id_pedido, 'SSL', false, false, true);

      switch ($_POST['cod_status']) {
        case '1':
          $transaction_type = 'Transação pagamento efetuado';
          break;
        case '2':
          $transaction_type = 'Transação cancelada';
          break;
        case '0':
        default:
          $transaction_type = 'Transação pendente, aguardando pagamento';
          break;
      }
	 
       $comments_retorno = 'Pagamento Digital | Pedido Valor total: R$' . sprintf("%01.2f", $_POST["valor_original"]) . "\n" . ' Recebido em  ' . $_POST['data_transacao'] . "\n" . ' Transacao ID:' . $_POST['id_transacao'] . "\n" . ' Situação '. $transaction_type . '.';
	   $comments_venda = ' Cliente :  ' . urlencode(nl2br($_POST['cliente_nome'])) .  "\n" .' CPF : ' . $_POST['cliente_cpf'] . "\n" .' Email : ' . $_POST['cliente_email'] . "\n" .'  .';
	   osC_Order::process($_POST['id_pedido'], $this->order_status, $comments_retorno);
	   osC_Order::insertOrderStatusHistory($_POST['id_pedido'], $this->order_status, $comments_venda);
	   	  	      		
      }  
    }

?>
