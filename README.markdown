Módulo integração API MoIP - OsCommerce
========================================

Módulo desenvolvido pela equipe MoIP Pagamentos (http://www.moip.com.br ), testado no OsCommerce 2.2 MS2 (http://www.oscommerce.com/ ).

Este módulo possui:

* Inclusão de layout personalizado da pagina de pagamento MoIP.
* Escolha do banner da pagina de opções de pagamento.
* Inclusão de banner personalizado.
* Retorno do cliente a sua loja.
* Atualização automática de status do pagamento.
* Alteração automática do Status do pedido.
* Envio automático de e-mail quando ocorre alteração de status.
* Calculo de frete próprio ( Caso haja calculo de frete em seu OsCommerce o Módulo irá enviar o valor de frete ao MoIP para cobrança do mesmo).
* Calculo de frete pelo MoIP ( Caso você não tenha ferramentas de calculo de frete o MoIP poderá calcular o frete para você com base na tabela dos correios).
* **Pagamento Direto** – Boleto Bancário (Com o pagamento Direto o cliente não visualiza a pagina do MoIP somente o Boleto para impressão)
* Personalização de Boleto - você poderá incluir sua logomarca no boleto e adicionar até três (3) linhas de instruções no corpo do boleto.

Instalação
----------

1 - **Atenção**: Para utilizar esse módulo é necessário que sua conta com o MoIP possua a ferramenta de Integração API habilitada. Caso a mesma não possua, entre em contato com a central do MoIP informe que você irá utilizar esse módulo e solicite a ativação da API em sua conta.

2 - ---

3 - Habilite a função de pagamento do MoIP em seu administrativo OsCommerce, **Módulos** >> **Pagamento**, escolha a opção **MoIP pagamentos** e clique no botão **Install**, assim como na imagem a seguir:


![img1](http://labs.moip.com.br/imagens_documentacao/moip_oscommerce1.png)

4 - Após instalar o módulo, você irá visualizar a página de visão geral. Assim como mostrado na imagem abaixo, clique no botão **editar** para configurar e habilitar seu novo módulo de pagamento MoIP.


![img2](http://labs.moip.com.br/imagens_documentacao/moip_oscommerce2.png)

5 - Logo você irá visualizar a pagina onde estão presentes os campos para configuração assim como imagem a seguir. O Próprio Módulo lhe dará instruções dos dados a serem preenchidos corretamente, leia-os atentamente.


![img3](http://labs.moip.com.br/imagens_documentacao/moip_oscommerce3.png)

6 - Depois de configurar o módulo conforme sua conta MoIP clique no botão **Atualizar** para salvar as configurações.

7 - Pronto, sua loja OsCommerce está configurado com a forma de pagamento MoIP, Bons Negócios.

Módulo em funcionamento

 
![img4](http://labs.moip.com.br/imagens_documentacao/moip_oscommerce4.png)


**Importante**: Talvez seja necessário copiar o arquivo “php.ini” para os diretórios “includes/languages/portugues/modules/payment” e “ext/modules/payment/”, pois seu OsCommerce pode necessitar do “register_globals” habilitado.

